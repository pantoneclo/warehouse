<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Symfony\Component\Console\Input\InputOption;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->extend('command.seed', function (SeedCommand $command) {
            return $command
                ->addOption(
                    'update',
                    null,
                    InputOption::VALUE_NONE,
                    'Update existing batch and package.',
                );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
