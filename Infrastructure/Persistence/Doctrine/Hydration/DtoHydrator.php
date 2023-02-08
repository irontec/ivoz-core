<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration;

use Doctrine\DBAL\Result;
use Doctrine\ORM\Internal\Hydration\ArrayHydrator;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query\ResultSetMapping;
use Ivoz\Core\Domain\Model\EntityInterface;

class DtoHydrator extends ArrayHydrator
{
    protected $loadedEntities = [];

    /**
     * Initiates a row-by-row hydration.
     *
     * @deprecated
     *
     * @param Result $stmt
     * @param ResultSetMapping       $resultSetMapping
     * @psalm-param array<string, mixed> $hints
     *
     * @return IterableResult|array
     */
    public function hydrateAll($stmt, $resultSetMapping, array $hints = array())
    {
        $response = parent::hydrateAll(...func_get_args());

        return $this
            ->mapToDto(
                $response
            );
    }

    public function hydrateRow()
    {
        $response = parent::hydrateRow();

        if (!is_array($response)) {
            return $response;
        }

        return $this
            ->mapToDto(
                $response
            );
    }

    private function mapToDto(array $rows): array
    {
        $aliasMap = $this->_rsm->getAliasMap();

        /** @var EntityInterface $entityClass */
        $entityClass = current($aliasMap);

        $response = [];
        foreach ($rows as $row) {
            $dto = $entityClass::createDto();
            foreach ($row as $fld => $value) {

                $normalizedFldSegments = array_map(
                    'ucfirst',
                    explode('.', $fld)
                );

                $setter = 'set' . implode('', $normalizedFldSegments);
                $dto->{$setter}($value);
            }
            $response[] = $dto;
        }

        return $response;
    }
}
