<?php

namespace Ivoz\Core\Infrastructure\Persistence\Filesystem;

class TempFileHelper
{
    /**
     * @param string $content
     * @param bool $rewind
     * @return resource|false
     */
    public function createWithContent(string $content, $rewind = true)
    {
        $tmpFile = tmpfile();
        fwrite(
            $tmpFile,
            $content
        );

        if ($rewind) {
            fseek($tmpFile, 0);
        }

        return $tmpFile;
    }

    /**
     * @param resource $tmpFile
     * @param string $content
     * @return resource
     */
    public function appendContent($tmpFile, string $content)
    {
        fwrite(
            $tmpFile,
            $content
        );

        return $tmpFile;
    }
}
