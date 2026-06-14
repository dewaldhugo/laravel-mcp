<?php

namespace DewaldHugo\LaravelMcp;

use Illuminate\Support\ServiceProvider;
use DewaldHugo\LaravelMcp\Commands\McpServeCommand;
use DewaldHugo\LaravelMcp\Services\ToolRegistry;
use DewaldHugo\LaravelMcp\Services\Tools\ListRoutes;
use DewaldHugo\LaravelMcp\Services\Tools\ReadModelSchema;
use DewaldHugo\LaravelMcp\Services\Tools\RunSafeArtisan;

class McpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();
            
            $registry->register(new ListRoutes());
            $registry->register(new ReadModelSchema());
            $registry->register(new RunSafeArtisan());
            
            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                McpServeCommand::class,
            ]);
        }
    }
}
