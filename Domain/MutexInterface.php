<?php

namespace Ivoz\Core\Domain;

interface MutexInterface
{
    /**
     * @param string $lockKey
     * @param int $lockTimeout in seconds
     */
    public function lock(string $lockKey, int $lockTimeout = 1800);

    public function release();
}
