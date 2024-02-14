<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Service;

use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Ivoz\Core\Domain\Service\CommonPersistErrorHandlerInterface;

class DuplicateEntryCommonErrorHandler implements CommonPersistErrorHandlerInterface
{
    const ON_ERROR_PRIORITY = self::PRIORITY_LOW;

    /*
     * Mysql error code list:
     * https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
     */
    const MYSQL_ERROR_DUPLICATE_ENTRY = 1062;

    public static function getSubscribedEvents()
    {
        return [
            self::EVENT_ON_ERROR => self::ON_ERROR_PRIORITY,
        ];
    }

    public function handle(\Throwable $exception)
    {
        if (!$exception instanceof UniqueConstraintViolationException) {
            return;
        }

        $pdoException = $exception->getPrevious();
        if (!$pdoException instanceof PDOException) {
            return;
        }

        $isDuplicatedError = $pdoException->getCode() === self::MYSQL_ERROR_DUPLICATE_ENTRY;

        if ($isDuplicatedError) {
            preg_match(
                '/Duplicate entry \'[_a-zA-Z0-9]+\' for key \'([_a-zA-Z0-9]+)\'/',
                $exception->getMessage(),
                $results
            );
            $uniqueKey = $results[1] ?? 'unknown';

            throw new \DomainException(
                'Duplicate value on key: ' . $uniqueKey,
                0,
                $exception
            );
        }
    }
}
