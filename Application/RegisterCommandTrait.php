<?php

namespace Ivoz\Core\Application;

use Ivoz\Core\Application\Event\CommandWasExecuted;
use Ivoz\Core\Domain\Service\DomainEventPublisher;

trait RegisterCommandTrait
{
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
