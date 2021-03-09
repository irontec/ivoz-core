<?php

namespace Ivoz\Core\Infrastructure\Domain\Service\Gearman;

class FakeManager extends Manager
{
    public static function getClient()
    {
        return new class extends \GearmanClient
        {
            public function doBackground(string $function, string $workload, ?string $unique = NULL): string
            {
                return '';
            }
        };
    }
}
