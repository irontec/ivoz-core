<?php

namespace Ivoz\Core\Infrastructure\Persistence\Redis;

use Psr\Log\LoggerInterface;

class Sentinel
{
    const RESOLVE_RETRIES = 30;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var RedisConf[]  */
    protected $sentinels;

    /** @var RedisConf */
    protected $master;

    /** @var  RedisConf */
    protected $sentinel;

    /**
     * Sentinel constructor.
     * @param RedisConf[] $sentinelConfig
     */
    public function __construct(
        array $sentinelConfig,
        LoggerInterface $logger
    ) {
        if (empty($sentinelConfig)) {
            throw new \RuntimeException(
                'Empty sentinel config found'
            );
        }

        $this->logger = $logger;
        $this->sentinels = $sentinelConfig;
    }

    public static function fromConfigArray(
        array $config,
        LoggerInterface $logger
    ) {
        $sentinelConfig = new SentinelConf($config);

        return new static(
            $sentinelConfig->get(),
            $logger
        );
    }

    public function resolveMaster(int $retries = self::RESOLVE_RETRIES): RedisConf
    {
        $resolveErrors = false;
        for ($i = 0; $i < count($this->sentinels); $i++) {
            try {
                $config = $this->sentinels[$i];
                $this->master = $this->getRedisMasterOrThrowException(
                    $config
                );

                $this->sentinel = $config;

                break;
            } catch (\Exception $e) {
                $this->logger->error(
                    "ERROR: " . $e->getMessage() . ". " . $retries . " retries left"
                );
                $resolveErrors = true;
                continue;
            }
        }

        if ($resolveErrors && $retries > 0) {
            sleep(1);
            return $this->resolveMaster($retries - 1);
        }

        return $this->master;
    }

    private function getRedisMasterOrThrowException(RedisConf $config): RedisConf
    {
        $sentinel = new \RedisSentinel(
            $config->getHost(),
            $config->getPort()
        );

        $masters = $sentinel->masters();

        if (empty($masters)) {
            throw new \RuntimeException(
                'No redis master found'
            );
        }

        $masterName = $masters[0]['name'] ?? '';
        if (!$masterName) {
            throw new \RuntimeException(
                'Unable to get redis master name'
            );
        }

        $master = $sentinel->getMasterAddrByName(
            $masterName
        );

        if (empty($master)) {
            throw new \RuntimeException(
                'Unable to get redis master'
            );
        }
        unset($sentinel);

        return new RedisConf(
            $master[0],
            $master[1],
            $masterName
        );
    }
}
