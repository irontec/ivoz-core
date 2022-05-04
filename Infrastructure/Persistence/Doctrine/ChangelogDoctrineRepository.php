<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ivoz\Core\Domain\Model\Changelog\Changelog;
use Ivoz\Core\Domain\Model\Changelog\ChangelogRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ChangelogDoctrineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ChangelogDoctrineRepository extends ServiceEntityRepository implements ChangelogRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Changelog::class);
    }
}
