<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query as DqlQuery;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration\ObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration\SimpleObjectHydrator;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Hydration\DtoHydrator;

class EntityManager extends EntityManagerDecorator implements ToggleableBufferedQueryInterface
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

            /** @var \PDO $connection */
            $connection = $this->getNativeConnection();
            $driverName = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);

            if ($driverName === 'mysql') {
                $connection->setAttribute(
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

        $emRef = new \ReflectionClass(
            DoctrineEntityManager::class
        );
        /** @var DoctrineEntityManager $em */
        $em = $emRef->newInstanceWithoutConstructor();
        $eventManager = $conn->getEventManager();
        (function () use ($conn, $config, $eventManager) {
            $this->__construct($conn, $config, $eventManager);
        })->call($em);

        $instance = new self($em);
        (function () use ($instance) {
            $this->em = $instance;
        })->call($instance->getUnitOfWork());

        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function getHydrator($hydrationMode)
    {
        return $this->newHydrator($hydrationMode);
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode)
    {
        switch ($hydrationMode) {
            case Query::HYDRATE_OBJECT:
                return new ObjectHydrator($this);

            case Query::HYDRATE_SIMPLEOBJECT:
                return new SimpleObjectHydrator($this);

            case Query::HYDRATE_DTO:
                return new DtoHydrator($this);

            default:
                return parent::newHydrator(...func_get_args());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createQuery($dql = '')
    {
        $query = new DqlQuery($this);

        if (! empty($dql)) {
            $query->setDQL($dql);
        }

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function createNativeQuery($sql, ResultSetMapping $rsm)
    {
        $query = new NativeQuery($this);

        $query->setSQL($sql);
        $query->setResultSetMapping($rsm);

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }
}