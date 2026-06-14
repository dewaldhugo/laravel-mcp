<?php

namespace OriginMain\LaravelMcp\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use OriginMain\LaravelMcp\McpServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
        ];
    }
}
