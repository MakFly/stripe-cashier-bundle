<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableEntityInterface;
use CashierBundle\Contract\BillableInterface;
use CashierBundle\Entity\StripeCustomer as LocalStripeCustomer;
use CashierBundle\Exception\CustomerAlreadyCreatedException;
use CashierBundle\Exception\InvalidCustomerException;
use CashierBundle\Repository\StripeCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Customer as StripeCustomer;

/**
 * @implements \CashierBundle\Concerns\ManagesCustomer<BillableInterface>
 */
class CustomerService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly StripeCustomerRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(BillableInterface $billable, array $options = []): string
    {
        if ($billable->stripeId() !== null) {
            throw new CustomerAlreadyCreatedException('Stripe customer already exists.');
        }

        if (!$billable instanceof BillableEntityInterface) {
            throw new InvalidCustomerException('Billable entity must implement BillableEntityInterface.');
        }

        $payload = array_merge([
            'email' => method_exists($billable, 'getEmail') ? $billable->getEmail() : null,
            'name' => method_exists($billable, 'getName') ? $billable->getName() : null,
            'phone' => method_exists($billable, 'getPhone') ? $billable->getPhone() : null,
            'address' => method_exists($billable, 'getAddress') ? $billable->getAddress() : null,
            'metadata' => [
                'billable_id' => $billable->getId(),
                'billable_type' => $billable::class,
            ],
        ], $options);

        $stripeCustomer = $this->stripe->customers->create($payload);

        $this->sync($stripeCustomer, $billable);

        return $stripeCustomer->id;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function update(BillableInterface $billable, array $options = []): void
    {
        $stripeId = $billable->stripeId();

        if ($stripeId === null) {
            throw new InvalidCustomerException('Stripe customer does not exist.');
        }

        $this->stripe->customers->update($stripeId, $options);

        if ($billable instanceof BillableEntityInterface) {
            $customer = $this->repository->findByBillable($billable);
            if ($customer !== null && method_exists($billable, 'getEmail')) {
                $customer->setEmail($billable->getEmail());
                $this->repository->save($customer, true);
            }
        }
    }

    public function find(string $stripeId): ?LocalStripeCustomer
    {
        return $this->repository->findByStripeId($stripeId);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createOrGetStripeId(BillableInterface $billable, array $options = []): string
    {
        if ($stripeId = $billable->stripeId()) {
            return $stripeId;
        }

        return $this->create($billable, $options);
    }

    public function sync(StripeCustomer $stripeCustomer, ?BillableInterface $billable = null): void
    {
        $customer = $this->repository->findByStripeId($stripeCustomer->id);

        if ($customer === null) {
            $customer = new LocalStripeCustomer();
            $customer->setStripeId($stripeCustomer->id);
        }

        $customer->setName($stripeCustomer->name);
        $customer->setEmail($stripeCustomer->email);
        $customer->setPhone($stripeCustomer->phone);
        $customer->setCurrency($stripeCustomer->currency ?? 'usd');
        $customer->setBalance($stripeCustomer->balance ?? 0);

        if ($stripeCustomer->address !== null) {
            $customer->setAddress($stripeCustomer->address->toArray());
        }

        if ($stripeCustomer->invoice_settings->default_payment_method ?? null) {
            $customer->setPmType($stripeCustomer->invoice_settings->default_payment_method);
        }

        if ($stripeCustomer->invoice_prefix ?? null) {
            $customer->setInvoicePrefix($stripeCustomer->invoice_prefix);
        }

        if ($stripeCustomer->tax_exempt ?? null) {
            $customer->setTaxExempt($stripeCustomer->tax_exempt);
        }

        if ($billable instanceof BillableEntityInterface) {
            $customer->setBillable($billable);
        }

        $this->repository->save($customer, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStripePayload(BillableInterface $billable): array
    {
        $payload = [];

        if (method_exists($billable, 'getEmail')) {
            $payload['email'] = $billable->getEmail();
        }

        if (method_exists($billable, 'getName')) {
            $payload['name'] = $billable->getName();
        }

        if (method_exists($billable, 'getPhone')) {
            $payload['phone'] = $billable->getPhone();
        }

        if (method_exists($billable, 'getAddress')) {
            $payload['address'] = $billable->getAddress();
        }

        return $payload;
    }
}
