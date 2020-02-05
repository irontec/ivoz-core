<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator as DoctrineObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Events;

class ObjectHydrator extends DoctrineObjectHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function cleanup()
    {
        parent::cleanup();

        $evm = $this->_em->getEventManager();
        $evm->dispatchEvent(
            Events::onHydratorComplete
        );
    }
}
