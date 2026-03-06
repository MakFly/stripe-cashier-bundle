<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $debug = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        $previous = $exception->getPrevious();
        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $this->logger->log(
            $status >= 500 ? 'error' : 'warning',
            sprintf(
                'API exception on %s %s: %s%s',
                $request->getMethod(),
                $request->getPathInfo(),
                $exception->getMessage(),
                $previous instanceof \Throwable ? sprintf(' | caused by %s: %s', $previous::class, $previous->getMessage()) : '',
            ),
            [
                'exception' => $exception,
                'exception_class' => $exception::class,
                'status' => $status,
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'previous_exception_class' => $previous instanceof \Throwable ? $previous::class : null,
                'previous_message' => $previous?->getMessage(),
            ],
        );

        $payload = [
            '@context' => '/api/v1/contexts/Error',
            '@type' => 'hydra:Error',
            'hydra:title' => Response::$statusTexts[$status] ?? 'Error',
            'hydra:description' => $exception instanceof HttpExceptionInterface
                ? $exception->getMessage()
                : 'An unexpected error occurred',
            'status' => $status,
        ];

        if ($this->debug) {
            $payload['exception'] = $exception::class;
            $payload['previous'] = $previous instanceof \Throwable ? $previous::class : null;
        }

        $event->setResponse(new JsonResponse($payload, $status));
    }
}
