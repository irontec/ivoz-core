<?php

namespace Ivoz\Core\Infrastructure\Persistence\Redis;

class FakeRedisMasterFactory extends RedisMasterFactory
{
    public function create(int $dbIndex = 1): \Redis
    {
        $fakeRedis = new Class extends \Redis {
            public function connect(
                $host,
                $port = 6379,
                $timeout = 0.0,
                $reserved = null,
                $retryInterval = 0,
                $readTimeout = 0.0
            ) {}
        };

        return new $fakeRedis;
    }
}
