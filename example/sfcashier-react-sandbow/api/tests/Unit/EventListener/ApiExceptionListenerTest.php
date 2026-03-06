<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ApiExceptionListener;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiExceptionListenerTest extends TestCase
{
    public function testItFormatsApiExceptionsAsHydraJson(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('log')
            ->with(
                'warning',
                self::stringContains('Invalid payload'),
                self::callback(static function (array $context): bool {
                    return $context['exception_class'] === BadRequestHttpException::class
                        && $context['previous_exception_class'] === null
                        && array_key_exists('exception', $context);
                }),
            );

        $listener = new ApiExceptionListener($logger, false);
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create('/api/v1/orders/checkout/session');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new BadRequestHttpException('Invalid payload'));

        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(400, $response->getStatusCode());

        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('hydra:Error', $payload['@type']);
        self::assertSame('Invalid payload', $payload['hydra:description']);
        self::assertSame(400, $payload['status']);
    }

    public function testItIgnoresNonApiRoutes(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('log');

        $listener = new ApiExceptionListener($logger, false);
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create('/login');
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new BadRequestHttpException('Invalid payload'));

        $listener->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function testItLogsPreviousExceptionContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('log')
            ->with(
                'error',
                self::stringContains('caused by RuntimeException: Stripe says no'),
                self::callback(static function (array $context): bool {
                    return $context['previous_exception_class'] === \RuntimeException::class
                        && $context['previous_message'] === 'Stripe says no';
                }),
            );

        $listener = new ApiExceptionListener($logger, true);
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create('/api/v1/orders/checkout/session');
        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new \Symfony\Component\HttpKernel\Exception\HttpException(502, 'Unable to create checkout session', new \RuntimeException('Stripe says no')),
        );

        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);

        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Symfony\\Component\\HttpKernel\\Exception\\HttpException', $payload['exception']);
        self::assertSame(\RuntimeException::class, $payload['previous']);
    }
}
