<?php

use Shelfwood\SettingsYaml\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->group('unit')->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeSettings', function () {
    return $this->toBeInstanceOf(\Shelfwood\SettingsYaml\Settings::class);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createSettingsFile(string $path, array $frontmatter, string $content = ''): void
{
    $yaml = \Symfony\Component\Yaml\Yaml::dump($frontmatter);
    $fullContent = "---\n{$yaml}---\n\n{$content}";

    $directory = dirname($path);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    file_put_contents($path, $fullContent);
}

function cleanupSettingsFiles(string $basePath): void
{
    if (is_dir($basePath)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($basePath);
    }
}
