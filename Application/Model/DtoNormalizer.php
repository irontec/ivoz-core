<?php

namespace Ivoz\Core\Application\Model;

trait DtoNormalizer
{
    /** @var string[]  */
    protected $sensitiveFields = [];

    /**
     * @return array
     */
    abstract public function toArray($hideSensitiveData = false);

    /**
     * @return array
     */
    abstract public static function getPropertyMap(string $context = '');

    public function getSensitiveFields(): array
    {
        return $this->sensitiveFields;
    }

    /**
     * @inheritdoc
     */
    public function normalize(string $context, string $role = '')
    {
        $response = $this->toArray(true);
        $contextProperties = static::getPropertyMap($context, $role);

        $response = array_filter(
            $response,
            function ($value, $key) use ($contextProperties) {

                $isEmbedded = is_array($value) || is_object($value);

                return
                    in_array($key, $contextProperties, true)
                    || (!$isEmbedded && in_array($value, $contextProperties, true))
                    || ($isEmbedded && array_key_exists($key, $contextProperties));
            },
            ARRAY_FILTER_USE_BOTH
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
            $response[$key] = array_filter(
                $val,
                function ($key) use ($validSubKeys) {
                    return in_array($key, $validSubKeys);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function denormalize(array $data, string $context, string $role = '')
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
