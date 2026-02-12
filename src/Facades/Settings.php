<?php

declare(strict_types=1);

namespace Shelfwood\SettingsYaml\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Shelfwood\SettingsYaml\Settings load(string $filename, string $instanceId)
 * @method static \Shelfwood\SettingsYaml\Settings theme(string $instanceId)
 * @method static \Shelfwood\SettingsYaml\Settings main(string $instanceId)
 * @method static \Shelfwood\SettingsYaml\Settings properties(string $instanceId)
 * @method static \Shelfwood\SettingsYaml\Settings booking(string $instanceId)
 * @method static void clearCache(?string $instanceId = null, ?array $files = null)
 *
 * @see \Shelfwood\SettingsYaml\Settings
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Shelfwood\SettingsYaml\Settings::class;
    }
}
