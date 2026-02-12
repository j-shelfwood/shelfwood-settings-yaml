<?php

declare(strict_types=1);

namespace Shelfwood\SettingsYaml\Support;

/**
 * Interpolates environment variables in settings values.
 *
 * Replaces ${VAR_NAME} patterns with values from Laravel config.
 */
class EnvironmentInterpolator
{
    public function __construct(
        private string $configPrefix = 'credentials'
    ) {}

    /**
     * Interpolate environment variables in the given data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function interpolate(array $data): array
    {
        return $this->processArray($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function processArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->processArray($value);
            } elseif (is_string($value)) {
                $result[$key] = $this->processString($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function processString(string $value): string
    {
        return preg_replace_callback(
            '/\$\{([^}]+)\}/',
            fn (array $matches) => $this->getConfigValue($matches[1]),
            $value
        ) ?? $value;
    }

    private function getConfigValue(string $varName): string
    {
        $configKey = $this->configPrefix . '.' . $varName;

        return (string) config($configKey, '');
    }
}
