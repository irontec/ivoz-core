<?php

namespace Ivoz\Core\Domain\Model\Mailer;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class Message
{
    /**
     * @var string
     */
    protected $body;

    /**
     * @var string
     */
    protected $bodyType;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string
     */
    protected $fromAddress;

    /**
     * @var string
     */
    protected $fromName;

    /**
     * @var string
     */
    protected $toAddress;

    /**
     * @var Attachment[]
     */
    protected $attachments = [];

    /**
     * @internal
     */
    public function toEmail(): Email
    {
        $message = new Email();

        if ($this->getBodyType() === 'text/plain') {
            $message
                ->text($this->getBody());
        } else {
            $message
                ->html($this->getBody());
        }

        $message
            ->subject($this->getSubject())
            ->from(
                new Address(
                    $this->getFromAddress(),
                    $this->getFromName()
                )
            )
            ->to($this->getToAddress());

        foreach ($this->attachments as $attachment) {

            if ($attachment->getType() === Attachment::TYPE_FILEPATH) {
                $message->attachFromPath(
                    $attachment->getFile(),
                    $attachment->getFilename(),
                    $attachment->getMimetype(),
                );
            } else {

                $stream = fopen('php://memory', 'rw+');
                fwrite($stream, $attachment->getFile());
                rewind($stream);

                $message->attach(
                    $stream,
                    $attachment->getFilename(),
                    $attachment->getMimetype(),
                );
            }
        }

        return $message;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getBodyType(): string
    {
        return $this->bodyType;
    }

    public function setBody(string $body, string $bodyType = 'text/plain'): Message
    {
        $this->body = $body;
        $this->bodyType = $bodyType;
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): Message
    {
        $this->subject = $subject;
        return $this;
    }

    public function getFromAddress(): string
    {
        return $this->fromAddress;
    }

    public function setFromAddress(string $fromAddress): Message
    {
        $this->fromAddress = $fromAddress;
        return $this;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function setFromName(string $fromName): Message
    {
        $this->fromName = $fromName;
        return $this;
    }

    public function getToAddress(): string
    {
        return $this->toAddress;
    }

    public function setToAddress(string $toAddress): Message
    {
        $this->toAddress = $toAddress;
        return $this;
    }

    public function setAttachment($file, $filename, $mimetype, $type = Attachment::TYPE_FILEPATH): Message
    {
        $this->attachments = [];
        $this->addAttachment(
            $file,
            $filename,
            $mimetype,
            $type
        );

        return $this;
    }

    public function addAttachment($file, $filename, $mimetype, $type = Attachment::TYPE_FILEPATH): Message
    {
        $this->attachments[] = new Attachment(
            $file,
            $filename,
            $mimetype,
            $type
        );

        return $this;
    }
}
