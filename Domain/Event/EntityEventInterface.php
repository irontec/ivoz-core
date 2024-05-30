<?php

namespace Ivoz\Core\Domain\Event;

interface EntityEventInterface extends DomainEventInterface
{
    public function __construct(string $entityClass, $entityId, array $changeSet = null);

    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getEntityClass();

    /**
     * @return int|string
     */
    public function getEntityId();

    /**
     * @return array
     */
    public function getData();
}
