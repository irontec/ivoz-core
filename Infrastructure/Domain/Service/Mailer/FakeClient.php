<?php

namespace Ivoz\Core\Infrastructure\Domain\Service\Mailer;

use Ivoz\Core\Domain\Model\Mailer\Message;
use Ivoz\Core\Domain\Service\MailerClientInterface;

class FakeClient implements MailerClientInterface
{
    public function send(Message $message): void
    {
    }
}
