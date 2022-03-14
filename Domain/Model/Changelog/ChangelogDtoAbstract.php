<?php

namespace Ivoz\Core\Domain\Model\Changelog;

use Ivoz\Core\Application\DataTransferObjectInterface;
use Ivoz\Core\Application\Model\DtoNormalizer;
use Ivoz\Core\Domain\Model\Commandlog\CommandlogDto;

/**
* ChangelogDtoAbstract
* @codeCoverageIgnore
*/
abstract class ChangelogDtoAbstract implements DataTransferObjectInterface
{
    use DtoNormalizer;

    /**
     * @var string|null
     */
    private $entity = null;

    /**
     * @var string|null
     */
    private $entityId = null;

    /**
     * @var array|null
     */
    private $data = null;

    /**
     * @var \DateTimeInterface|string|null
     */
    private $createdOn = null;

    /**
     * @var int|null
     */
    private $microtime = null;

    /**
     * @var string|null
     */
    private $id = null;

    /**
     * @var CommandlogDto | null
     */
    private $command = null;

    public function __construct($id = null)
    {
        $this->setId($id);
    }

    /**
    * @inheritdoc
    */
    public static function getPropertyMap(string $context = '', string $role = null): array
    {
        if ($context === self::CONTEXT_COLLECTION) {
            return ['id' => 'id'];
        }

        return [
            'entity' => 'entity',
            'entityId' => 'entityId',
            'data' => 'data',
            'createdOn' => 'createdOn',
            'microtime' => 'microtime',
            'id' => 'id',
            'commandId' => 'command'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(bool $hideSensitiveData = false): array
    {
        $response = [
            'entity' => $this->getEntity(),
            'entityId' => $this->getEntityId(),
            'data' => $this->getData(),
            'createdOn' => $this->getCreatedOn(),
            'microtime' => $this->getMicrotime(),
            'id' => $this->getId(),
            'command' => $this->getCommand()
        ];

        if (!$hideSensitiveData) {
            return $response;
        }

        foreach ($this->sensitiveFields as $sensitiveField) {
            if (!array_key_exists($sensitiveField, $response)) {
                throw new \Exception($sensitiveField . ' field was not found');
            }
            $response[$sensitiveField] = '*****';
        }

        return $response;
    }

    public function setEntity(string $entity): static
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntityId(string $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setCreatedOn(\DateTimeInterface|string $createdOn): static
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function getCreatedOn(): \DateTimeInterface|string|null
    {
        return $this->createdOn;
    }

    public function setMicrotime(int $microtime): static
    {
        $this->microtime = $microtime;

        return $this;
    }

    public function getMicrotime(): ?int
    {
        return $this->microtime;
    }

    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setCommand(?CommandlogDto $command): static
    {
        $this->command = $command;

        return $this;
    }

    public function getCommand(): ?CommandlogDto
    {
        return $this->command;
    }

    public function setCommandId($id): static
    {
        $value = !is_null($id)
            ? new CommandlogDto($id)
            : null;

        return $this->setCommand($value);
    }

    public function getCommandId()
    {
        if ($dto = $this->getCommand()) {
            return $dto->getId();
        }

        return null;
    }
}
