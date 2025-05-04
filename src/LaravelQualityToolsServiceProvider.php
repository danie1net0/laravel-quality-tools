<?php

namespace DDR\LaravelQualityTools;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use DDR\LaravelQualityTools\Commands\LaravelQualityToolsCommand;

class LaravelQualityToolsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-quality-tools')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_quality_tools_table')
            ->hasCommand(LaravelQualityToolsCommand::class);
    }
}
