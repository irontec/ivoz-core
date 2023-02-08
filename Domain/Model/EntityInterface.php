<?php

namespace Ivoz\Core\Domain\Model;

use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\ForeignKeyTransformerInterface;

/**
 * Entity interface
 *
 * @author Mikel Madariaga <mikel@irontec.com>
 */
interface EntityInterface
{
    /**
     * @return string|int|null
     */
    public function getId();

    public function isNew(): bool;

    public function isPersisted(): bool;

    public function markAsPersisted(): void;

    public function hasBeenDeleted(): bool;

    public function __toString(): string;

    public function isInitialized(): bool;

    public function initChangelog(): void;

    /**
     * @throws \Exception
     */
    public function hasChanged(string $fieldName): bool;

    /**
     * @return string[]
     */
    public function getChangedFields(): array;

    /**
     * @throws \Exception
     */
    public function getInitialValue(string $fieldName): mixed;

    public static function createDto(int|string|null $id = null): DataTransferObjectInterface;

    /**
     * @todo move this into dto::fromEntity
     */
    public static function entityToDto(?EntityInterface $entity, int $depth = 0): ?DataTransferObjectInterface;

    /**
     * Factory method
     */
    public static function fromDto(
        DataTransferObjectInterface $dto,
        ForeignKeyTransformerInterface $fkTransformer
    ): EntityInterface;

    /**
     * @internal use EntityTools instead
     */
    public function updateFromDto(
        DataTransferObjectInterface $dto,
        ForeignKeyTransformerInterface $fkTransformer
    ): static;

    /**
     * DTO casting
     * @todo move this into dto::fromEntity
     */
    public function toDto(int $depth = 0): DataTransferObjectInterface;
}
