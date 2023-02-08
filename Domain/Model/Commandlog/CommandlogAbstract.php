<?php

declare(strict_types=1);

namespace Ivoz\Core\Domain\Model\Commandlog;

use Assert\Assertion;
use Ivoz\Core\Domain\DataTransferObjectInterface;
use Ivoz\Core\Domain\Model\ChangelogTrait;
use Ivoz\Core\Domain\Model\EntityInterface;
use Ivoz\Core\Domain\ForeignKeyTransformerInterface;
use Ivoz\Core\Domain\Model\Helper\DateTimeHelper;

/**
* CommandlogAbstract
* @codeCoverageIgnore
*/
abstract class CommandlogAbstract
{
    use ChangelogTrait;

    /**
     * @var string
     */
    protected $requestId;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var ?string
     */
    protected $method = null;

    /**
     * @var ?array
     */
    protected $arguments = [];

    /**
     * @var ?array
     */
    protected $agent = [];

    /**
     * @var \DateTime
     */
    protected $createdOn;

    /**
     * @var int
     */
    protected $microtime;

    /**
     * Constructor
     */
    protected function __construct(
        string $requestId,
        string $class,
        \DateTimeInterface|string $createdOn,
        int $microtime
    ) {
        $this->setRequestId($requestId);
        $this->setClass($class);
        $this->setCreatedOn($createdOn);
        $this->setMicrotime($microtime);
    }

    abstract public function getId(): null|string|int;

    public function __toString(): string
    {
        return sprintf(
            "%s#%s",
            "Commandlog",
            (string) $this->getId()
        );
    }

    /**
     * @throws \Exception
     */
    protected function sanitizeValues(): void
    {
    }

    public static function createDto(string|int|null $id = null): CommandlogDto
    {
        return new CommandlogDto($id);
    }

    /**
     * @internal use EntityTools instead
     * @param null|CommandlogInterface $entity
     */
    public static function entityToDto(?EntityInterface $entity, int $depth = 0): ?CommandlogDto
    {
        if (!$entity) {
            return null;
        }

        Assertion::isInstanceOf($entity, CommandlogInterface::class);

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
     * @param CommandlogDto $dto
     */
    public static function fromDto(
        DataTransferObjectInterface $dto,
        ForeignKeyTransformerInterface $fkTransformer
    ): static {
        Assertion::isInstanceOf($dto, CommandlogDto::class);
        $requestId = $dto->getRequestId();
        Assertion::notNull($requestId, 'getRequestId value is null, but non null value was expected.');
        $class = $dto->getClass();
        Assertion::notNull($class, 'getClass value is null, but non null value was expected.');
        $createdOn = $dto->getCreatedOn();
        Assertion::notNull($createdOn, 'getCreatedOn value is null, but non null value was expected.');
        $microtime = $dto->getMicrotime();
        Assertion::notNull($microtime, 'getMicrotime value is null, but non null value was expected.');

        $self = new static(
            $requestId,
            $class,
            $createdOn,
            $microtime
        );

        $self
            ->setMethod($dto->getMethod())
            ->setArguments($dto->getArguments())
            ->setAgent($dto->getAgent());

        $self->initChangelog();

        return $self;
    }

    /**
     * @internal use EntityTools instead
     * @param CommandlogDto $dto
     */
    public function updateFromDto(
        DataTransferObjectInterface $dto,
        ForeignKeyTransformerInterface $fkTransformer
    ): static {
        Assertion::isInstanceOf($dto, CommandlogDto::class);

        $requestId = $dto->getRequestId();
        Assertion::notNull($requestId, 'getRequestId value is null, but non null value was expected.');
        $class = $dto->getClass();
        Assertion::notNull($class, 'getClass value is null, but non null value was expected.');
        $createdOn = $dto->getCreatedOn();
        Assertion::notNull($createdOn, 'getCreatedOn value is null, but non null value was expected.');
        $microtime = $dto->getMicrotime();
        Assertion::notNull($microtime, 'getMicrotime value is null, but non null value was expected.');

        $this
            ->setRequestId($requestId)
            ->setClass($class)
            ->setMethod($dto->getMethod())
            ->setArguments($dto->getArguments())
            ->setAgent($dto->getAgent())
            ->setCreatedOn($createdOn)
            ->setMicrotime($microtime);

        return $this;
    }

    /**
     * @internal use EntityTools instead
     */
    public function toDto(int $depth = 0): CommandlogDto
    {
        return self::createDto()
            ->setRequestId(self::getRequestId())
            ->setClass(self::getClass())
            ->setMethod(self::getMethod())
            ->setArguments(self::getArguments())
            ->setAgent(self::getAgent())
            ->setCreatedOn(self::getCreatedOn())
            ->setMicrotime(self::getMicrotime());
    }

    protected function __toArray(): array
    {
        return [
            'requestId' => self::getRequestId(),
            'class' => self::getClass(),
            'method' => self::getMethod(),
            'arguments' => self::getArguments(),
            'agent' => self::getAgent(),
            'createdOn' => self::getCreatedOn(),
            'microtime' => self::getMicrotime()
        ];
    }

    protected function setRequestId(string $requestId): static
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    protected function setClass(string $class): static
    {
        Assertion::maxLength($class, 50, 'class value "%s" is too long, it should have no more than %d characters, but has %d characters.');

        $this->class = $class;

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    protected function setMethod(?string $method = null): static
    {
        if (!is_null($method)) {
            Assertion::maxLength($method, 64, 'method value "%s" is too long, it should have no more than %d characters, but has %d characters.');
        }

        $this->method = $method;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    protected function setArguments(?array $arguments = null): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    protected function setAgent(?array $agent = null): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getAgent(): ?array
    {
        return $this->agent;
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
}
