<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Model;

use CashierBundle\Model\Payment;
use PHPUnit\Framework\TestCase;

/** Test suite for Payment. */
final class PaymentTest extends TestCase
{
    private function createPaymentIntent(array $data = []): object
    {
        return new class ($data) {
            public string $id;
            public int $amount;
            public string $currency;
            public string $status;
            public ?string $client_secret;

            public function __construct(array $data)
            {
                $this->id = $data['id'] ?? 'pi_test_123';
                $this->amount = $data['amount'] ?? 1000;
                $this->currency = $data['currency'] ?? 'usd';
                $this->status = $data['status'] ?? 'succeeded';
                $this->client_secret = $data['client_secret'] ?? 'secret_123';
            }

            public function capture(): void
            {
            }
            public function cancel(): void
            {
            }
        };
    }

    public function testIdReturnsCorrectValue(): void
    {
        $intent = $this->createPaymentIntent(['id' => 'pi_test_456']);
        $payment = new Payment($intent);

        $this->assertEquals('pi_test_456', $payment->id());
    }

    public function testRawAmountReturnsCents(): void
    {
        $intent = $this->createPaymentIntent(['amount' => 2500]);
        $payment = new Payment($intent);

        $this->assertEquals(2500, $payment->rawAmount());
    }

    public function testCurrencyReturnsCorrectValue(): void
    {
        $intent = $this->createPaymentIntent(['currency' => 'eur']);
        $payment = new Payment($intent);

        $this->assertEquals('eur', $payment->currency());
    }

    public function testClientSecretReturnsCorrectValue(): void
    {
        $intent = $this->createPaymentIntent(['client_secret' => 'cs_test_789']);
        $payment = new Payment($intent);

        $this->assertEquals('cs_test_789', $payment->clientSecret());
    }

    public function testStatusReturnsCorrectValue(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'processing']);
        $payment = new Payment($intent);

        $this->assertEquals('processing', $payment->status());
    }

    public function testIsSucceededReturnsTrueWhenSucceeded(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'succeeded']);
        $payment = new Payment($intent);

        $this->assertTrue($payment->isSucceeded());
    }

    public function testIsSucceededReturnsFalseWhenNotSucceeded(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'processing']);
        $payment = new Payment($intent);

        $this->assertFalse($payment->isSucceeded());
    }

    public function testRequiresPaymentMethodReturnsTrue(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'requires_payment_method']);
        $payment = new Payment($intent);

        $this->assertTrue($payment->requiresPaymentMethod());
    }

    public function testRequiresActionReturnsTrue(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'requires_action']);
        $payment = new Payment($intent);

        $this->assertTrue($payment->requiresAction());
    }

    public function testRequiresConfirmationReturnsTrue(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'requires_confirmation']);
        $payment = new Payment($intent);

        $this->assertTrue($payment->requiresConfirmation());
    }

    public function testRequiresCaptureReturnsTrue(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'requires_capture']);
        $payment = new Payment($intent);

        $this->assertTrue($payment->requiresCapture());
    }

    public function testIsCanceledReturnsTrue(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'canceled']);
        $payment = new Payment($intent);

        $this->assertTrue($payment->isCanceled());
    }

    public function testIsProcessingReturnsTrue(): void
    {
        $intent = $this->createPaymentIntent(['status' => 'processing']);
        $payment = new Payment($intent);

        $this->assertTrue($payment->isProcessing());
    }

    public function testAsStripePaymentIntentReturnsOriginal(): void
    {
        $intent = $this->createPaymentIntent();
        $payment = new Payment($intent);

        $this->assertSame($intent, $payment->asStripePaymentIntent());
    }
}
