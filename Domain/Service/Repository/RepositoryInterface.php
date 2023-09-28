<?php

namespace Ivoz\Core\Domain\Service\Repository;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\Model\EntityInterface;

/**
 * @template EntityT of EntityInterface
 * @template DtoT of DataTransferObjectInterface
 * @extends ObjectRepository<EntityT>
 * @extends Selectable<int, EntityT>
 */
interface RepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @param DtoT $dto
     * @param EntityT|null $entity
     * @return EntityT
     */
    public function persistDto(DataTransferObjectInterface $dto, EntityInterface $entity = null, $dispatchImmediately = false): EntityInterface;

    /**
     * @param EntityT $entity
     */
    public function persist(EntityInterface $entity, bool $dispatchImmediately = false): void;

    /**
     * @param EntityT $entity
     */
    public function remove(EntityInterface $entity): void;

    /**
     * @param EntityT[] $entities
     */
    public function removeFromArray(array $entities): void;

    public function dispatchQueued(): void;
}