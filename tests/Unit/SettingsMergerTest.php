<?php

declare(strict_types=1);

use Shelfwood\SettingsYaml\Support\SettingsMerger;

describe('SettingsMerger', function () {
    beforeEach(function () {
        $this->merger = new SettingsMerger();
    });

    describe('merge()', function () {
        it('merges flat arrays', function () {
            $shared = ['a' => 1, 'b' => 2];
            $instance = ['c' => 3];

            $result = $this->merger->merge($shared, $instance);

            expect($result)->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
        });

        it('instance values override shared values', function () {
            $shared = ['color' => 'red', 'size' => 'large'];
            $instance = ['color' => 'blue'];

            $result = $this->merger->merge($shared, $instance);

            expect($result['color'])->toBe('blue');
            expect($result['size'])->toBe('large');
        });

        it('recursively merges nested arrays', function () {
            $shared = [
                'theme' => [
                    'colors' => ['primary' => '#333', 'secondary' => '#666'],
                    'fonts' => ['heading' => 'Arial'],
                ],
            ];
            $instance = [
                'theme' => [
                    'colors' => ['primary' => '#f00'],
                ],
            ];

            $result = $this->merger->merge($shared, $instance);

            expect($result['theme']['colors']['primary'])->toBe('#f00');
            expect($result['theme']['colors']['secondary'])->toBe('#666');
            expect($result['theme']['fonts']['heading'])->toBe('Arial');
        });

        it('preserves shared keys not in instance', function () {
            $shared = ['a' => 1, 'b' => 2, 'c' => 3];
            $instance = ['b' => 20];

            $result = $this->merger->merge($shared, $instance);

            expect($result)->toBe(['a' => 1, 'b' => 20, 'c' => 3]);
        });

        it('handles empty shared array', function () {
            $shared = [];
            $instance = ['key' => 'value'];

            $result = $this->merger->merge($shared, $instance);

            expect($result)->toBe(['key' => 'value']);
        });

        it('handles empty instance array', function () {
            $shared = ['key' => 'value'];
            $instance = [];

            $result = $this->merger->merge($shared, $instance);

            expect($result)->toBe(['key' => 'value']);
        });

        it('replaces scalars (does not merge)', function () {
            $shared = ['value' => 'old'];
            $instance = ['value' => 'new'];

            $result = $this->merger->merge($shared, $instance);

            expect($result['value'])->toBe('new');
        });

        it('handles deeply nested structures', function () {
            $shared = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'a' => 1,
                            'b' => 2,
                        ],
                    ],
                ],
            ];
            $instance = [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'b' => 20,
                            'c' => 30,
                        ],
                    ],
                ],
            ];

            $result = $this->merger->merge($shared, $instance);

            expect($result['level1']['level2']['level3'])->toBe([
                'a' => 1,
                'b' => 20,
                'c' => 30,
            ]);
        });

        it('replaces array with scalar', function () {
            $shared = ['key' => ['nested' => 'value']];
            $instance = ['key' => 'scalar'];

            $result = $this->merger->merge($shared, $instance);

            expect($result['key'])->toBe('scalar');
        });

        it('replaces scalar with array', function () {
            $shared = ['key' => 'scalar'];
            $instance = ['key' => ['nested' => 'value']];

            $result = $this->merger->merge($shared, $instance);

            expect($result['key'])->toBe(['nested' => 'value']);
        });

        it('handles numeric array keys', function () {
            $shared = ['items' => ['a', 'b', 'c']];
            $instance = ['items' => ['x', 'y']];

            $result = $this->merger->merge($shared, $instance);

            // Numeric arrays are merged by index, instance overrides
            expect($result['items'][0])->toBe('x');
            expect($result['items'][1])->toBe('y');
            expect($result['items'][2])->toBe('c');
        });
    });
});
