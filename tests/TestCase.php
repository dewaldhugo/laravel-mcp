<?php

namespace DewaldHugo\LaravelMcp\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use DewaldHugo\LaravelMcp\McpServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
        ];
    }
}
