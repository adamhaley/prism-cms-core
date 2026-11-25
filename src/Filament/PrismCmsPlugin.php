<?php

declare(strict_types=1);

namespace PrismCms\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use PrismCms\Filament\Resources\Pages\PageResource;

/**
 * Adds Prism CMS's resources to a Filament panel: `$panel->plugin(PrismCmsPlugin::make())`.
 */
class PrismCmsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'prism-cms';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PageResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
