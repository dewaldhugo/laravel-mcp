<?php

namespace OriginMain\LaravelMcp;

use Illuminate\Support\ServiceProvider;
use OriginMain\LaravelMcp\Commands\McpServeCommand;
use OriginMain\LaravelMcp\Services\ToolRegistry;
use OriginMain\LaravelMcp\Services\Tools\ListRoutes;
use OriginMain\LaravelMcp\Services\Tools\ReadModelSchema;
use OriginMain\LaravelMcp\Services\Tools\RunSafeArtisan;

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
