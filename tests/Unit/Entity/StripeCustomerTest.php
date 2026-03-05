<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Entity;

use CashierBundle\Entity\StripeCustomer;
use CashierBundle\Entity\Subscription;
use CashierBundle\Contract\BillableEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class StripeCustomerTest extends TestCase
{
    private function createStripeCustomer(array $data = []): StripeCustomer
    {
        $customer = new StripeCustomer();

        $customer->setStripeId($data['stripeId'] ?? 'cus_test_123');
        $customer->setPmType($data['pmType'] ?? 'card');
        $customer->setPmLastFour($data['pmLastFour'] ?? '4242');

        if (isset($data['trialEndsAt'])) {
            $customer->setTrialEndsAt($data['trialEndsAt']);
        }

        return $customer;
    }

    public function testStripeIdReturnsCorrectValue(): void
    {
        $customer = $this->createStripeCustomer(['stripeId' => 'cus_abc']);
        $this->assertEquals('cus_abc', $customer->getStripeId());
    }

    public function testPmTypeReturnsCorrectValue(): void
    {
        $customer = $this->createStripeCustomer(['pmType' => 'sepa_debit']);
        $this->assertEquals('sepa_debit', $customer->getPmType());
    }

    public function testPmLastFourReturnsCorrectValue(): void
    {
        $customer = $this->createStripeCustomer(['pmLastFour' => '1234']);
        $this->assertEquals('1234', $customer->getPmLastFour());
    }

    public function testOnTrialReturnsTrueWhenTrialEndsAtIsInFuture(): void
    {
        $customer = $this->createStripeCustomer([
            'trialEndsAt' => new \DateTimeImmutable('+7 days')
        ]);

        $this->assertTrue($customer->onTrial());
    }

    public function testOnTrialReturnsFalseWhenTrialEndsAtIsInPast(): void
    {
        $customer = $this->createStripeCustomer([
            'trialEndsAt' => new \DateTimeImmutable('-1 day')
        ]);

        $this->assertFalse($customer->onTrial());
    }

    public function testOnTrialReturnsFalseWhenTrialEndsAtIsNull(): void
    {
        $customer = $this->createStripeCustomer();
        $this->assertFalse($customer->onTrial());
    }

    public function testOnGenericTrialReturnsTrueWhenTrialEndsAtIsInFuture(): void
    {
        $customer = $this->createStripeCustomer([
            'trialEndsAt' => new \DateTimeImmutable('+7 days')
        ]);

        $this->assertTrue($customer->onGenericTrial());
    }

    public function testSubscriptionsCollectionIsInitialized(): void
    {
        $customer = new StripeCustomer();
        $this->assertInstanceOf(ArrayCollection::class, $customer->getSubscriptions());
    }

    public function testCreatedAtIsInitialized(): void
    {
        $customer = new StripeCustomer();
        $this->assertInstanceOf(\DateTimeImmutable::class, $customer->getCreatedAt());
    }

    public function testUpdatedAtIsInitialized(): void
    {
        $customer = new StripeCustomer();
        $this->assertInstanceOf(\DateTimeImmutable::class, $customer->getUpdatedAt());
    }
}
