<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator as DoctrineObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Events as IvozEvents;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class ObjectHydrator extends DoctrineObjectHydrator
{
    protected $loadedEntities = [];

    private static int $recursionLevel = 0;

    /**
     * {@inheritdoc}
     */
    public function hydrateAll($stmt, $resultSetMapping, array $hints = array())
    {
        $evm = $this->_em->getEventManager();
        $evm->addEventListener(
            [Events::postLoad],
            $this
        );

        self::$recursionLevel++;
        $response = parent::hydrateAll(...func_get_args());

        $evm->removeEventListener(
            [Events::postLoad],
            $this
        );

        $mustTriggerEvents =
            !empty($response)
            && is_object($response[0]);

        if ($mustTriggerEvents) {
            /** @var string $reponseClass */
            $reponseClass = get_class($response[0]);
            $foreignEntities = array_filter(
                $this->loadedEntities,
                function ($entity) use ($reponseClass) {
                    return !($entity instanceof $reponseClass);
                }
            );

            $this->triggerHydratorCompleteEvent(
                $foreignEntities
            );
            $this->triggerHydratorCompleteEvent(
                $response
            );
        }

        $this->loadedEntities = [];
        return $response;
    }

    protected function cleanup()
    {
        self::$recursionLevel--;
        if (self::$recursionLevel === 0) {
            parent::cleanup();
        }
    }

    public function hydrateRow()
    {
        $response = parent::hydrateRow();
        if (is_array($response)) {
            $this->triggerHydratorCompleteEvent($response);
        }

        return $response;
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $this->loadedEntities[] = $args->getObject();
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
