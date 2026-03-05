<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Contract\InvoiceStorageInterface;
use CashierBundle\Entity\GeneratedInvoice;
use CashierBundle\Event\PaymentSucceededEvent;
use CashierBundle\Model\Invoice;
use CashierBundle\Repository\GeneratedInvoiceRepository;
use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Service\InvoiceArchiveService;
use CashierBundle\Tests\Support\TestStripeClient;
use CashierBundle\ValueObject\StoredInvoice;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Stripe\Invoice as StripeInvoice;
use Symfony\Component\HttpFoundation\Response;

final class InvoiceArchiveServiceTest extends TestCase
{
    public function testArchiveFromPaymentSuccessPersistsInvoiceWhenMissing(): void
    {
        $stripe = (new TestStripeClient())->withService('invoices', new class () {
            /**
             * @param array<string, mixed> $options
             */
            public function retrieve(string $invoiceId, array $options = []): StripeInvoice
            {
                $invoice = new StripeInvoice($invoiceId);
                $invoice->number = 'INV-2026-100';
                $invoice->status = 'paid';
                $invoice->currency = 'eur';
                $invoice->total = 4999;
                $invoice->payment_intent = 'pi_123';
                $invoice->created = time();
                $invoice->metadata = \Stripe\StripeObject::constructFrom([
                    'app_resource_type' => 'order',
                    'app_resource_id' => '42',
                    'plan_code' => 'starter',
                ]);

                return $invoice;
            }
        });

        $renderer = new class () implements InvoiceRendererInterface {
            public function render(Invoice $invoice, array $data = []): Response
            {
                return new Response('%PDF');
            }

            public function renderBinary(Invoice $invoice, array $data = []): string
            {
                return '%PDF test';
            }

            public function stream(Invoice $invoice, array $data = []): Response
            {
                return new Response('%PDF');
            }
        };

        $storage = $this->createMock(InvoiceStorageInterface::class);
        $storage->expects(self::once())
            ->method('store')
            ->willReturn(new StoredInvoice('/tmp/invoice.pdf', 'var/data/invoices/invoice.pdf', 'invoice.pdf', 'application/pdf', 10, 'hash'));

        $generatedInvoiceRepo = $this->createMock(GeneratedInvoiceRepository::class);
        $generatedInvoiceRepo->expects(self::once())
            ->method('findOneForStripeInvoice')
            ->with('in_123')
            ->willReturn(null);

        $customerRepo = $this->createMock(StripeCustomerRepository::class);
        $customerRepo->expects(self::once())
            ->method('findByStripeId')
            ->with('cus_123')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(GeneratedInvoice::class));
        $entityManager->expects(self::once())->method('flush');

        $service = new InvoiceArchiveService($stripe, $renderer, $storage, $customerRepo, $generatedInvoiceRepo, $entityManager);

        $generatedInvoice = $service->archiveFromPaymentSuccess(new PaymentSucceededEvent(
            'cus_123',
            'pi_123',
            4999,
            'eur',
            'in_123',
        ));

