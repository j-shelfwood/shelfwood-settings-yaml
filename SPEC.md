# shelfwood/settings-yaml

**Laravel package for YAML-based configuration with fallback hierarchy**

## Overview

A Laravel package for loading configuration from YAML frontmatter files with automatic fallback from instance-specific to shared defaults. Supports recursive merging, environment variable interpolation, and intelligent caching.

## Installation

```bash
composer require shelfwood/settings-yaml
```

## Requirements

- PHP 8.2+
- Laravel 11.0+
- shelfwood/markdown-frontmatter ^1.0

## Features

- Load settings from YAML frontmatter markdown files
- Automatic fallback hierarchy (instance → shared)
- Recursive settings merge (instance overrides shared)
- Environment variable interpolation (`${VAR_NAME}`)
- 24-hour caching with automatic invalidation
- Dot notation access (`settings.nested.key`)
- Named configuration files (theme.md, main.md, etc.)

## Configuration

```php
// config/settings-yaml.php
return [
    // Base path for settings files
    'base_path' => base_path('instance'),

    // Shared defaults directory name
    'shared_directory' => '_shared',

    // Cache settings
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'ttl' => env('SETTINGS_CACHE_TTL', 86400), // 24 hours
        'prefix' => 'settings',
    ],

    // Environment variable interpolation config key
    'env_config_key' => 'pms.credentials',
];
```

## API Design

### Settings Loader

```php
use Shelfwood\SettingsYaml\Settings;

// Load settings file with fallback
$settings = Settings::load('theme.md', $instanceId);

// Access values with dot notation
$primaryColor = $settings->get('colors.primary', '#333333');

// Check if key exists
if ($settings->has('layout.sidebar')) {
    // ...
}

// Get all settings as array
$all = $settings->all();
```

### Facade (Optional)

```php
use Shelfwood\SettingsYaml\Facades\Settings;

// Load specific file
$theme = Settings::load('theme.md');

// Convenience methods for common files
$theme = Settings::theme();       // theme.md
$main = Settings::main();         // main.md
$booking = Settings::booking();   // booking.md
```

### Settings Value Object

```php
final class Settings
{
    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function all(): array;
    public function getContent(): string;  // Markdown body (if any)

    public static function load(string $filename, string $instanceId): self;
    public static function clearCache(?string $instanceId = null): void;
}
```

## Directory Structure

```
src/
├── Settings.php                    # Main settings loader
├── SettingsServiceProvider.php     # Laravel service provider
├── Facades/
│   └── Settings.php                # Optional facade
├── Contracts/
│   └── SettingsLoaderInterface.php # Contract for custom loaders
└── Support/
    ├── SettingsMerger.php          # Recursive merge logic
    └── EnvironmentInterpolator.php # ${VAR} replacement

config/
└── settings-yaml.php               # Package configuration

tests/
├── Unit/
│   ├── SettingsTest.php
│   ├── SettingsMergerTest.php
│   └── EnvironmentInterpolatorTest.php
├── Feature/
│   └── SettingsLoadingTest.php
└── Pest.php
```

## Merge Behavior

### Recursive Merge Rules

```yaml
# _shared/theme.md
---
colors:
  primary: '#333333'
  secondary: '#666666'
  accent: '#0066cc'
layout:
  sidebar: true
  footer: true
---

# instance/example.com/theme.md
---
colors:
  primary: '#ff0000'  # Override
  # secondary inherited from shared
  # accent inherited from shared
# layout fully inherited from shared
---
```

**Result:**
```php
[
    'colors' => [
        'primary' => '#ff0000',    // Overridden
        'secondary' => '#666666',  // Inherited
        'accent' => '#0066cc',     // Inherited
    ],
    'layout' => [
        'sidebar' => true,         // Inherited
        'footer' => true,          // Inherited
    ],
]
```

### Merge Priority

1. Instance-specific values override shared values
2. Missing instance keys fall back to shared
3. Arrays are recursively merged
4. Scalars are replaced (not merged)

## Environment Variable Interpolation

```yaml
# Settings file
---
api:
  key: '${API_KEY}'
  secret: '${API_SECRET}'
database:
  password: '${DB_PASSWORD}'
---
```

Interpolation uses `config('pms.credentials.VAR_NAME')` by default (configurable).

## Caching Strategy

```php
// Cache key format
"settings:{instance_id}:{filename}"

// Example
"settings:example.com:theme.md"

// Cache invalidation
Settings::clearCache('example.com');  // Clear specific instance
Settings::clearCache();               // Clear all instances
```

### Cache Bypass

- Testing environment (`APP_ENV=testing`)
- Array cache driver (`CACHE_DRIVER=array`)
- Unit tests (`app()->runningUnitTests()`)

