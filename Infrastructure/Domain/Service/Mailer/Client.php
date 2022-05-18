<?php

namespace Ivoz\Core\Infrastructure\Domain\Service\Mailer;

use Ivoz\Core\Domain\Model\Mailer\Message;
use Ivoz\Core\Domain\Service\MailerClientInterface;
use Symfony\Component\Mailer\MailerInterface;

class Client implements MailerClientInterface
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    /**
     * @param Message $message
     * @return void
     */
    public function send(Message $message): void
    {
        $this->mailer
            ->send(
                $message->toEmail()
            );
    }
}
