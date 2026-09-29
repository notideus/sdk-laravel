<?php

declare(strict_types=1);

namespace Notideus\Laravel\Tests;

use Notideus\Laravel\Facades\Notideus;
use Notideus\NotideusClient;

final class ServiceProviderTest extends TestCase
{
    public function testSingletonResolvesClientFromConfig(): void
    {
        config()->set('notideus.api_key', 'nt_live_cfg');
        config()->set('notideus.timeout', 12.5);

        $client = app('notideus');
        self::assertInstanceOf(NotideusClient::class, $client);
        self::assertSame($client, app('notideus')); // singleton
        self::assertInstanceOf(NotideusClient::class, app(\Notideus\NotideusClient::class)); // alias
    }

    public function testFacadeResolvesTheSingleton(): void
    {
        config()->set('notideus.api_key', 'nt_live_cfg');

        self::assertInstanceOf(NotideusClient::class, Notideus::getFacadeRoot());
    }

    public function testConfigIsMergedWithDefaults(): void
    {
        self::assertSame('https://api.notideus.io', config('notideus.base_url'));
        self::assertSame(30.0, (float) config('notideus.timeout'));
        self::assertSame(2, (int) config('notideus.max_retries'));
    }
}
