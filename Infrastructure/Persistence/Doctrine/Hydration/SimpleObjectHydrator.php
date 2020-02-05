<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration;

use Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator as DoctrineSimpleObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Events;

class SimpleObjectHydrator extends DoctrineSimpleObjectHydrator
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
