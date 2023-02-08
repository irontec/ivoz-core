<?php

namespace Ivoz\Core\Domain;

use Ivoz\Core\Domain\Event\CommandWasExecuted;
use Ivoz\Core\Domain\Service\DomainEventPublisher;

trait RegisterCommandTrait
{
    /**
     * @var DomainEventPublisher
     */
    private $eventPublisher;

    /**
     * @var RequestId
     */
    private $requestId;

    private function registerCommand(string $service, string $method, $aguments = [], $agent = [])
    {
        $event = new CommandWasExecuted(
            $this->requestId->toString(),
            $service,
            $method,
            $aguments,
            $agent
        );

        $this->eventPublisher->publish($event);
    }
}
