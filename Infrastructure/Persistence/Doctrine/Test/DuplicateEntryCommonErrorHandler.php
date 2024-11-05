<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Test;

use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ivoz\Core\Domain\Service\CommonPersistErrorHandlerInterface;

class DuplicateEntryCommonErrorHandler implements CommonPersistErrorHandlerInterface
{
    const ON_ERROR_PRIORITY = self::PRIORITY_LOW;

    const SQLITE_ERROR_UNIQUE_KEY = 'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed';

    public static function getSubscribedEvents()
    {
        return [
            self::EVENT_ON_ERROR => self::ON_ERROR_PRIORITY,
        ];
    }

    public function handle(\Throwable $exception)
    {
        $pdoException = $exception->getPrevious();
        if (!$pdoException instanceof PDOException) {
            return;
        }

        $isDuplicatedError = str_starts_with(
            $pdoException->getMessage(),
        self::SQLITE_ERROR_UNIQUE_KEY
        );

        if ($isDuplicatedError) {
            throw new \DomainException(
                'Duplicated value found',
                0,
                $exception
            );
        }
    }
}
