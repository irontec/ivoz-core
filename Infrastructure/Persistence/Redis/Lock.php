<?php

namespace Ivoz\Core\Infrastructure\Persistence\Redis;

use Ivoz\Core\Application\MutexInterface;
use Psr\Log\LoggerInterface;

class Lock implements MutexInterface
{
    private $sentinel;
    private $logger;
    private $dbIndex;

    /** @var ?\Redis */
    private $redisMaster;

    private $lockKey;
    private $lockRandomValue;

    public function __construct(
        Sentinel $sentinel,
        LoggerInterface $logger,
        int $dbIndex = 1
    ) {
        $this->sentinel = $sentinel;
        $this->logger = $logger;
        $this->dbIndex = $dbIndex;
    }

    /**
     * @param string $lockKey
     * @param int $lockTimeout in seconds
     */
    public function lock(string $lockKey, int $lockTimeout = 1800)
    {
        $this->logger->info(
            sprintf(
                'About to create a redis lock %s with %d ttl',
                $lockKey,
                $lockTimeout
            )
        );

        $redisMasterConf = $this->sentinel->resolveMaster();

        if ($this->redisMaster) {
            $this->redisMaster->close();
            unset($this->redisMaster);
        }

        $this->lockKey = $lockKey;
        $this->lockRandomValue = microtime(true) * 10000;

        $this->redisMaster = new \Redis();
        $this->redisMaster->connect(
            $redisMasterConf->getHost(),
            $redisMasterConf->getPort()
        );

        $this->redisMaster->select($this->dbIndex);

        do {
            $lockAcquired = $this
                ->redisMaster
                ->set(
                    $this->lockKey,
                    $this->lockRandomValue,
                    [
                        'nx', // Only set the key if it does not already exist.
                        'ex' => $lockTimeout
                    ]
                );

            if (! $lockAcquired) {

                $retryIn = 5;
                $this->logger->debug(
                    sprintf(
                        'Retry to create a redis lock %s in %d seconds',
                        $lockKey,
                        $retryIn
                    )
                );

                sleep($retryIn);
            }

        } while (! $lockAcquired);

        $this->logger->info(
            sprintf(
                'Redis lock %s successfully created',
                $lockKey
            )
        );
    }

    public function release()
    {
        $this->logger->info(
            sprintf(
                'About to release a redis lock %s',
                $this->lockKey
            )
        );

        $value = $this
            ->redisMaster
            ->get($this->lockKey);

        if ($value !== (string) $this->lockRandomValue) {
            return;
        }

        $this
            ->redisMaster
            ->del(
                $this->lockKey
            );
    }
}