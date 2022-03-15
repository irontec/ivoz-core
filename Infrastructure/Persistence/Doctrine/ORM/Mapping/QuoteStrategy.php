<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Internal\SQLResultCasing;

class QuoteStrategy extends DefaultQuoteStrategy
{
    use SQLResultCasing;

    /**
     * {@inheritdoc}
     */
    public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ClassMetadata $class = null)
    {
        $columnName = parent::getColumnAlias(...func_get_args());
        $columnName = is_numeric($columnName[0]) ? '_' . $columnName : $columnName;

        return $this->getSQLResultCasing($platform, $columnName);
    }
}