### Incomplete Class Handling

If cached object has `__PHP_Incomplete_Class` (after deployment/autoload changes), cache is automatically invalidated and reloaded.

## Test Coverage Requirements

### Unit Tests (SettingsTest.php)

```php
describe('Settings', function () {
    describe('load()', function () {
        it('loads settings from instance-specific file');
        it('falls back to shared file when instance file missing');
        it('merges instance and shared settings recursively');
        it('returns empty settings when both files missing');
        it('caches loaded settings');
        it('bypasses cache in testing environment');
        it('handles __PHP_Incomplete_Class in cache');
    });

    describe('get()', function () {
        it('returns value for dot notation key');
        it('returns nested value');
        it('returns default for missing key');
    });

    describe('has()', function () {
        it('returns true for existing key');
        it('returns true for nested key');
        it('returns false for missing key');
    });

    describe('all()', function () {
        it('returns all settings as array');
    });

    describe('clearCache()', function () {
        it('clears cache for specific instance');
        it('clears all settings files for instance');
    });
});
```

### Unit Tests (SettingsMergerTest.php)

```php
describe('SettingsMerger', function () {
    describe('merge()', function () {
        it('merges flat arrays');
        it('recursively merges nested arrays');
        it('instance values override shared values');
        it('preserves shared keys not in instance');
        it('handles empty shared array');
        it('handles empty instance array');
        it('replaces scalars (does not merge)');
        it('handles deeply nested structures');
    });
});
```

### Unit Tests (EnvironmentInterpolatorTest.php)

```php
describe('EnvironmentInterpolator', function () {
    describe('interpolate()', function () {
        it('replaces ${VAR} with config value');
        it('handles multiple variables in one string');
        it('returns empty string for undefined variable');
        it('recursively processes arrays');
        it('leaves non-string values unchanged');
        it('handles nested array interpolation');
    });
});
```

### Feature Tests (SettingsLoadingTest.php)

```php
describe('Settings Loading', function () {
    it('loads theme settings for instance');
    it('loads main settings for instance');
    it('inherits from shared when instance file partial');
    it('caches settings between requests');
    it('invalidates cache on clearCache()');
});
```

## Source File Mapping

| Package File | Original Location |
|--------------|-------------------|
| `src/Settings.php` | `modules/Content/Theme/Settings.php` |
| `src/Support/SettingsMerger.php` | Extracted from Settings.php |
| `src/Support/EnvironmentInterpolator.php` | Extracted from Settings.php |

## Changes from Original

1. **Namespace**: `Modules\Content\Theme` → `Shelfwood\SettingsYaml`
2. **Decouple from Instance facade**: Accept instance ID as parameter
3. **Extract merge logic**: Separate `SettingsMerger` class
4. **Extract interpolation**: Separate `EnvironmentInterpolator` class
5. **Configurable paths**: Use config instead of hardcoded `instance/` path
6. **Remove File inheritance**: Settings no longer extends File base class

## composer.json

```json
{
    "name": "shelfwood/settings-yaml",
    "description": "Laravel YAML-based configuration with fallback hierarchy",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Shelfwood",
            "email": "packages@shelfwood.dev"
        }
    ],
    "require": {
        "php": "^8.2",
        "illuminate/support": "^11.0",
        "illuminate/cache": "^11.0",
        "illuminate/config": "^11.0",
        "shelfwood/markdown-frontmatter": "^1.0"
    },
    "require-dev": {
        "orchestra/testbench": "^9.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Shelfwood\\SettingsYaml\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Shelfwood\\SettingsYaml\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Shelfwood\\SettingsYaml\\SettingsServiceProvider"
            ],
            "aliases": {
                "Settings": "Shelfwood\\SettingsYaml\\Facades\\Settings"
            }
        }
    },
    "scripts": {
        "test": "pest",
        "test:coverage": "pest --coverage"
    },
    "config": {
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "stable"
}
```

## Development Checklist

- [ ] Create composer.json
- [ ] Create config/settings-yaml.php
- [ ] Extract Settings.php (remove Instance facade dependency)
- [ ] Create SettingsMerger.php
- [ ] Create EnvironmentInterpolator.php
- [ ] Create SettingsServiceProvider.php
- [ ] Create Settings facade
- [ ] Configure Pest with Orchestra Testbench
- [ ] Write SettingsTest.php
- [ ] Write SettingsMergerTest.php
- [ ] Write EnvironmentInterpolatorTest.php
- [ ] Write SettingsLoadingTest.php (feature)
- [ ] Achieve 100% test coverage
- [ ] Add to worktree as path repository
- [ ] Refactor worktree to use package
- [ ] Verify all worktree tests pass
