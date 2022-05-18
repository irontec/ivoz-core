<?php

namespace Ivoz\Core\Domain\Model\Mailer;

class Attachment
{
    public function __construct(
        private string $file,
        private string $filename,
        private string $mimetype
    ) {
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getMimetype(): string
    {
        return $this->mimetype;
    }


}
