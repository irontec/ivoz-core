<?php

namespace Ivoz\Core\Domain\Service;

interface FileContainerInterface
{
    const DOWNLOADABLE_FILE = 1;
    const UPDALOADABLE_FILE = 2;

    const FILE_OBJECT_FILTERS = [
        self::DOWNLOADABLE_FILE,
        self::UPDALOADABLE_FILE
    ];

    public function addTmpFile(string $fldName, TempFile $file);

    /**
     * @throws \Exception
     */
    public function removeTmpFile(TempFile $file);

    /**
     * @return array
     */
    public function getFileObjects(int $filter = null);


    /**
     * @return \Ivoz\Core\Domain\Service\TempFile[]
     */
    public function getTempFiles();
}
