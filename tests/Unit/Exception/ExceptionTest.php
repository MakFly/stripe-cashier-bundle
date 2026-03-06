<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Exception;

use CashierBundle\Exception\CustomerAlreadyCreatedException;
use CashierBundle\Exception\IncompletePaymentException;
use CashierBundle\Exception\InvalidCouponException;
use CashierBundle\Exception\InvalidCustomerException;
use CashierBundle\Exception\InvalidInvoiceException;
use CashierBundle\Exception\InvalidPaymentMethodException;
use CashierBundle\Exception\SubscriptionUpdateFailureException;
use CashierBundle\Model\Payment;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentIntent;

/** Test suite for Cashier exception classes. */
final class ExceptionTest extends TestCase
{
    public function testCustomerAlreadyCreatedException(): void
    {
        $exception = CustomerAlreadyCreatedException::create('cus_123');

        $this->assertStringContainsString('cus_123', $exception->getMessage());
    }

    public function testInvalidCouponException(): void
    {
        $exception = InvalidCouponException::invalid('COUPON_XYZ');

        $this->assertStringContainsString('COUPON_XYZ', $exception->getMessage());
    }

    public function testInvalidCustomerExceptionNotYetCreated(): void
    {
        $exception = InvalidCustomerException::notYetCreated();

        $this->assertStringContainsString('not been created', $exception->getMessage());
    }

    public function testInvalidCustomerExceptionInvalidId(): void
    {
        $exception = InvalidCustomerException::invalidId('cus_invalid');

        $this->assertStringContainsString('cus_invalid', $exception->getMessage());
    }

    public function testInvalidInvoiceExceptionInvalid(): void
    {
        $exception = InvalidInvoiceException::invalid('in_123');

        $this->assertStringContainsString('in_123', $exception->getMessage());
    }

    public function testInvalidInvoiceExceptionNotBelongToCustomer(): void
    {
        $exception = InvalidInvoiceException::notBelongToCustomer('in_123', 'cus_456');

        $this->assertStringContainsString('in_123', $exception->getMessage());
        $this->assertStringContainsString('cus_456', $exception->getMessage());
    }

    public function testInvalidPaymentMethodException(): void
    {
        $exception = InvalidPaymentMethodException::invalid('pm_invalid');

        $this->assertStringContainsString('pm_invalid', $exception->getMessage());
    }

    public function testSubscriptionUpdateFailureException(): void
    {
        $exception = SubscriptionUpdateFailureException::create('Price not found');

        $this->assertStringContainsString('Price not found', $exception->getMessage());
    }

    public function testIncompletePaymentExceptionReturnsPayment(): void
    {
        $intent = $this->createMock(PaymentIntent::class);
        $intent->id = 'pi_test';
        $intent->amount = 1000;
        $intent->currency = 'usd';
        $intent->status = 'requires_action';
        $intent->client_secret = 'secret';

        $payment = new Payment($intent);
        $exception = new IncompletePaymentException($payment);

        $this->assertSame($payment, $exception->payment());
        $this->assertStringContainsString('additional action', $exception->getMessage());
    }
}
