<?php

namespace Ivoz\Core\Domain\Model;

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

            $isEmbedded = is_array($val);
            if (!$isEmbedded) {
                continue;
            }

            if (!isset($contextProperties[$key])) {
                continue;
            }

            $validSubKeys = $contextProperties[$key];
            if (!is_array($validSubKeys)) {
                throw new \RuntimeException($key . ' context properties were expected to be array');
            }

            $response[$key] = $this->filterResponseSubProperties(
                $val,
                $validSubKeys
            );
        }

        return $response;
    }

    private function filterResponseSubProperties(array $values, array $validSubKeys): array
    {
        if (empty($validSubKeys)) {
            return [];
        }

        if (is_array($validSubKeys[0])) {
            $response = [];
            foreach($values as $k => $value) {
                $response[$k] = $this->filterResponseSubProperties(
                    $value,
                    $validSubKeys[0]
                );
            }

            return $response;
        }

        return array_filter(
            $values,
            function ($key) use ($validSubKeys) {
                return in_array($key, $validSubKeys);
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
                    if (
                        !isset($data[$key])
                        || !is_string($property)
                        || !is_array($data[$key])
                        || !array_key_exists($property, $data[$key])
                    ) {
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
