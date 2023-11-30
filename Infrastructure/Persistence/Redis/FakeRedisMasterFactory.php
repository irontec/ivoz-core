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
                $readTimeout = 0.0,
                $context = null
            ) {
                return false;
            }

            public function lPush($key, ...$value1) { return false; }

            public function rPush($key, ...$value1) { return false; }

            public function blPop($key, $timeout_or_key, ...$extra_args) { return []; }

            public function scan(&$iterator, $pattern = null, $count = 0, ...$extra_args)
            {
                $iterator = 0;
                return ['something'];
            }
        };

        return new $fakeRedis;
    }
}
