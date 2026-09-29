# laravel-notideus

Official Laravel integration for the [Notideus](https://notideus.io) email
API. MIT licensed.

Bridges the [`notideus/notideus-php`](https://github.com/notideus/sdk-php) SDK
into Laravel: a configurable `NotideusClient` singleton, a `Notideus` facade,
a `notideus` mail transport (so any `Mail::send()` or Mailable rides the API),
and a Notifications channel.

Requires PHP 8.2+ and Laravel 10/11/12. The service provider and facade alias
are auto-discovered — nothing to register in `config/app.php`.

## Install

```bash
composer require notideus/laravel-notideus
```

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=notideus-config
```

| Env var               | Config key     | Default                   | Notes                                          |
| --------------------- | -------------- | ------------------------- | ---------------------------------------------- |
| `NOTIDEUS_API_KEY`    | `api_key`      | —                         | your `nt_live_…` key                            |
| `NOTIDEUS_BASE_URL`   | `base_url`     | `https://api.notideus.io` | override for self-hosting                       |
| `NOTIDEUS_TIMEOUT`    | `timeout`      | `30.0`                    | request timeout (seconds)                       |
| `NOTIDEUS_MAX_RETRIES`| `max_retries`  | `2`                       | retries for network errors, 5xx and 429         |
| `NOTIDEUS_FROM_ADDRESS`| `from_address`| —                         | reserved — not consumed yet (see below)         |
| `NOTIDEUS_FROM_NAME`  | `from_name`    | —                         | reserved — not consumed yet (see below)         |

The client is registered as a singleton — `app('notideus')`, also aliased to
`Notideus\NotideusClient` — built from this config.

`NOTIDEUS_FROM_ADDRESS` and `NOTIDEUS_FROM_NAME` are reserved for a future
release: nothing reads them today (the provider only consumes `api_key`,
`base_url`, `timeout` and `max_retries`). For an app-level default sender,
use Laravel's `mail.from` config or pass `from` explicitly per send.

## Usage

All SDK resources are reachable through the `Notideus` facade:

```php
use Notideus\Laravel\Facades\Notideus;

$email = Notideus::emails()->send([
    'from' => 'Acme Inc <noreply@acme.com>',
    'to' => ['jane@example.com'],
    'subject' => 'Welcome',
    'html' => '<p>Hi {{name}}</p>',
    'variables' => ['name' => 'Jane'],
    'tags' => ['welcome'],
    'idempotency_key' => 'req-123',
]);
```

`contacts()`, `whatsapp()`, `unsubscribe()` and `plans()` work the same way.
Params and responses use snake_case exactly like the API — see the
[API reference](https://notideus.io/docs) and the `notideus/notideus-php`
README for the full resource surface.

## Mail

Point the default mailer at the `notideus` transport:

```dotenv
MAIL_MAILER=notideus
```

```php
// config/mail.php
'mailers' => [
    'notideus' => [
        'transport' => 'notideus',
    ],
],
```

Every `Mail::send()`, Mailable, and `mail`-channeled notification now goes
through the API. The transport maps the message's from, to, subject and HTML
(plus the text alternative and reply-to when set) onto `POST /v1/emails`, and
derives an `idempotency_key` from the content so a retried send never
double-sends.

## Notifications

Route a notification through the `NotideusChannel` and define `toNotideus()`.
Return the channel's class name from `via()` — the provider registers no named
`notideus` notification driver:

```php
use Illuminate\Notifications\Notification;
use Notideus\Laravel\Notifications\NotideusChannel;

final class WelcomeNotification extends Notification
{
    /** @return array<string> */
    public function via(mixed $notifiable): array
    {
        return [NotideusChannel::class];
    }

    /** @return array<string, mixed> */
    public function toNotideus(mixed $notifiable): array
    {
        return [
            'from' => 'Acme <noreply@acme.com>',
            'subject' => 'Welcome',
            'html' => '<p>Hi Jane</p>',
        ];
    }
}
```

The recipient comes from `routeNotificationFor('notideus')`, then
`routeNotificationFor('mail')`, then the notifiable's `$email` property, and
is sent as `to` unless `toNotideus()` sets it itself. The returned array takes
the same snake_case params as `emails()->send()`.

## Error handling

Request-level failures throw `Notideus\NotideusException`:

```php
use Notideus\NotideusException;

try {
    Notideus::emails()->send([…]);
} catch (NotideusException $e) {
    if ($e->getErrorCode() === 'from_domain_not_verified') { … }
    if ($e->getErrorCode() === 'rate_limited') {
        $e->getRetryAfter(); // seconds to wait
    }
    $e->getStatus(); // HTTP status
}
```

## Development

```bash
composer install
composer test    # phpunit via orchestra/testbench — mocked Guzzle, no infra needed
composer lint    # phpcs
composer stan    # phpstan analyse
```

**Pre-Packagist note.** `composer.json` currently pins the core SDK through a
temporary vcs repository (`notideus/notideus-php: dev-main as 1.0.0` pointing
at the GitHub repo), so `composer install` resolves it straight from source.
For fully offline work, swap the vcs entry for a `path` repository pointing at
a sibling `sdk-php` checkout. After `notideus/notideus-php` is submitted to
Packagist, clean this up: drop the `repositories` entry and require `^1.0`.

## Releasing

1. Update `version` in `composer.json` and merge to `main`.
2. Create a GitHub Release with tag `v<version>` — it must match
   `composer.json`, the `publish` workflow verifies this and fails otherwise.
3. The `publish` workflow runs the full gate (`composer validate`, `install`,
   `lint`, `stan`, `test`). There is no publish step and **no secrets at
   all**: Packagist auto-updates the package from the release tag.
4. **One-time setup**: submit the repository on
   [packagist.org](https://packagist.org) (GitHub hook or manual "Update"
   enables auto-sync from tags). Unlike npm, Packagist has no
   registry-existence prerequisite — the very first release can go straight
   through the workflow.
