<?php

namespace Ivoz\Core\Infrastructure\Persistence\Redis;

class RedisMasterFactory
{
    private $sentinel;

    public function __construct(
        Sentinel $sentinel
    ) {
        $this->sentinel = $sentinel;
    }

    public function create(int $dbIndex = 1): \Redis
    {
        $redisConf = $this->sentinel->resolveMaster();
        $redisClient = new \Redis();
        $redisClient->connect(
            $redisConf->getHost(),
            $redisConf->getPort()
        );

        $redisClient->select($dbIndex);

        return $redisClient;
    }
}
