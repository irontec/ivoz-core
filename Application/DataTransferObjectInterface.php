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

    /**
     * @return array
     */
    public function normalize(string $context, string $role = '');

    /**
     * @return void
     */
    public function denormalize(array $data, string $context, string $role = '');

    /**
     * @return array
     */
    public static function getPropertyMap(string $context = '', string $role = null);

    public function getSensitiveFields(): array;

    /**
     * @return array
     */
    public function toArray($hideSensitiveData = false);
}
