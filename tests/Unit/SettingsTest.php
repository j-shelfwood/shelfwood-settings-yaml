<?php

declare(strict_types=1);

use Shelfwood\SettingsYaml\Settings;

describe('Settings', function () {
    beforeEach(function () {
        $this->testPath = sys_get_temp_dir() . '/settings-yaml-test-' . getmypid() . '-' . mt_rand();

        // Create test directories
        @mkdir("{$this->testPath}/_shared", 0755, true);
        @mkdir("{$this->testPath}/test-instance", 0755, true);

        // Configure the package
        config(['settings-yaml.base_path' => $this->testPath]);
        config(['settings-yaml.shared_directory' => '_shared']);
        config(['settings-yaml.cache.enabled' => false]);
    });

    afterEach(function () {
        // Cleanup test files using global helper
        cleanupSettingsFiles($this->testPath);
    });

    describe('load()', function () {
        it('loads settings from instance-specific file', function () {
            createSettingsFile("{$this->testPath}/test-instance/settings.md", [
                'site' => ['name' => 'Test Site'],
            ]);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->get('site.name'))->toBe('Test Site');
        });

        it('falls back to shared file when instance file missing', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", [
                'site' => ['name' => 'Shared Site'],
            ]);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->get('site.name'))->toBe('Shared Site');
        });

        it('merges instance and shared settings recursively', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", [
                'colors' => [
                    'primary' => '#333',
                    'secondary' => '#666',
                ],
                'layout' => ['sidebar' => true],
            ]);
            createSettingsFile("{$this->testPath}/test-instance/settings.md", [
                'colors' => ['primary' => '#f00'],
            ]);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->get('colors.primary'))->toBe('#f00');
            expect($settings->get('colors.secondary'))->toBe('#666');
            expect($settings->get('layout.sidebar'))->toBe(true);
        });

        it('returns empty settings when both files missing', function () {
            $settings = Settings::load('nonexistent.md', 'test-instance');

            expect($settings->all())->toBe([]);
            expect($settings->getContent())->toBe('');
        });

        it('uses instance content over shared content', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", ['key' => 'value'], 'Shared content');
            createSettingsFile("{$this->testPath}/test-instance/settings.md", [], 'Instance content');

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->getContent())->toBe('Instance content');
        });

        it('falls back to shared content when instance has none', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", ['key' => 'value'], 'Shared content');
            createSettingsFile("{$this->testPath}/test-instance/settings.md", ['other' => 'data']);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->getContent())->toBe('Shared content');
        });
    });

    describe('get()', function () {
        it('returns value for dot notation key', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", [
                'site' => ['name' => 'Test'],
            ]);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->get('site.name'))->toBe('Test');
        });

        it('returns nested value', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", [
                'deep' => ['nested' => ['value' => 'found']],
            ]);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->get('deep.nested.value'))->toBe('found');
        });

        it('returns default for missing key', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", ['exists' => true]);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->get('missing', 'default'))->toBe('default');
        });

        it('returns null for missing key without default', function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", []);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->get('missing'))->toBeNull();
        });
    });

    describe('has()', function () {
        beforeEach(function () {
            createSettingsFile("{$this->testPath}/_shared/settings.md", [
                'exists' => 'value',
                'nested' => ['key' => 'value'],
            ]);
        });

        it('returns true for existing key', function () {
            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->has('exists'))->toBeTrue();
        });

        it('returns true for nested key', function () {
            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->has('nested.key'))->toBeTrue();
        });

        it('returns false for missing key', function () {
            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->has('missing'))->toBeFalse();
        });
    });

    describe('all()', function () {
        it('returns all settings as array', function () {
            $data = ['a' => 1, 'b' => 2];
            createSettingsFile("{$this->testPath}/_shared/settings.md", $data);

            $settings = Settings::load('settings.md', 'test-instance');

            expect($settings->all())->toBe($data);
        });
    });

    describe('convenience methods', function () {
        beforeEach(function () {
            createSettingsFile("{$this->testPath}/_shared/theme.md", ['colors' => ['primary' => '#333']]);
            createSettingsFile("{$this->testPath}/_shared/main.md", ['site' => ['name' => 'Main']]);
            createSettingsFile("{$this->testPath}/_shared/properties.md", ['routing' => ['base' => '/props']]);
            createSettingsFile("{$this->testPath}/_shared/booking.md", ['prepayment' => 30]);
        });

        it('theme() loads theme.md', function () {
            $settings = Settings::theme('test-instance');

            expect($settings->get('colors.primary'))->toBe('#333');
        });

        it('main() loads main.md', function () {
            $settings = Settings::main('test-instance');

            expect($settings->get('site.name'))->toBe('Main');
        });

        it('properties() loads properties.md', function () {
            $settings = Settings::properties('test-instance');

            expect($settings->get('routing.base'))->toBe('/props');
        });

        it('booking() loads booking.md', function () {
            $settings = Settings::booking('test-instance');

            expect($settings->get('prepayment'))->toBe(30);
        });
    });
});
