<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Traits;

use Doctrine\ORM\QueryBuilder;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Model\Helper\CriteriaHelper;

trait GetGeneratorByConditionsTrait
{
    public function getGeneratorByConditions(array $conditions, int $batchSize, ?array $order = null): \Generator
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('self');
        $qb->addCriteria(CriteriaHelper::fromArray($conditions));

        if ($order) {
            $qb->orderBy(...$order);
        }

        $currentPage = 1;
        $continue =  true;
        while ($continue) {
            $qb
                ->setMaxResults($batchSize)
                ->setFirstResult(($currentPage -1) * $batchSize);

            $query = $qb->getQuery();
            $results = $query->getResult();
            $continue = count($results) === $batchSize;
            $currentPage++;

            yield $results;
        }
    }
}
