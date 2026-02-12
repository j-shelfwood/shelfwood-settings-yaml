<?php

namespace Shelfwood\SettingsYaml\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Shelfwood\SettingsYaml\SettingsServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SettingsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Default test config - can be overridden in individual tests
    }
}
