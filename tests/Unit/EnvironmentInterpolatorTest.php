<?php

declare(strict_types=1);

use Shelfwood\SettingsYaml\Support\EnvironmentInterpolator;

describe('EnvironmentInterpolator', function () {
    beforeEach(function () {
        $this->interpolator = new EnvironmentInterpolator('test');

        // Set up test config values
        config(['test.API_KEY' => 'secret-key-123']);
        config(['test.API_SECRET' => 'secret-secret']);
        config(['test.EMPTY_VAR' => '']);
    });

    describe('interpolate()', function () {
        it('replaces ${VAR} with config value', function () {
            $data = ['api_key' => '${API_KEY}'];

            $result = $this->interpolator->interpolate($data);

            expect($result['api_key'])->toBe('secret-key-123');
        });

        it('handles multiple variables in one string', function () {
            $data = ['combined' => '${API_KEY}:${API_SECRET}'];

            $result = $this->interpolator->interpolate($data);

            expect($result['combined'])->toBe('secret-key-123:secret-secret');
        });

        it('returns empty string for undefined variable', function () {
            $data = ['undefined' => '${UNDEFINED_VAR}'];

            $result = $this->interpolator->interpolate($data);

            expect($result['undefined'])->toBe('');
        });

        it('recursively processes arrays', function () {
            $data = [
                'level1' => [
                    'level2' => [
                        'key' => '${API_KEY}',
                    ],
                ],
            ];

            $result = $this->interpolator->interpolate($data);

            expect($result['level1']['level2']['key'])->toBe('secret-key-123');
        });

        it('leaves non-string values unchanged', function () {
            $data = [
                'number' => 42,
                'bool' => true,
                'null' => null,
                'float' => 3.14,
            ];

            $result = $this->interpolator->interpolate($data);

            expect($result['number'])->toBe(42);
            expect($result['bool'])->toBe(true);
            expect($result['null'])->toBeNull();
            expect($result['float'])->toBe(3.14);
        });

        it('handles nested array interpolation', function () {
            $data = [
                'services' => [
                    'api' => [
                        'key' => '${API_KEY}',
                        'secret' => '${API_SECRET}',
                    ],
                    'other' => [
                        'enabled' => true,
                    ],
                ],
            ];

            $result = $this->interpolator->interpolate($data);

            expect($result['services']['api']['key'])->toBe('secret-key-123');
            expect($result['services']['api']['secret'])->toBe('secret-secret');
            expect($result['services']['other']['enabled'])->toBe(true);
        });

        it('preserves strings without variables', function () {
            $data = ['plain' => 'just a regular string'];

            $result = $this->interpolator->interpolate($data);

            expect($result['plain'])->toBe('just a regular string');
        });

        it('handles empty arrays', function () {
            $result = $this->interpolator->interpolate([]);

            expect($result)->toBe([]);
        });

        it('handles mixed content with variables', function () {
            $data = ['url' => 'https://api.example.com?key=${API_KEY}&v=1'];

            $result = $this->interpolator->interpolate($data);

            expect($result['url'])->toBe('https://api.example.com?key=secret-key-123&v=1');
        });

        it('uses custom config prefix', function () {
            config(['custom.MY_VAR' => 'custom-value']);
            $interpolator = new EnvironmentInterpolator('custom');

            $data = ['value' => '${MY_VAR}'];
            $result = $interpolator->interpolate($data);

            expect($result['value'])->toBe('custom-value');
        });
    });
});
