<?php

namespace Ivoz\Core\Domain\Model\Commandlog;

use Ivoz\Core\Domain\Model\EntityInterface;
use Ivoz\Core\Domain\Event\CommandEventInterface;
use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\ForeignKeyTransformerInterface;

/**
* CommandlogInterface
*/
interface CommandlogInterface extends EntityInterface
{
    /**
     * Get id
     * @codeCoverageIgnore
     * @return string
     */
    public function getId(): ?string;

    /**
     * @param \Ivoz\Core\Domain\Event\CommandEventInterface $event
     * @return self
     */
    public static function fromEvent(CommandEventInterface $event);

    public static function createDto(string|int|null $id = null): CommandlogDto;

    /**
     * @internal use EntityTools instead
     * @param null|CommandlogInterface $entity
     */
    public static function entityToDto(?EntityInterface $entity, int $depth = 0): ?CommandlogDto;

    /**
     * Factory method
     * @internal use EntityTools instead
     * @param CommandlogDto $dto
     */
    public static function fromDto(DataTransferObjectInterface $dto, ForeignKeyTransformerInterface $fkTransformer): static;

    /**
     * @internal use EntityTools instead
     */
    public function toDto(int $depth = 0): CommandlogDto;

    public function getRequestId(): string;

    public function getClass(): string;

    public function getMethod(): ?string;

    public function getArguments(): ?array;

    public function getAgent(): ?array;

    public function getCreatedOn(): \DateTime;

    public function getMicrotime(): int;

    public function isInitialized(): bool;
}
