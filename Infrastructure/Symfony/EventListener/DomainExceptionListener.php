<?php

namespace Ivoz\Core\Infrastructure\Symfony\EventListener;

use Assert\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class DomainExceptionListener
{
    /**
     * @param ExceptionEvent $event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $exceptionClass = get_class($exception);
        $publicExceptions = [
            \DomainException::class,
            InvalidArgumentException::class
        ];

        if (!in_array($exceptionClass, $publicExceptions)) {
            return;
        }

        $exceptionCode = $exception->getCode()
            ? $exception->getCode()
            : Response::HTTP_FAILED_DEPENDENCY;

        $event->setResponse(new Response(
            $exception->getMessage(),
            $exceptionCode,
            [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'deny',
            ]
        ));
        $event->stopPropagation();
    }
}
