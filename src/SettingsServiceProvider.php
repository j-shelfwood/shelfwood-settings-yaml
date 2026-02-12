<?php

declare(strict_types=1);

namespace Shelfwood\SettingsYaml;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/settings-yaml.php',
            'settings-yaml'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/settings-yaml.php' => config_path('settings-yaml.php'),
            ], 'settings-yaml-config');
        }
    }
}
