<?php

declare(strict_types=1);

namespace PrismCms;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use PrismCms\Body\TokenRegistry;
use PrismCms\Policies\PagePolicy;

class PrismCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/prism.php', 'prism');
        $this->app->singleton(TokenRegistry::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/prism.php' => config_path('prism.php')], 'prism-config');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'prism');

        // A site registering its own policy for its Page model in its own provider wins: app
        // providers boot after package providers.
        Gate::policy(config('prism.models.page'), PagePolicy::class);

        if (config('prism.routes')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }
}
