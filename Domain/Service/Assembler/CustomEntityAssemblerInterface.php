<?php

namespace Ivoz\Core\Domain\Service\Assembler;

use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\ForeignKeyTransformerInterface;
use Ivoz\Core\Domain\Model\EntityInterface;

interface CustomEntityAssemblerInterface
{
    public function fromDto(
        DataTransferObjectInterface $dto,
        EntityInterface $entity,
        ForeignKeyTransformerInterface $fkTransformer
    );
}
