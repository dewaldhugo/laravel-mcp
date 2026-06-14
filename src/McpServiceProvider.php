<?php

namespace OriginMain\LaravelMcp;

use Illuminate\Support\ServiceProvider;
use OriginMain\LaravelMcp\Commands\McpServeCommand;
use OriginMain\LaravelMcp\Services\ToolRegistry;
use OriginMain\LaravelMcp\Services\Tools\ListRoutes;

class McpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();
            
            // Register tools for compilation here
            $registry->register(new ListRoutes());
            
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
