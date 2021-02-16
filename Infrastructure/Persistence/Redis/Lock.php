<?php

namespace Ivoz\Core\Infrastructure\Persistence\Redis;

use Ivoz\Core\Application\MutexInterface;
use Psr\Log\LoggerInterface;

class Lock implements MutexInterface
{
    private $sentinel;
    private $logger;

    /** @var \Redis */
    private $redisMaster;

    private $lockKey;
    private $lockRandomValue;

    public function __construct(
        Sentinel $sentinel,
        LoggerInterface $logger
    ) {
        $this->sentinel = $sentinel;
        $this->logger = $logger;
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

        $redisMasterConf = $this->sentinel->getRedisMasterConfig();

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
                $ttl = $this
                    ->redisMaster
                    ->ttl($this->lockKey);

                $ttl = max($ttl, 1);

                $this->logger->debug(
                    sprintf(
                        'Retry to create a redis lock %s in %d seconds',
                        $lockKey,
                        $ttl
                    )
                );

                sleep($ttl);
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

        if ($value !== $this->lockRandomValue) {
            return;
        }

        $this
            ->redisMaster
            ->del(
                $this->lockKey
            );
    }
}