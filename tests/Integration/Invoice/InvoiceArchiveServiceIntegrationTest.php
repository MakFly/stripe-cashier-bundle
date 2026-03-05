<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Integration\Invoice;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Entity\GeneratedInvoice;
use CashierBundle\Entity\StripeCustomer;
use CashierBundle\Event\PaymentSucceededEvent;
use CashierBundle\Model\Invoice;
use CashierBundle\Repository\GeneratedInvoiceRepository;
use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Service\InvoiceArchiveService;
use CashierBundle\Service\InvoiceStorage\LocalInvoiceStorage;
use CashierBundle\Tests\Support\TestManagerRegistry;
use CashierBundle\Tests\Support\TestStripeClient;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Stripe\Invoice as StripeInvoice;
use Symfony\Component\HttpFoundation\Response;

final class InvoiceArchiveServiceIntegrationTest extends TestCase
{
    private EntityManager $entityManager;
    private string $storagePath;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([
            __DIR__ . '/../../../src/Entity',
        ], true);
        $config->enableNativeLazyObjects(true);

        $connection = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $this->entityManager = new EntityManager(DriverManager::getConnection($connection, $config), $config);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        $this->storagePath = sys_get_temp_dir() . '/cashier_invoice_archive_' . bin2hex(random_bytes(8)) . '/var/data/invoices';
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        $this->removeDirectory(dirname(dirname($this->storagePath)));
    }

    public function testArchiveFlowPersistsDatabaseRowAndPdfOnlyOnce(): void
    {
        $customer = (new StripeCustomer())
            ->setStripeId('cus_123')
            ->setEmail('alice@example.test')
            ->setName('Alice');
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        $stripe = (new TestStripeClient())->withService('invoices', new class () {
            /**
             * @param array<string, mixed> $options
             */
            public function retrieve(string $invoiceId, array $options = []): StripeInvoice
            {
                $invoice = new StripeInvoice($invoiceId);
                $invoice->number = 'INV-2026-777';
                $invoice->status = 'paid';
                $invoice->currency = 'eur';
                $invoice->total = 12999;
                $invoice->subtotal = 12999;
                $invoice->created = time();
                $invoice->customer = 'cus_123';
                $invoice->payment_intent = 'pi_777';

                return $invoice;
            }
        });

        $renderer = new class () implements InvoiceRendererInterface {
            public function render(Invoice $invoice, array $data = []): Response
            {
                return new Response('%PDF integration');
            }

            public function renderBinary(Invoice $invoice, array $data = []): string
            {
                return '%PDF integration';
            }

            public function stream(Invoice $invoice, array $data = []): Response
            {
                return new Response('%PDF integration');
            }
        };

        $service = new InvoiceArchiveService(
            $stripe,
            $renderer,
            new LocalInvoiceStorage($this->storagePath),
            new StripeCustomerRepository(new TestManagerRegistry($this->entityManager, $this->entityManager->getConnection())),
            new GeneratedInvoiceRepository(new TestManagerRegistry($this->entityManager, $this->entityManager->getConnection())),
            $this->entityManager,
        );

        $event = new PaymentSucceededEvent('cus_123', 'pi_777', 12999, 'eur', 'in_777');

        $first = $service->archiveFromPaymentSuccess($event);
        $second = $service->archiveFromPaymentSuccess($event);

        self::assertInstanceOf(GeneratedInvoice::class, $first);
        self::assertSame($first->getId(), $second?->getId());
        self::assertSame(
            1,
            (int) $this->entityManager
                ->createQueryBuilder()
                ->select('COUNT(invoice.id)')
                ->from(GeneratedInvoice::class, 'invoice')
                ->getQuery()
                ->getSingleScalarResult(),
        );
        self::assertFileExists($this->storagePath . '/INV-2026-777.pdf');
        self::assertSame('%PDF integration', (string) file_get_contents($this->storagePath . '/INV-2026-777.pdf'));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path . '/' . $entry;
            if (is_dir($entryPath)) {
                $this->removeDirectory($entryPath);
                continue;
            }

            unlink($entryPath);
        }

        rmdir($path);
    }
}
