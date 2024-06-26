<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Model\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

class UtcDateTimeType extends DateTimeType
{
    static private $utc;

    /**
     * {@inheritdoc}
     * 
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_string($value)) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            $value->setTimezone(
                new \DateTimeZone('UTC')
            );
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * @return null|\DateTime
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof \DateTime) {
            return $value;
        }

        $converted = \DateTime::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            self::$utc ? self::$utc : self::$utc = new \DateTimeZone('UTC')
        );

        if (! $converted) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $converted;
    }
}