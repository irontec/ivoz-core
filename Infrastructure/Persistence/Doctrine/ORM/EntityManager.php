<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration\ObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration\SimpleObjectHydrator;

class EntityManager extends DoctrineEntityManager implements ToggleableBufferedQueryInterface
{
    public function enableBufferedQuery()
    {
        $this->setBufferedQuery(true);
    }

    public function disableBufferedQuery()
    {
        $this->setBufferedQuery(false);
    }

    private function setBufferedQuery(bool $enabled = true)
    {
        // https://www.php.net/manual/en/mysqlinfo.concepts.buffering.php

        (function () use ($enabled) {
            $this->connect();
            $driverName = $this->_conn->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driverName === 'mysql') {
                $this->_conn->setAttribute(
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                    $enabled
                );
            }
        })->call($this->getConnection());
    }

    public static function create($conn, Configuration $config, EventManager $eventManager = null)
    {
        if (! $config->getMetadataDriverImpl()) {
            throw ORMException::missingMappingDriverImpl();
        }

        switch (true) {
            case (is_array($conn)):
                $conn = DriverManager::getConnection(
                    $conn,
                    $config,
                    ($eventManager ?: new EventManager())
                );
                break;

            case ($conn instanceof Connection):
                if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                    throw ORMException::mismatchedEventManager();
                }
                break;

            default:
                throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new static($conn, $config, $conn->getEventManager());
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode)
    {
        if ($hydrationMode === Query::HYDRATE_OBJECT) {
            return new ObjectHydrator($this);
        }

        if ($hydrationMode === Query::HYDRATE_SIMPLEOBJECT) {
            return new SimpleObjectHydrator($this);
        }

        return parent::newHydrator(...func_get_args());
    }
}
