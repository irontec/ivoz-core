<?php

namespace Ivoz\Core\Application\Model;

trait DtoNormalizer
{
    /** @var string[]  */
    protected $sensitiveFields = [];

    /**
     * @return array
     */
    abstract public function toArray(bool $hideSensitiveData = false): array;

    /**
     * @return array
     */
    abstract public static function getPropertyMap(string $context = ''): array;

    public function getSensitiveFields(): array
    {
        return $this->sensitiveFields;
    }

    /**
     * @inheritdoc
     */
    public function normalize(string $context, string $role = ''): array
    {
        $response = $this->toArray(true);
        $contextProperties = static::getPropertyMap($context, $role);

        $response = array_filter(
            $response,
            function ($key) use ($contextProperties) {
                return
                    in_array($key, $contextProperties, true)
                    || array_key_exists($key, $contextProperties);
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($response as $key => $val) {

            $isEmbedded = is_array($val)/* || is_object($val)*/;
            if (!$isEmbedded) {
                continue;
            }

            if (!isset($contextProperties[$key])) {
                continue;
            }

            $validSubKeys = $contextProperties[$key];
            $response[$key] = $this->filterResponseValues($val, $validSubKeys);
        }

        return $response;
    }

    private function filterResponseValues($value, $validSubKeys)
    {
        $filterCollectionItems =
            is_array($value)
            && is_array($validSubKeys)
            && array_key_exists(0, $validSubKeys)
            && is_array($validSubKeys[0]);

        if ($filterCollectionItems) {
            foreach ($value as $k => $itemValue) {
                $value[$k] = $this->filterResponseValues(
                    $value[$k],
                    $validSubKeys[0]
                );

                if (empty($value[$k])) {
                    unset($value[$k]);
                }
            }

            return $value;
        }

        return array_filter(
            $value,
            function ($subkey) use ($validSubKeys) {
                return in_array($subkey, $validSubKeys);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @inheritdoc
     */
    public function denormalize(array $data, string $context, string $role = ''): void
    {
        $contextProperties = static::getPropertyMap($context, $role);

        $this->setByContext(
            $contextProperties,
            $data
        );
    }

    protected function setByContext(array $contextProperties, array $data)
    {
        unset($contextProperties['id']);

        $methods = [];
        foreach ($contextProperties as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $property) {
                    if (!isset($data[$key]) || !array_key_exists($property, $data[$key])) {
                        continue;
                    }

                    $setter = 'set' . ucfirst($key) . ucfirst($property);
                    $dataPath = [
                        $key,
                        $property
                    ];
                    $methods[$setter] = $dataPath;
                }
            } elseif (array_key_exists($value, $data)) {
                $methods['set' . ucfirst($key)] = [$value];
            }
        }

        foreach ($methods as $setter => $dataPath) {
            $value = $this->getValueFromArray($data, $dataPath);
            $this->{$setter}($value);
        }
    }

    /**
     * @param array $data
     * @param array $dataPath
     * @return mixed
     */
    private function getValueFromArray(array $data, array $dataPath)
    {
        $response = $data;
        foreach ($dataPath as $key) {
            if (!isset($response[$key])) {
                $response = null;
                continue;
            }

            $response = $response[$key];
        }

        return $response;
    }
}
