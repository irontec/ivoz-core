<?php

namespace Ivoz\Core\Infrastructure\Domain\Service;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query as DqlQuery;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Parser;
use Ivoz\Core\Domain\Event\EntityEventInterface;
use Ivoz\Core\Domain\Event\QueryWasExecuted;
use Ivoz\Core\Domain\Service\DomainEventPublisher;
use Ivoz\Core\Infrastructure\Domain\Service\Lifecycle\CommandPersister;
use Psr\Log\LoggerInterface;

class DoctrineQueryRunner
{
    const DEADLOCK_RETRIES = 3;
    const RETRY_SLEEP_TIME = 2;

    protected $em;
    protected $eventPublisher;
    protected $commandPersister;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        DomainEventPublisher $eventPublisher,
        CommandPersister $commandPersister,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->eventPublisher = $eventPublisher;
        $this->commandPersister = $commandPersister;
        $this->logger = $logger;
    }

    /**
     * @return int affected rows
     */
    public function execute(string $entityName, AbstractQuery $query)
    {
        $connection = $this->em->getConnection();
        $alreadyWithinTransaction = $connection->isTransactionActive();

        if ($alreadyWithinTransaction) {

            $event = $this->prepareChangelogEvent(
                $entityName,
                $query
            );

            return $this->runQueryAndReturnAffectedRows(
                $query,
                $event
            );
        }

        $retries = self::DEADLOCK_RETRIES;
        /** @phpstan-ignore-next-line  */
        while (0 < $retries) {

            $retries -= 1;

            $this
                ->em
                ->getConnection()
                ->beginTransaction();

            try {

                $affectedRows = $this->execute(
                    $entityName,
                    $query
                );

                $this
                    ->commandPersister
                    ->persistEvents();

                $this
                    ->em
                    ->getConnection()
                    ->commit();

                return $affectedRows;

            } catch (\Exception $e) {

                /**
                 * Excepted issues:
                 * SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction
                 * SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded; try restarting transaction
                 */
                $this
                    ->em
                    ->getConnection()
                    ->rollBack();

                $lockIssues = false !== strpos(
                    $e->getMessage(),
                    'try restarting transaction'
                );

                if (!$retries || !$lockIssues) {
                    throw $e;
                }

                $this->logger->warning(
                    'Retrying transaction: ' . $e->getMessage()
                );

                sleep(self::RETRY_SLEEP_TIME);
            }
        }
    }

    /**
     * @return int $affectedRows
     */
    private function runQueryAndReturnAffectedRows(AbstractQuery $query, EntityEventInterface $event)
    {
        $isNativeQuery = $query instanceof NativeQuery;
        if ($isNativeQuery) {
            $affectedRows = $this->em->getConnection()->executeUpdate(
                $query->getSQL()
            );
        } else {
            $affectedRows = $query->execute();
        }

        if ($affectedRows > 0) {
            $this->eventPublisher->publish($event);
        }

        return $affectedRows;
    }

    /**
     * @param AbstractQuery $query
     * @return array
     */
    private function getQueryParameters(AbstractQuery $query): array
    {
        /** @var Parameter[] $parameters */
        $parameters = $query->getParameters()->toArray();
        foreach ($parameters as $key => $parameter) {
            $parameters[$key] = [
                $parameter->getName() => $parameter->getValue()
            ];
        }

        return $parameters;
    }

    /**
     * @param string $entityName
     * @param AbstractQuery $query
     * @return QueryWasExecuted
     */
    private function prepareChangelogEvent(string $entityName, AbstractQuery $query): QueryWasExecuted
    {
        $sqlParams = [];
        $types = [];

        /** @var \Closure $dqlParamResolver */
        $dqlParamResolver = function () use (&$sqlParams, &$types) {

            /** @var AbstractQuery $this */
            assert(
                $this instanceof DqlQuery,
                new \Exception('dqlParamResolver context must be instance of ' . DqlQuery::class)
            );

            $parser = new Parser($this);
            $paramMappings = $parser->parse()->getParameterMappings();
            /** @phpstan-ignore-next-line  */
            list($params, $paramTypes) = $this->processParameterMappings($paramMappings);

            $sqlParams = $params;
            $types = $paramTypes;
        };

        if ($query instanceof DqlQuery) {
            $dqlParamResolver->call($query);
        } else {
            $sqlParams = $this->getQueryParameters(
                $query
            );
        }

        return new QueryWasExecuted(
            $entityName,
            0,
            [
                'query' => $query->getSQL(),
                'arguments' => $sqlParams,
                'types' => $types
            ]
        );
    }
}
