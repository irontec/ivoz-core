<?php

namespace Ivoz\Core\Domain\Service;

use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\Service\Assembler\EntityAssembler;
use Ivoz\Core\Domain\Model\EntityInterface;

/**
 * @internal
 */
class UpdateEntityFromDto
{
    /**
     * @var EntityAssembler
     */
    private $entityAssembler;

    public function __construct(
        EntityAssembler $entityAssembler
    ) {
        $this->entityAssembler = $entityAssembler;
    }

    /**
     * @return void
     */
    public function execute(EntityInterface $entity, DataTransferObjectInterface $dto)
    {
        //Ensure that we don't propagate applied changes
        $dto = clone $dto;

        $this->entityAssembler->updateFromDto(
            $dto,
            $entity
        );
    }
}
