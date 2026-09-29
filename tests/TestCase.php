<?php

declare(strict_types=1);

namespace Notideus\Laravel\Tests;

use Notideus\Laravel\NotideusServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @param list<class-string> $providers */
    protected function getPackageProviders($app): array
    {
        return [NotideusServiceProvider::class];
    }
}
