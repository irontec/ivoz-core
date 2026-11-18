<?php

namespace Ivoz\Core\Domain\Service;

use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\Model\EntityInterface;

interface EntityPersisterInterface
{
    public function persistDto(DataTransferObjectInterface $dto, ?EntityInterface $entity = null, bool $dispatchImmediately = false): EntityInterface;

    /**
     * @param EntityInterface $entity
     * @param boolean $dispatchImmediately
     * @return void
     */
    public function persist(EntityInterface $entity, $dispatchImmediately = false);

    /**
     * @param EntityInterface $entity
     * @return void
     */
    public function remove(EntityInterface $entity);

    /**
     * @param EntityInterface[] $entities
     * @return void
     */
    public function removeFromArray(array $entities);

    /**
     * @return void
     */
    public function dispatchQueued();
}
