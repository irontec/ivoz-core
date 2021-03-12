<?php

namespace Ivoz\Core\Domain\Service;

use Ivoz\Core\Domain\Assert\Assertion;
use Ivoz\Core\Domain\Model\EntityInterface;

class CommonLifecycleServiceCollection implements LifecycleServiceCollectionInterface
{
    use LifecycleServiceCollectionTrait;

    /**
     * @return void
     */
    public function setServices(string $event, array $services)
    {
        $this->services[$event] = [];
        $isErrorHandler = ($event ===  LifecycleEventHandlerInterface::EVENT_ON_ERROR);

        foreach ($services as $service) {
            if ($isErrorHandler) {
                $this->addErrorhandler($service);

                continue;
            }

            $this->addService($event, $service);
        }
    }

    protected function addService(string $event, $service): void
    {
        Assertion::isInstanceOf($service, CommonLifecycleEventHandlerInterface::class);
        $this->services[$event][] = $service;
    }

    /**
     * @param EntityInterface $entity
     */
    public function execute(string $event, EntityInterface $entity)
    {
        foreach ($this->services[$event] as $service) {
            $service->handle($entity);
        }
    }
}
