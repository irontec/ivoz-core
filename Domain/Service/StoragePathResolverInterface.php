<?php

namespace Ivoz\Core\Domain\Service;

use Ivoz\Core\Domain\Model\EntityInterface;

interface StoragePathResolverInterface
{
    /**
     * StoragePathResolverInterface constructor.
     * @param string $localStoragePath
     * @param string $basePath
     */
    public function __construct(string $localStoragePath, string $basePath);

    public function setOriginalFileName(?string $originalFileName = null): void;

    /**
     * @param EntityInterface $entity
     * @return string
     */
    public function getFilePath(EntityInterface $entity)/* @todo : ?string */;
}
