<?php

declare(strict_types=1);

namespace Shelfwood\SettingsYaml;

use Illuminate\Support\Facades\Cache;
use Shelfwood\MarkdownFrontmatter\Parser;
use Shelfwood\SettingsYaml\Support\EnvironmentInterpolator;
use Shelfwood\SettingsYaml\Support\SettingsMerger;

/**
 * Settings loader with automatic fallback from instance-specific to shared defaults.
 *
 * Loads YAML frontmatter from markdown files and merges instance values with shared defaults.
 */
class Settings
{
    /** @var array<string, mixed> */
    private array $data;

    private string $content;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data, string $content = '')
    {
        $this->data = $data;
        $this->content = $content;
    }

    /**
     * Load settings with automatic instance → shared fallback.
     */
    public static function load(string $filename, string $instanceId): self
    {
        $cacheEnabled = config('settings-yaml.cache.enabled', true);
        $isTestingEnvironment = app()->environment('testing')
            || app()->runningUnitTests()
            || config('cache.default') === 'array';

        if (!$cacheEnabled || $isTestingEnvironment) {
            return self::loadWithFallback($filename, $instanceId);
        }

        $cacheKey = self::getCacheKey($instanceId, $filename);
        $cacheTtl = config('settings-yaml.cache.ttl', 86400);

        $settings = Cache::remember(
            $cacheKey,
            $cacheTtl,
            fn () => self::loadWithFallback($filename, $instanceId)
        );

        // Handle __PHP_Incomplete_Class from stale cache
        if ($settings instanceof \__PHP_Incomplete_Class) {
            Cache::forget($cacheKey);
            return self::loadWithFallback($filename, $instanceId);
        }

        return $settings;
    }

    /**
     * Load settings with proper instance → shared fallback at the value level.
     */
    private static function loadWithFallback(string $filename, string $instanceId): self
    {
        $basePath = config('settings-yaml.base_path', base_path('instance'));
        $sharedDir = config('settings-yaml.shared_directory', '_shared');

        $instancePath = "{$basePath}/{$instanceId}/{$filename}";
        $sharedPath = "{$basePath}/{$sharedDir}/{$filename}";

        $parser = new Parser();
        $merger = new SettingsMerger();
        $interpolator = new EnvironmentInterpolator(
            config('settings-yaml.env_config_prefix', 'credentials')
        );

        // Load shared defaults
        $sharedProperties = [];
        $sharedContent = '';
        if (file_exists($sharedPath)) {
            $parsed = $parser->parse(file_get_contents($sharedPath));
            $sharedProperties = $parsed->metadata->toArray();
            $sharedContent = $parsed->content;
        }

        // Load instance overrides
        $instanceProperties = [];
        $instanceContent = '';
        if (file_exists($instancePath)) {
            $parsed = $parser->parse(file_get_contents($instancePath));
            $instanceProperties = $parsed->metadata->toArray();
            $instanceContent = $parsed->content;
        }

        // Merge settings (instance overrides shared)
        $mergedProperties = $merger->merge($sharedProperties, $instanceProperties);

        // Interpolate environment variables
        $mergedProperties = $interpolator->interpolate($mergedProperties);

        // Use instance content if available, else shared
        $finalContent = $instanceContent ?: $sharedContent;

        return new self($mergedProperties, $finalContent);
    }

    /**
     * Get a configuration value using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool
    {
        return data_get($this->data, $key) !== null;
    }

    /**
     * Get all configuration data as array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get the markdown content body (if any).
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Clear cached settings for an instance.
     *
     * @param string|null $instanceId Instance to clear, or null for all common files
     * @param array<string>|null $files Specific files to clear, or null for common files
     */
    public static function clearCache(?string $instanceId = null, ?array $files = null): void
    {
        $files ??= ['theme.md', 'main.md', 'properties.md', 'booking.md'];

        if ($instanceId) {
            foreach ($files as $file) {
                Cache::forget(self::getCacheKey($instanceId, $file));
            }
        }
    }

    private static function getCacheKey(string $instanceId, string $filename): string
    {
        $prefix = config('settings-yaml.cache.prefix', 'settings');

        return "{$prefix}:{$instanceId}:{$filename}";
    }

    // Convenience methods for common settings files

    public static function theme(string $instanceId): self
    {
        return self::load('theme.md', $instanceId);
    }

    public static function main(string $instanceId): self
    {
        return self::load('main.md', $instanceId);
    }

    public static function properties(string $instanceId): self
    {
        return self::load('properties.md', $instanceId);
    }

    public static function booking(string $instanceId): self
    {
        return self::load('booking.md', $instanceId);
    }
}
