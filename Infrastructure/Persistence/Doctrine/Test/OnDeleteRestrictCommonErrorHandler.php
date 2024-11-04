<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Test;

use Doctrine\DBAL\Driver\PDO\Exception as PDOException;
use Ivoz\Core\Domain\Service\CommonPersistErrorHandlerInterface;

class OnDeleteRestrictCommonErrorHandler implements CommonPersistErrorHandlerInterface
{
    const ON_ERROR_PRIORITY = self::PRIORITY_LOW;

    private const SQLITE_INTEGRITY_CONSTRAINT_VIOLATION =
        'SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed';

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

        $isFkViolation = $pdoException->getMessage() === self::SQLITE_INTEGRITY_CONSTRAINT_VIOLATION;
        if (!$isFkViolation) {
            return;
        }

        throw new \DomainException(
            'Unable delete this element, due to is being used by unknown',
            0,
            $exception
        );
    }
}
