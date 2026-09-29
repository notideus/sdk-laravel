<?php

declare(strict_types=1);

namespace Notideus\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Notideus\Resources\EmailsResource emails()
 * @method static \Notideus\Resources\ContactsResource contacts()
 * @method static \Notideus\Resources\WhatsAppResource whatsapp()
 * @method static \Notideus\Resources\UnsubscribeResource unsubscribe()
 * @method static \Notideus\Resources\PlansResource plans()
 *
 * @see \Notideus\NotideusClient
 */
final class Notideus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notideus';
    }
}
