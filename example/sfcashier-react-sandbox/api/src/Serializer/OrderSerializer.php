<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Order;
use App\Entity\OrderItem;
use CashierBundle\Entity\GeneratedInvoice;
use CashierBundle\Repository\GeneratedInvoiceRepository;

class OrderSerializer
{
    public function __construct(
        private readonly GeneratedInvoiceRepository $generatedInvoiceRepository,
    ) {}

    public function serialize(Order $order, bool $withItems = false): array
    {
        $base = [
            '@id' => '/api/v1/orders/' . $order->getId(),
            '@type' => 'Order',
            'id' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'userId' => $order->getUser()?->getId(),
            'total' => $order->getTotal(),
            'status' => $order->getStatus()->value,
            'createdAt' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'itemCount' => $order->getItems()->count(),
            'detailPath' => sprintf('/orders/%d/%d', $order->getUser()?->getId(), $order->getId()),
            'stripeCheckoutSessionId' => $order->getStripeCheckoutSessionId(),
        ];

        $invoice = $this->resolveInvoiceForOrder($order);
        if ($invoice instanceof GeneratedInvoice) {
            $base['invoice'] = $this->serializeInvoice($invoice);
        }

        if ($withItems) {
            $base['items'] = $order->getItems()->map(
                fn (OrderItem $item): array => $this->serializeOrderItem($item)
            )->toArray();
        }

        return $base;
    }

    private function resolveInvoiceForOrder(Order $order): ?GeneratedInvoice
    {
        $user = $order->getUser();
        if (!$user instanceof \App\Entity\User) {
            return null;
        }

        $paymentIntentId = $order->getStripePaymentIntentId();
        if ($paymentIntentId !== null && $paymentIntentId !== '') {
            $invoice = $this->generatedInvoiceRepository->findOneBy([
                'stripePaymentIntentId' => $paymentIntentId,
            ]);
            if ($invoice instanceof GeneratedInvoice) {
                return $invoice;
            }
        }

        $checkoutSessionId = $order->getStripeCheckoutSessionId();
        if ($checkoutSessionId !== null && $checkoutSessionId !== '') {
            $invoice = $this->generatedInvoiceRepository->findOneBy([
                'stripeCheckoutSessionId' => $checkoutSessionId,
            ]);
            if ($invoice instanceof GeneratedInvoice) {
                return $invoice;
            }
        }

        $orderId = $order->getId();
        if ($orderId !== null) {
            $invoice = $this->generatedInvoiceRepository->findOneBy([
                'resourceType' => 'order',
                'resourceId' => (string) $orderId,
            ]);
            if ($invoice instanceof GeneratedInvoice) {
                return $invoice;
            }
        }

        return null;
    }

    private function serializeOrderItem(OrderItem $item): array
    {
        return [
            'id' => $item->getId(),
            'productId' => $item->getProductId(),
            'productName' => $item->getProductName(),
            'productSlug' => $item->getProductSlug(),
            'productDescription' => $item->getProductDescription(),
            'productImageUrl' => $item->getProductImageUrl(),
            'unitPrice' => $item->getUnitPrice(),
            'quantity' => $item->getQuantity(),
            'subtotal' => $item->getSubtotal(),
        ];
    }

    private function serializeInvoice(GeneratedInvoice $invoice): array
    {
        return [
            'id' => $invoice->getId(),
            'stripeInvoiceId' => $invoice->getStripeInvoiceId(),
            'filename' => $invoice->getFilename(),
            'relativePath' => $invoice->getRelativePath(),
            'mimeType' => $invoice->getMimeType(),
            'size' => $invoice->getSize(),
            'status' => $invoice->getStatus(),
            'amountTotal' => $invoice->getAmountTotal(),
            'currency' => $invoice->getCurrency(),
            'createdAt' => $invoice->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'downloadPath' => sprintf('/api/v1/orders/%d/invoice/download', $invoice->getId()),
            'hostedInvoiceUrl' => $invoice->getPayload()['hosted_invoice_url'] ?? null,
        ];
    }
}
