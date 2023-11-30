<?php

declare(strict_types=1);

namespace Ivoz\Core\Domain\Model\Changelog;

use Assert\Assertion;
use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\Model\ChangelogTrait;
use Ivoz\Core\Domain\Model\EntityInterface;
use Ivoz\Core\Domain\ForeignKeyTransformerInterface;
use Ivoz\Core\Domain\Model\Helper\DateTimeHelper;
use Ivoz\Core\Domain\Model\Commandlog\CommandlogInterface;
use Ivoz\Core\Domain\Model\Commandlog\Commandlog;

/**
* ChangelogAbstract
* @codeCoverageIgnore
*/
abstract class ChangelogAbstract
{
    use ChangelogTrait;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var ?array
     */
    protected $data = [];

    /**
     * @var \DateTime
     */
    protected $createdOn;

    /**
     * @var int
     */
    protected $microtime;

    /**
     * @var CommandlogInterface
     */
    protected $command;

    /**
     * Constructor
     */
    protected function __construct(
        string $entity,
        string $entityId,
        \DateTimeInterface|string $createdOn,
        int $microtime
    ) {
        $this->setEntity($entity);
        $this->setEntityId($entityId);
        $this->setCreatedOn($createdOn);
        $this->setMicrotime($microtime);
    }

    abstract public function getId(): null|string|int;

    public function __toString(): string
    {
        return sprintf(
            "%s#%s",
            "Changelog",
            (string) $this->getId()
        );
    }

    /**
     * @throws \Exception
     */
    protected function sanitizeValues(): void
    {
    }

    /**
     * @param string|null $id
     */
    public static function createDto($id = null): ChangelogDto
    {
        return new ChangelogDto($id);
    }

    /**
     * @internal use EntityTools instead
     * @param null|ChangelogInterface $entity
     */
    public static function entityToDto(?EntityInterface $entity, int $depth = 0): ?ChangelogDto
    {
        if (!$entity) {
            return null;
        }

        Assertion::isInstanceOf($entity, ChangelogInterface::class);

        if ($depth < 1) {
            return static::createDto($entity->getId());
        }

        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy && !$entity->__isInitialized()) {
            return static::createDto($entity->getId());
        }

        $dto = $entity->toDto($depth - 1);

        return $dto;
    }

    /**
     * Factory method
     * @internal use EntityTools instead
     * @param ChangelogDto $dto
     */
    public static function fromDto(
        DataTransferObjectInterface $dto,
        ForeignKeyTransformerInterface $fkTransformer
    ): static {
        Assertion::isInstanceOf($dto, ChangelogDto::class);
        $entity = $dto->getEntity();
        Assertion::notNull($entity, 'getEntity value is null, but non null value was expected.');
        $entityId = $dto->getEntityId();
        Assertion::notNull($entityId, 'getEntityId value is null, but non null value was expected.');
        $createdOn = $dto->getCreatedOn();
        Assertion::notNull($createdOn, 'getCreatedOn value is null, but non null value was expected.');
        $microtime = $dto->getMicrotime();
        Assertion::notNull($microtime, 'getMicrotime value is null, but non null value was expected.');
        $command = $dto->getCommand();
        Assertion::notNull($command, 'getCommand value is null, but non null value was expected.');

        $self = new static(
            $entity,
            $entityId,
            $createdOn,
            $microtime
        );

        $self
            ->setData($dto->getData())
            ->setCommand($fkTransformer->transform($command));

        $self->initChangelog();

        return $self;
    }

    /**
     * @internal use EntityTools instead
     * @param ChangelogDto $dto
     */
    public function updateFromDto(
        DataTransferObjectInterface $dto,
        ForeignKeyTransformerInterface $fkTransformer
    ): static {
        Assertion::isInstanceOf($dto, ChangelogDto::class);

        $entity = $dto->getEntity();
        Assertion::notNull($entity, 'getEntity value is null, but non null value was expected.');
        $entityId = $dto->getEntityId();
        Assertion::notNull($entityId, 'getEntityId value is null, but non null value was expected.');
        $createdOn = $dto->getCreatedOn();
        Assertion::notNull($createdOn, 'getCreatedOn value is null, but non null value was expected.');
        $microtime = $dto->getMicrotime();
        Assertion::notNull($microtime, 'getMicrotime value is null, but non null value was expected.');
        $command = $dto->getCommand();
        Assertion::notNull($command, 'getCommand value is null, but non null value was expected.');

        $this
            ->setEntity($entity)
            ->setEntityId($entityId)
            ->setData($dto->getData())
            ->setCreatedOn($createdOn)
            ->setMicrotime($microtime)
            ->setCommand($fkTransformer->transform($command));

        return $this;
    }

    /**
     * @internal use EntityTools instead
     */
    public function toDto(int $depth = 0): ChangelogDto
    {
        return self::createDto()
            ->setEntity(self::getEntity())
            ->setEntityId(self::getEntityId())
            ->setData(self::getData())
            ->setCreatedOn(self::getCreatedOn())
            ->setMicrotime(self::getMicrotime())
            ->setCommand(Commandlog::entityToDto(self::getCommand(), $depth));
    }

    protected function __toArray(): array
    {
        return [
            'entity' => self::getEntity(),
            'entityId' => self::getEntityId(),
            'data' => self::getData(),
            'createdOn' => self::getCreatedOn(),
            'microtime' => self::getMicrotime(),
            'commandId' => self::getCommand()->getId()
        ];
    }

    protected function setEntity(string $entity): static
    {
        Assertion::maxLength($entity, 150, 'entity value "%s" is too long, it should have no more than %d characters, but has %d characters.');

        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    protected function setEntityId(string $entityId): static
    {
        Assertion::maxLength($entityId, 36, 'entityId value "%s" is too long, it should have no more than %d characters, but has %d characters.');

        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    protected function setData(?array $data = null): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    protected function setCreatedOn(string|\DateTimeInterface $createdOn): static
    {

        /** @var \Datetime */
        $createdOn = DateTimeHelper::createOrFix(
            $createdOn,
            null
        );

        if ($this->isInitialized() && $this->createdOn == $createdOn) {
            return $this;
        }

        $this->createdOn = $createdOn;

        return $this;
    }

    public function getCreatedOn(): \DateTime
    {
        return clone $this->createdOn;
    }

    protected function setMicrotime(int $microtime): static
    {
        $this->microtime = $microtime;

        return $this;
    }

    public function getMicrotime(): int
    {
        return $this->microtime;
    }

    protected function setCommand(CommandlogInterface $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getCommand(): CommandlogInterface
    {
        return $this->command;
    }
}
