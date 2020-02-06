<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator as DoctrineObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Events;

class ObjectHydrator extends DoctrineObjectHydrator
{
    /**
     * {@inheritdoc}
     */
    public function hydrateAll($stmt, $resultSetMapping, array $hints = array())
    {
        $response = parent::hydrateAll(...func_get_args());

        if (empty($response)) {
            return $response;
        }

        $evm = $this->_em->getEventManager();
        $evm->dispatchEvent(
            Events::onHydratorComplete
        );

        return $response;
    }
}
