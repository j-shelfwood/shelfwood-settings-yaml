<?php

declare(strict_types=1);

namespace Shelfwood\SettingsYaml\Support;

/**
 * Recursively merges settings arrays with instance values overriding shared values.
 */
class SettingsMerger
{
    /**
     * Merge shared and instance settings with proper override behavior.
     *
     * @param array<string, mixed> $shared Base/shared settings
     * @param array<string, mixed> $instance Instance-specific overrides
     * @return array<string, mixed> Merged settings
     */
    public function merge(array $shared, array $instance): array
    {
        $result = $shared;

        foreach ($instance as $key => $value) {
            if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                // Recursively merge arrays
                $result[$key] = $this->merge($result[$key], $value);
            } else {
                // Instance value overrides shared value (scalars replace, not merge)
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
