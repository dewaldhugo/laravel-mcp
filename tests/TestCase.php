<?php

namespace DewaldHugo\LaravelMcp\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use DewaldHugo\LaravelMcp\McpServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [McpServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mcp.enabled', true);
        // Ensure cache persists within the test process memory
        $app['config']->set('cache.default', 'array');
    }
}