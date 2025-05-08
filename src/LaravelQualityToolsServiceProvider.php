<?php

namespace DDR\LaravelQualityTools;

use DDR\LaravelQualityTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelQualityToolsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-quality-tools')
            ->hasConfigFile()
            ->hasCommands(InstallCommand::class);
    }
}
