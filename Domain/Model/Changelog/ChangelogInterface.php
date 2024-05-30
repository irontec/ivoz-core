<?php

namespace Ivoz\Core\Domain\Model\Changelog;

use Ivoz\Core\Domain\Model\EntityInterface;
use Ivoz\Core\Domain\Event\EntityEventInterface;
use Ivoz\Core\Domain\Model\Commandlog\CommandlogInterface;
use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\ForeignKeyTransformerInterface;

/**
* ChangelogInterface
*/
interface ChangelogInterface extends EntityInterface
{
    /**
     * @param \Ivoz\Core\Domain\Event\EntityEventInterface $event
     * @return self
     */
    public static function fromEvent(EntityEventInterface $event, CommandlogInterface $command);

    /**
     * @param array<array-key, mixed> $data | null
     * @return static
     */
    public function replaceData($data = null);

    /**
     * Get id
     * @codeCoverageIgnore
     * @return string
     */
    public function getId(): ?string;

    /**
     * @param string|null $id
     */
    public static function createDto($id = null): ChangelogDto;

    /**
     * @internal use EntityTools instead
     * @param null|ChangelogInterface $entity
     */
    public static function entityToDto(?EntityInterface $entity, int $depth = 0): ?ChangelogDto;

    /**
     * Factory method
     * @internal use EntityTools instead
     * @param ChangelogDto $dto
     */
    public static function fromDto(DataTransferObjectInterface $dto, ForeignKeyTransformerInterface $fkTransformer): static;

    /**
     * @internal use EntityTools instead
     */
    public function toDto(int $depth = 0): ChangelogDto;

    public function getEntity(): string;

    public function getEntityId(): string;

    public function getData(): ?array;

    public function getCreatedOn(): \DateTime;

    public function getMicrotime(): int;

    public function getCommand(): CommandlogInterface;

    public function isInitialized(): bool;
}
