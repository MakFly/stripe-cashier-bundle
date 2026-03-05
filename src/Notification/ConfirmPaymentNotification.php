<?php

declare(strict_types=1);

namespace CashierBundle\Notification;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Model\Payment;

/**
 * Notification sent to users when a payment requires additional confirmation (SCA).
 */
class ConfirmPaymentNotification
{
    public function __construct(
        private readonly BillableInterface $billable,
        private readonly Payment $payment,
    ) {
    }

    public function billable(): BillableInterface
    {
        return $this->billable;
    }

    public function payment(): Payment
    {
        return $this->payment;
    }

    /**
     * Get the notification channels.
     *
     * @return array<string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return array{subject: string, from: array{address: string, name: string}, to: string, html: string, text: string}
     */
    public function toMail(): array
    {
        $amount = $this->payment->amount();
        $currency = strtoupper($this->payment->currency());

        return [
            'subject' => 'Confirm your payment',
            'from' => [
                'address' => 'noreply@example.com',
                'name' => config('cashier.mail.from_name', 'Your Company'),
            ],
            'to' => $this->billable->stripeId(),
            'html' => $this->htmlContent($amount, $currency),
            'text' => $this->textContent($amount, $currency),
        ];
    }

    /**
     * Get the HTML content of the notification.
     */
    private function htmlContent(string $amount, string $currency): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Confirm your payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        h1 {
            color: #0570de;
        }
        .button {
            display: inline-block;
            background: #0570de;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Confirm your payment</h1>

    <div class="container">
        <p>Hello,</p>

        <p>We need you to confirm your payment of <span class="amount">{$amount} {$currency}</span>.</p>

        <p>This additional step is required by your bank to verify the payment for security reasons (Strong Customer Authentication).</p>

        <p><a href="{$this->getConfirmationUrl()}" class="button">Confirm Payment</a></p>

        <p>Or copy and paste this link into your browser:</p>
        <p>{$this->getConfirmationUrl()}</p>

        <p><strong>This link will expire in 24 hours.</strong></p>

        <p>If you didn't initiate this payment, please ignore this email.</p>
    </div>

    <p style="color: #999; font-size: 12px;">
        This is an automated email, please do not reply.
    </p>
</body>
</html>
HTML;
    }

    /**
     * Get the plain text content of the notification.
     */
    private function textContent(string $amount, string $currency): string
    {
        return <<<TEXT
Confirm your payment

Hello,

We need you to confirm your payment of {$amount} {$currency}.

This additional step is required by your bank to verify the payment for security reasons (Strong Customer Authentication).

Click the link below to confirm your payment:

{$this->getConfirmationUrl()}

This link will expire in 24 hours.

If you didn't initiate this payment, please ignore this email.

---
This is an automated email, please do not reply.
TEXT;
    }

    /**
     * Get the payment confirmation URL.
     */
    private function getConfirmationUrl(): string
    {
        // This should be generated using Symfony's router
        // In a real implementation, inject UrlGeneratorInterface or use dependency injection
        return sprintf(
            '%s/cashier/payment/%s',
            config('app.url', 'http://localhost'),
            $this->payment->id(),
        );
    }
}
