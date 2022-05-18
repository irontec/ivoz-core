<?php

namespace Ivoz\Core\Domain\Service;

use Ivoz\Core\Domain\Model\Mailer\Message;

interface MailerClientInterface
{
    public function send(Message $message): void;
}
