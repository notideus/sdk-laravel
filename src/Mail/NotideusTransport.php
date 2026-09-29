<?php

declare(strict_types=1);

namespace Notideus\Laravel\Mail;

use Notideus\NotideusClient;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class NotideusTransport extends AbstractTransport
{
    public function __construct(private readonly NotideusClient $notideus)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();
        if (!$email instanceof Email) {
            throw new \UnexpectedValueException('The notideus transport only supports Symfony Email instances.');
        }

        /** @var list<Address> $from */
        $from = $email->getFrom();
        /** @var list<Address> $to */
        $to = $email->getTo();
        $recipients = array_map(static fn (Address $a): string => $a->getAddress(), $to);
        $sender = $from[0];
        $fromHeader = $sender->getName() !== ''
            ? $sender->getName() . ' <' . $sender->getAddress() . '>'
            : $sender->getAddress();

        $params = [
            'from' => $fromHeader,
            'to' => $recipients,
            'subject' => $email->getSubject() ?? '',
            'html' => $email->getHtmlBody() ?? '',
            'idempotency_key' => 'mail-' . hash(
                'sha256',
                ($email->getHtmlBody() ?? '') . '|' . implode(',', $recipients) . '|' . ($email->getSubject() ?? ''),
            ),
        ];

        if (($text = $email->getTextBody()) !== null) {
            $params['text'] = $text;
        }
        /** @var list<Address> $replyTo */
        $replyTo = $email->getReplyTo();
        if ($replyTo !== []) {
            $params['reply_to'] = $replyTo[0]->getAddress();
        }

        $this->notideus->emails()->send($params);
    }

    public function __toString(): string
    {
        return 'notideus';
    }
}
