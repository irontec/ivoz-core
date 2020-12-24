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

        return array_filter(
            $response,
            function ($key) use ($contextProperties) {
                return
                    in_array($key, $contextProperties, true)
                    || array_key_exists($key, $contextProperties);
            },
            ARRAY_FILTER_USE_KEY
        );
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
