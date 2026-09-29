<?php

declare(strict_types=1);

namespace Notideus\Laravel\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Notideus\Laravel\Mail\NotideusTransport;
use Notideus\NotideusClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class NotideusTransportTest extends TestCase
{
    public function testSendsEmailThroughTheApi(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(201, [], '{"id":"mail-1"}'),
        ]));
        $stack->push(Middleware::history($history));

        $client = new NotideusClient('k', ['handler' => $stack]);
        $transport = new NotideusTransport($client);

        $email = (new Email())
            ->from(new Address('noreply@acme.com', 'Acme Inc'))
            ->to(new Address('jane@example.com'))
            ->subject('Welcome')
            ->html('<p>Hi Jane</p>');

        $transport->send($email);

        self::assertCount(1, $history);
        $request = $history[0]['request'];
        self::assertSame('/v1/emails', $request->getUri()->getPath());
        /** @var array{from: string, to: list<string>, subject: string, html: string, idempotency_key: string} $body */
        $body = json_decode((string) $request->getBody(), true);
        self::assertSame('Acme Inc <noreply@acme.com>', $body['from']);
        self::assertSame(['jane@example.com'], $body['to']);
        self::assertSame('Welcome', $body['subject']);
        self::assertSame('<p>Hi Jane</p>', $body['html']);
        self::assertArrayHasKey('idempotency_key', $body);
        self::assertStringStartsWith('mail-', $body['idempotency_key']);
    }

    public function testIncludesTextAlternativeWhenSet(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(201, [], '{"id":"m"}')]));
        $stack->push(Middleware::history($history));

        $client = new NotideusClient('k', ['handler' => $stack]);
        $transport = new NotideusTransport($client);

        $email = (new Email())
            ->from('noreply@acme.com')
            ->to('jane@example.com')
            ->subject('Welcome')
            ->text('Hi Jane')
            ->html('<p>Hi Jane</p>');

        $transport->send($email);

        /** @var array{text: string} $body */
        $body = json_decode((string) $history[0]['request']->getBody(), true);
        self::assertSame('Hi Jane', $body['text']);
    }
}
