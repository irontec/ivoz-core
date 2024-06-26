<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\Model\EntityInterface;
use Ivoz\Core\Domain\Service\EntityPersisterInterface;
use Ivoz\Core\Domain\Service\Repository\RepositoryInterface;

/**
 * CompanyDoctrineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @template T of EntityInterface
 * @template DtoT of DataTransferObjectInterface
 * @extends ServiceEntityRepository<T>
 * @implements RepositoryInterface<T, DtoT>
 */
class DoctrineRepository extends ServiceEntityRepository implements RepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        private EntityPersisterInterface $entityPersister,
    ) {
        parent::__construct(
            $registry,
            $entityClass
        );
    }

    /**
     * @param DtoT $dto
     * @param T|null $entity
     * @return T
     */
    public function persistDto(DataTransferObjectInterface $dto, EntityInterface $entity = null, $dispatchImmediately = false): EntityInterface
    {
        return
            $this
                ->entityPersister
                ->persistDto(
                    $dto,
                    $entity,
                    $dispatchImmediately
                );
    }

    /**
     * @param T $entity
     */
    public function persist(EntityInterface $entity, bool $dispatchImmediately = false): void
    {
        $this
            ->entityPersister
            ->persist(
                $entity,
                $dispatchImmediately
            );
    }

    /**
     * @param T $entity
     */
    public function remove(EntityInterface $entity): void
    {
        $this
            ->entityPersister
            ->remove(
                $entity
            );
    }

    /**
     * @param T[] $entities
     */
    public function removeFromArray(array $entities): void
    {
        $this
            ->entityPersister
            ->removeFromArray(
                $entities
            );
    }

    public function dispatchQueued(): void
    {
        $this->entityPersister->dispatchQueued();
    }
}