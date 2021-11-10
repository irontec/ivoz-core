<?php

namespace Ivoz\Core\Application;

interface DataTransferObjectInterface
{
    const CONTEXT_COLLECTION = 'collection';
    const CONTEXT_SIMPLE = 'item';
    const CONTEXT_DETAILED = 'detailed';
    const CONTEXT_DETAILED_COLLECTION = 'detailedCollection';
    const CONTEXT_EMPTY = '';

    const CONTEXT_TYPES = [
        self::CONTEXT_EMPTY,
        self::CONTEXT_COLLECTION,
        self::CONTEXT_SIMPLE,
        self::CONTEXT_DETAILED,
        self::CONTEXT_DETAILED_COLLECTION
    ];

    public function setId($id);
    public function getId();
    public function normalize(string $context, string $role = ''): array;
    public function denormalize(array $data, string $context, string $role = ''): void;
    public static function getPropertyMap(string $context = '', string $role = null): array;
    public function getSensitiveFields(): array;
    public function toArray(bool $hideSensitiveData = false): array;
}
