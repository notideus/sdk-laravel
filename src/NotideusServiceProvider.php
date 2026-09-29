<?php

declare(strict_types=1);

namespace Notideus\Laravel;

use Illuminate\Support\ServiceProvider;
use Notideus\NotideusClient;

final class NotideusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notideus.php', 'notideus');

        $this->app->singleton('notideus', function ($app): NotideusClient {
            /** @var array<string, mixed> $config */
            $config = $app['config']->get('notideus', []);

            return new NotideusClient(
                $config['api_key'] ?? null,
                [
                    'base_url' => $config['base_url'] ?? 'https://api.notideus.io',
                    'timeout' => (float) ($config['timeout'] ?? 30.0),
                    'max_retries' => (int) ($config['max_retries'] ?? 2),
                ]
            );
        });
        $this->app->alias('notideus', NotideusClient::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/notideus.php' => config_path('notideus.php'),
        ], 'notideus-config');
    }
}
