<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration;

use Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator as DoctrineSimpleObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Events as IvozEvents;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class SimpleObjectHydrator extends DoctrineSimpleObjectHydrator
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

        $this->triggerHydratorCompleteEvent(
            $response
        );

        return $response;
    }

    public function hydrateRow()
    {
        $response = parent::hydrateRow();
        $this->triggerHydratorCompleteEvent(
            $response
        );

        return $response;
    }

    protected function triggerHydratorCompleteEvent(array $entities)
    {
        $evm = $this->_em->getEventManager();
        foreach ($entities as $entity) {
            $evm->dispatchEvent(
                IvozEvents::onHydratorComplete,
                new LifecycleEventArgs(
                    $entity,
                    $this->_em
                )
            );
        }
    }
}
