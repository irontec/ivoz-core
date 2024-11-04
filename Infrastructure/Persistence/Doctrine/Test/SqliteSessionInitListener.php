<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Test;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Platforms\SqlitePlatform;

class SqliteSessionInitListener
{
    public function postConnect(ConnectionEventArgs $args): void
    {
        $disableFk = $_ENV['DISABLE_FK'] ?? false;

        if ($disableFk) {
            return;
        }

        $connection = $args->getConnection();
        $isSqlite = $connection->getDatabasePlatform() instanceof SqlitePlatform;

        // Check if we're on SQLite
        if ($isSqlite) {
            $connection->executeStatement('PRAGMA foreign_keys = ON');
        }
    }
}
