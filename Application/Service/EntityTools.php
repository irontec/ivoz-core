<?php

namespace Ivoz\Core\Application\Service;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\PessimisticLockException;
use Ivoz\Core\Application\DataTransferObjectInterface;
use Ivoz\Core\Application\Service\Assembler\DtoAssembler;
use Ivoz\Core\Domain\Model\EntityInterface;
use Ivoz\Core\Domain\Service\EntityPersisterInterface;
use Doctrine\ORM\UnitOfWork;

/**
 * Entity service facade
 * @author Mikel Madariaga <mikel@irontec.com>
 */
class EntityTools
{
    private $em;
    private $entityPersister;
    private $dtoAssembler;
    private $entityFromDto;
    private $entityUpdater;

    public function __construct(
        EntityManager $entityManager,
        EntityPersisterInterface $entityPersister,
        DtoAssembler $dtoAssembler,
        CreateEntityFromDto $createEntityFromDto,
        UpdateEntityFromDto $entityUpdater
    ) {
        $this->em = $entityManager;

        $this->entityPersister = $entityPersister;
        $this->dtoAssembler = $dtoAssembler;
        $this->entityFromDto = $createEntityFromDto;
        $this->entityUpdater = $entityUpdater;
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $fqdn full entity class name.
     *
     * @return \Doctrine\ORM\EntityRepository The repository class.
     */
    public function getRepository(string $fqdn)
    {
        return $this
            ->em
            ->getRepository($fqdn);
    }

    public function entityToDto(EntityInterface $entity): DataTransferObjectInterface
    {
        return $this
            ->dtoAssembler
            ->toDto($entity);
    }

    public function dtoToEntity(
        DataTransferObjectInterface $dto,
        EntityInterface $entity = null
    ): EntityInterface {

        if ($entity) {

            $this->entityUpdater->execute(
                $entity,
                $dto
            );

            return $entity;
        }

        return $this
            ->entityFromDto
            ->execute(
                $dto
            );
    }

    /**
     * lock entity or throw exception
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/transactions-and-concurrency.html#locking-support
     *
     * @param EntityInterface $entity
     * @param int $expectedVersion
     * @param int $lockMode
     *
     * @throws OptimisticLockException
     * @throws PessimisticLockException
     *
     * @return void
     */
    public function lock(EntityInterface $entity, int $expectedVersion, $lockMode = LockMode::OPTIMISTIC)
    {
        $this->em->lock($entity, $lockMode, $expectedVersion);
    }

    /**
     * @param EntityInterface $entity
     * @param boolean $dispatchImmediately
     * @return void
     */
    public function persist(EntityInterface $entity, $dispatchImmediately = false)
    {
        $this
            ->entityPersister
            ->persist(
                $entity,
                $dispatchImmediately
            );
    }

    /**
     * @param EntityInterface[] $entities
     */
    public function persistFromArray(array $entities)
    {
        $this
            ->entityPersister
            ->persistFromArray(
                $entities
            );
    }

    /**
     * @param DataTransferObjectInterface $dto
     * @param EntityInterface|null $entity
     * @param bool $dispatchImmediately
     * @return EntityInterface
     */
    public function persistDto(
        DataTransferObjectInterface &$dto,
        EntityInterface $entity = null,
        $dispatchImmediately = false
    ) {
        $entity = $this
            ->entityPersister
            ->persistDto($dto, $entity, $dispatchImmediately);

        // Resync dto
        $dto = $this->entityToDto($entity);

        return $entity;
    }

    public function updateEntityByDto(
        EntityInterface $entity,
        DataTransferObjectInterface $dto
    ): EntityInterface {
        $this->entityUpdater->execute(
            $entity,
            $dto
        );

        return $entity;
    }

    /**
     * @return void
     */
    public function dispatchQueuedOperations()
    {
        $this
            ->entityPersister
            ->dispatchQueued();
    }

    /**
     * @param EntityInterface $entity
     * @return void
     */
    public function remove(EntityInterface $entity)
    {
        $this
            ->entityPersister
            ->remove($entity);
    }

    /**
     * @param EntityInterface[] $entities
     * @return void
     */
    public function removeFromArray(array $entities)
    {
        $this
            ->entityPersister
            ->removeFromArray($entities);
    }

    public function isScheduledForRemoval(EntityInterface $entity): bool
    {
        $unitOfWork = $this->em->getUnitOfWork();
        $entityState = $unitOfWork->getEntityState($entity);

        return $entityState === UnitOfWork::STATE_REMOVED;
    }

    /**
     * Clears the EntityManager. All entities that are currently managed
     * by this EntityManager become detached.
     *
     * @param string|null $entityName if given, only entities of this type will get detached
     *
     * @return void
     */
    public function clear($entityName = null)
    {
        if (!$entityName) {
            $this->em->clear();
            return;
        }

        $unitOfWork = $this->em->getUnitOfWork();
        $identityMap = $unitOfWork->getIdentityMap();

        if (!array_key_exists($entityName, $identityMap)) {
            return;
        }

        foreach ($identityMap[$entityName] as $entity) {
            $this->em->detach($entity);
        }
    }

    /**
     * Clears the EntityManager. All entities that are currently managed
     * except the one given
     *
     * @param string $entityNameToSkip if given, only entities of this type will not get detached
     *
     * @return void
     */
    public function clearExcept($entityNameToSkip)
    {
        $unitOfWork = $this->em->getUnitOfWork();
        $identityMap = $unitOfWork->getIdentityMap();

        foreach ($identityMap as $fqdn => $entities) {
            if ($fqdn === $entityNameToSkip) {
                continue;
            }

            foreach ($entities as $entity) {
                $this->em->detach($entity);
            }
        }
    }
}