        self::assertInstanceOf(GeneratedInvoice::class, $generatedInvoice);
        self::assertSame('in_123', $generatedInvoice->getStripeInvoiceId());
        self::assertSame('invoice.pdf', $generatedInvoice->getFilename());
        self::assertSame('order', $generatedInvoice->getResourceType());
        self::assertSame('42', $generatedInvoice->getResourceId());
        self::assertSame('starter', $generatedInvoice->getPlanCode());
    }

    public function testArchiveFromPaymentSuccessFallsBackToSubscriptionMetadataWhenInvoiceMetadataIsEmpty(): void
    {
        $stripe = (new TestStripeClient())->withService('invoices', new class () {
            /**
             * @param array<string, mixed> $options
             */
            public function retrieve(string $invoiceId, array $options = []): StripeInvoice
            {
                $invoice = new StripeInvoice($invoiceId);
                $invoice->number = 'INV-2026-200';
                $invoice->status = 'paid';
                $invoice->currency = 'eur';
                $invoice->total = 1999;
                $invoice->payment_intent = 'pi_sub_123';
                $invoice->created = time();
                $invoice->metadata = \Stripe\StripeObject::constructFrom([]);
                /** @phpstan-ignore-next-line test fixture uses Stripe dynamic properties */
                $invoice->parent = \Stripe\StripeObject::constructFrom([
                    'subscription_details' => [
                        'metadata' => [
                            'app_resource_type' => 'subscription_plan',
                            'app_resource_id' => 'pro',
                            'plan_code' => 'pro',
                        ],
                    ],
                ]);

                return $invoice;
            }
        });

        $renderer = new class () implements InvoiceRendererInterface {
            public function render(Invoice $invoice, array $data = []): Response
            {
                return new Response('%PDF');
            }

            public function renderBinary(Invoice $invoice, array $data = []): string
            {
                return '%PDF test';
            }

            public function stream(Invoice $invoice, array $data = []): Response
            {
                return new Response('%PDF');
            }
        };

        $storage = $this->createMock(InvoiceStorageInterface::class);
        $storage->method('store')
            ->willReturn(new StoredInvoice('/tmp/subscription.pdf', 'var/data/invoices/subscription.pdf', 'subscription.pdf', 'application/pdf', 10, 'hash'));

        $generatedInvoiceRepo = $this->createMock(GeneratedInvoiceRepository::class);
        $generatedInvoiceRepo->method('findOneForStripeInvoice')->willReturn(null);

        $customerRepo = $this->createMock(StripeCustomerRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(GeneratedInvoice::class));
        $entityManager->expects(self::once())->method('flush');

        $service = new InvoiceArchiveService($stripe, $renderer, $storage, $customerRepo, $generatedInvoiceRepo, $entityManager);

        $generatedInvoice = $service->archiveFromPaymentSuccess(new PaymentSucceededEvent(
            'cus_123',
            'pi_sub_123',
            1999,
            'eur',
            'in_sub_123',
        ));

        self::assertInstanceOf(GeneratedInvoice::class, $generatedInvoice);
        self::assertSame('subscription_plan', $generatedInvoice->getResourceType());
        self::assertSame('pro', $generatedInvoice->getResourceId());
        self::assertSame('pro', $generatedInvoice->getPlanCode());
    }

    public function testArchiveFromPaymentSuccessReturnsExistingInvoiceWithoutWritingAgain(): void
    {
        $existing = (new GeneratedInvoice())
            ->setStripeInvoiceId('in_existing')
            ->setFilename('existing.pdf')
            ->setRelativePath('var/data/invoices/existing.pdf')
            ->setMimeType('application/pdf')
            ->setSize(1)
            ->setChecksum('hash')
            ->setCurrency('eur')
            ->setAmountTotal(100)
            ->setStatus('paid');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $generatedInvoiceRepo = $this->createMock(GeneratedInvoiceRepository::class);
        $generatedInvoiceRepo->expects(self::once())
            ->method('findOneForStripeInvoice')
            ->with('in_existing')
            ->willReturn($existing);

        $service = new InvoiceArchiveService(
            new TestStripeClient(),
            new class () implements InvoiceRendererInterface {
                public function render(Invoice $invoice, array $data = []): Response
                {
                    return new Response();
                }

                public function renderBinary(Invoice $invoice, array $data = []): string
                {
                    return '';
                }

                public function stream(Invoice $invoice, array $data = []): Response
                {
                    return new Response();
                }
            },
            $this->createMock(InvoiceStorageInterface::class),
            $this->createMock(StripeCustomerRepository::class),
            $generatedInvoiceRepo,
            $entityManager,
        );

        $result = $service->archiveFromPaymentSuccess(new PaymentSucceededEvent('cus_123', 'pi_123', 100, 'eur', 'in_existing'));

        self::assertSame($existing, $result);
    }
}
