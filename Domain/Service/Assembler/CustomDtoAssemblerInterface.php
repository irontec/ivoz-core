<?php

namespace Ivoz\Core\Domain\Service\Assembler;

use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\Model\EntityInterface;

interface CustomDtoAssemblerInterface
{
    public function toDto(EntityInterface $entity, int $depth = 0, string $context = null): DataTransferObjectInterface;
}
