<?php

declare(strict_types=1);

namespace CashierBundle\Webhook;

use CashierBundle\Contract\WebhookHandlerInterface;
use CashierBundle\Event\WebhookHandledEvent;
use CashierBundle\Event\WebhookReceivedEvent;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class WebhookProcessor
{
    /**
     * @param ContainerInterface $handlers Locator of webhook handlers tagged with 'cashier.webhook_handler'
     */
    public function __construct(
        #[AutowireLocator('cashier.webhook_handler')]
        private ContainerInterface $handlers,
        private EventDispatcherInterface $dispatcher,
        private string $webhookSecret,
        private int $tolerance = 300,
    ) {
    }

    public function process(string $payload, ?string $signature): void
    {
        if ($payload === '') {
            throw new \InvalidArgumentException('Webhook payload cannot be empty.');
        }

        if ($signature === null || trim($signature) === '') {
            throw new \InvalidArgumentException('Stripe signature header is required.');
        }

        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            $this->webhookSecret,
            $this->tolerance,
        );

        // Dispatch WebhookReceivedEvent
        $this->dispatcher->dispatch(new WebhookReceivedEvent($event));

        // Find and execute handlers
        $eventType = $event->type;
        foreach ($this->getHandlersForType($eventType) as $handler) {
            $handler->handle($event);
        }

        // Dispatch WebhookHandledEvent
        $this->dispatcher->dispatch(new WebhookHandledEvent($event));
    }

    /**
     * @return iterable<WebhookHandlerInterface>
     */
    private function getHandlersForType(string $type): iterable
    {
        foreach ($this->getHandlerServiceIds() as $serviceId) {
            if (!$this->handlers->has($serviceId)) {
                continue;
            }

            $handler = $this->handlers->get($serviceId);
            if (!$handler instanceof WebhookHandlerInterface) {
                continue;
            }

            if (in_array($type, $handler->handles(), true)) {
                yield $handler;
            }
        }
    }

    /**
     * @return iterable<string>
     */
    private function getHandlerServiceIds(): iterable
    {
        if (method_exists($this->handlers, 'getProvidedServices')) {
            /** @var array<string, mixed> $providedServices */
            $providedServices = $this->handlers->getProvidedServices();

            return array_keys($providedServices);
        }

        if (method_exists($this->handlers, 'getServiceIds')) {
            /** @var iterable<string> $serviceIds */
            $serviceIds = $this->handlers->getServiceIds();

            return $serviceIds;
        }

        return [];
    }
}
