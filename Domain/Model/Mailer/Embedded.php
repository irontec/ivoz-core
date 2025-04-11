<?php

namespace Ivoz\Core\Domain\Model\Mailer;

use Ivoz\Core\Domain\Assert\Assertion;

class Embedded
{
    public const TYPE_FILEPATH = 'file';
    public const TYPE_CONTENT = 'content';

    public const ALLOWED_TYPES = [
        self::TYPE_FILEPATH,
        self::TYPE_CONTENT,
    ];

    private string $type;

    public function __construct(
        private string  $filePath,
        private ?string $name = null,
        private ?string $mimetype = null,
        string          $type = self::TYPE_FILEPATH
    ) {
        Assertion::choice(
            $type,
            self::ALLOWED_TYPES,
            'type "%s" is not an element of the valid values: %s'
        );

        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFile(): string
    {
        return $this->filePath;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMimetype(): ?string
    {
        return $this->mimetype;
    }


}
