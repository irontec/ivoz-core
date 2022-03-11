<?php

namespace Ivoz\Core\Domain\Model;

use Ivoz\Core\Application\DataTransferObjectInterface;

trait ChangelogTrait
{
    /**
     * @var bool
     */
    public $__isInitialized__ = true;

    /**
     * Changelog tracking purpose
     * @var array
     */
    protected $_initialValues = [];

    /**
     * @var bool
     */
    protected $isPersisted = false;

    abstract public function getId();
    abstract protected function __toArray();
    abstract public static function createDto(string|int $id = null): DataTransferObjectInterface;

    /**
     * TRUE on new entities until transaction is closed
     * always false for ON_COMMIT lifecycle services
     */
    public function isNew(): bool
    {
        return !$this->isPersisted();
    }

    public function isInitialized(): bool
    {
        return !empty($this->_initialValues);
    }

    public function isPersisted(): bool
    {
        return $this->isPersisted;
    }

    public function markAsPersisted(): void
    {
        $this->isPersisted = true;
    }

    public function hasBeenDeleted(): bool
    {
        $id = $this->getId();
        if ($id !== null) {
            return false;
        }

        $initialId = $this->getInitialValue('id');
        $hasInitialValue = !is_null($initialId);

        return $hasInitialValue;
    }

    public function initChangelog(): void
    {
        $values = $this->__toArray();
        if (!$this->getId()) {
            // Empty values for entities with no Id
            foreach ($values as $key => $val) {
                $values[$key] = null;
            }
        }

        $this->isPersisted = $this->getId() !== null;

        $this->_initialValues = $values;
    }

    /**
     * @throws \Exception
     */
    public function hasChanged(string $dbFieldName): bool
    {
        if (!array_key_exists($dbFieldName, $this->_initialValues)) {
            throw new \Exception($dbFieldName . ' field was not found');
        }
        $currentValues = $this->__toArray();

        return $currentValues[$dbFieldName] != $this->_initialValues[$dbFieldName];
    }

    /**
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function getInitialValue(string $dbFieldName): mixed
    {
        if (!array_key_exists($dbFieldName, $this->_initialValues)) {
            throw new \Exception($dbFieldName . ' field was not found');
        }

        return $this->_initialValues[$dbFieldName];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getChangeSet(): array
    {
        $changes = [];
        $currentValues = $this->__toArray();
        foreach ($currentValues as $key => $value) {
            $isDateTime =
                $value instanceof \DateTimeInterface
                || $this->_initialValues[$key] instanceof \DateTimeInterface;

            $strictCompare = !$isDateTime;

            $notChanged = $strictCompare
                ? $this->_initialValues[$key] === $currentValues[$key]
                : $this->_initialValues[$key] == $currentValues[$key];

            if ($notChanged) {
                continue;
            }
            $value = $currentValues[$key];
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $changes[$key] = $value;
        }

        /** @var DataTransferObjectInterface $dto */
        $dto = static::createDto();
        $sensitiveFields = $dto->getSensitiveFields();
        foreach ($sensitiveFields as $sensitiveField) {

            if (!isset($changes[$sensitiveField])) {
                continue;
            }

            $changes[$sensitiveField] = '*****';
        }

        return $changes;
    }

    /**
     * @return string[]
     */
    public function getChangedFields(): array
    {
        $changes = $this->getChangeSet();

        return array_keys(
            $changes
        );
    }
}
