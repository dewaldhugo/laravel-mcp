<?php

namespace OriginMain\LaravelMcp;

use Illuminate\Support\ServiceProvider;
use OriginMain\LaravelMcp\Commands\McpServeCommand;

class McpServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
