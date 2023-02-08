<?php

namespace Ivoz\Core\Domain\Event;

use Ivoz\Core\Domain\Event\DomainEventInterface;

interface CommandEventInterface extends DomainEventInterface
{
    public function __construct(
        string $requestId,
        string $service,
        string $method,
        array $arguments,
        array $agent
    );

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getRequestId();

    /**
     * @return string
     */
    public function getService();

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @return array
     */
    public function getArguments();

    /**
     * @return array
     */
    public function getAgent();
}
