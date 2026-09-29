<?php

declare(strict_types=1);

namespace Notideus\Laravel\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notification;
use Notideus\Laravel\Notifications\NotideusChannel;
use Notideus\NotideusClient;
use PHPUnit\Framework\TestCase;

final class NotideusChannelTest extends TestCase
{
    public function testSendsViaToNotideusParams(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(201, [], '{"id":"n-1"}')]));
        $stack->push(Middleware::history($history));

        $channel = new NotideusChannel(new NotideusClient('k', ['handler' => $stack]));
        $notifiable = new class {
            public string $email = 'jane@example.com';
        };
        $notification = new class extends Notification {
            /** @return array<string, mixed> */
            public function toNotideus(mixed $notifiable): array
            {
                return [
                    'from' => 'Acme <noreply@acme.com>',
                    'subject' => 'Hello',
                    'html' => '<p>Hi</p>',
                ];
            }
        };

        $channel->send($notifiable, $notification);

        /** @var array{to: list<string>, subject: string} $body */
        $body = json_decode((string) $history[0]['request']->getBody(), true);
        self::assertSame(['jane@example.com'], $body['to']);
        self::assertSame('Hello', $body['subject']);
    }

    public function testSkipsWhenNoRecipient(): void
    {
        $stack = HandlerStack::create(new MockHandler([])); // would throw if a request were made
        $channel = new NotideusChannel(new NotideusClient('k', ['handler' => $stack]));

        $notification = new class extends Notification {
            /** @return array<string, mixed> */
            public function toNotideus(mixed $notifiable): array
            {
                return ['subject' => 'x', 'html' => 'y'];
            }
        };

        $channel->send(new class {
        }, $notification);

        self::assertTrue(true); // no request made, no exception
    }
}
