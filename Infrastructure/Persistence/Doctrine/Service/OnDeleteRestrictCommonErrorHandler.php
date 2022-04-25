<?php

namespace Ivoz\Core\Infrastructure\Persistence\Doctrine\Service;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Ivoz\Core\Domain\Service\CommonPersistErrorHandlerInterface;

class OnDeleteRestrictCommonErrorHandler implements CommonPersistErrorHandlerInterface
{
    const ON_ERROR_PRIORITY = self::PRIORITY_LOW;

    public static function getSubscribedEvents()
    {
        return [
            self::EVENT_ON_ERROR => self::ON_ERROR_PRIORITY,
        ];
    }

    public function handle(\Throwable $exception)
    {
        if (!$exception instanceof ForeignKeyConstraintViolationException) {
            return;
        }

        preg_match(
            '/\(`[^`]+`\.`([^`]+)`/',
            $exception->getMessage(),
            $results
        );
        $entity = $results[1] ?? 'unknown';


        throw new \DomainException(
            'Unable delete this element, due to is being used by '. $entity,
            0,
            $exception
        );
    }
}
