<?php

namespace Ivoz\Core\Domain\Model\Commandlog;

use Ivoz\Core\Domain\Event\CommandEventInterface;
use Ivoz\Core\Domain\Model\LoggerEntityInterface;

class Commandlog extends CommandlogAbstract implements LoggerEntityInterface, CommandlogInterface
{
    use CommandlogTrait;

    /**
     * Get id
     * @codeCoverageIgnore
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param \Ivoz\Core\Domain\Event\CommandEventInterface $event
     * @return self
     */
    public static function fromEvent(CommandEventInterface $event)
    {
        $entity = new static(
            $event->getRequestId(),
            $event->getService(),
            $event->getOccurredOn(),
            $event->getMicrotime()
        );

        $entity->id = $event->getId();
        $entity->setAgent(
            $event->getAgent()
        );
        $entity->setMethod(
            $event->getMethod()
        );
        $entity->setArguments(
            $event->getArguments()
        );

        $entity->sanitizeValues();
        $entity->initChangelog();

        return $entity;
    }
}
