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
            $detailedMessage = $this->extractDuplicateInfo($exception->getMessage());
            
            throw new \DomainException(
                $detailedMessage,
                0,
                $exception
            );
        }
    }

    private function extractDuplicateInfo(string $originalMessage): string
    {
        $mysqlDuplicatePattern = "/duplicate entry '([^']+)' for key '([^']+)'/i";
        
        if (preg_match($mysqlDuplicatePattern, $originalMessage, $matches)) {
            $duplicatedValue = $matches[1];
            $keyName = $matches[2];
            
            return sprintf(
                "A duplicate value has been found: '%s' in %s",
                $duplicatedValue,
                $keyName
            );
        }
        
        return "Duplicated value found";
    }
}
