<?php namespace Modules\Core\Console\Installers\Scripts\UserProviders;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Console\Installers\SetupScript;

class SentinelInstaller extends ProviderInstaller implements SetupScript
{
    /**
     * Check if the user driver is correctly registered.
     * @return bool
     */
    public function checkIsInstalled()
    {
        return class_exists('Cartalyst\Sentinel\Laravel\SentinelServiceProvider');
    }

    /**
     * Not called
     * @return mixed
     */
    public function composer()
    {
        $this->composer->enableOutput($this->command);
        $this->composer->install('cartalyst/sentinel:dev-feature/laravel-5');
        $this->composer->remove('cartalyst/sentry');
        $this->composer->dumpAutoload();

        // Dynamically register the service provider, so we can use it during publishing
        $this->application->register('Cartalyst\Sentinel\Laravel\SentinelServiceProvider');
    }

    /**
     * @return mixed
     */
    public function publish()
    {
        $this->command->call('vendor:publish', ['--provider' => 'Cartalyst\Sentinel\Laravel\SentinelServiceProvider']);
    }

    /**
     * @return mixed
     */
    public function migrate()
    {
        $this->command->call('migrate');
    }

    /**
     * @return mixed
     */
    public function configure()
    {
        $this->replaceCartalystUserModelConfiguration(
            'Cartalyst\Sentinel\Users\EloquentUser',
            'Sentinel'
        );

        $this->changeDefaultUserProvider('Sentinel');

        $this->bindUserRepositoryOnTheFly('Sentinel');
    }

    /**
     * @return mixed
     */
    public function seed()
    {
        $this->command->call('db:seed', ['--class' => 'Modules\User\Database\Seeders\SentinelGroupSeedTableSeeder']);
    }

    /**
     * @param $password
     * @return mixed
     */
    public function getHashedPassword($password)
    {
        return Hash::make($password);
    }

    /**
     * @param $driver
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function changeDefaultUserProvider($driver)
    {
        $path = base_path('config/asgard.user.users.php');
        $config = $this->finder->get($path);
        $config = str_replace('Sentry', $driver, $config);
        $this->finder->put($path, $config);
    }
}