<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Model;

use CashierBundle\Model\Coupon;
use PHPUnit\Framework\TestCase;

/** Test suite for Coupon. */
final class CouponTest extends TestCase
{
    private function createCoupon(array $data = []): object
    {
        return new class ($data) {
            public string $id;
            public ?string $name;
            public ?float $percent_off;
            public ?int $amount_off;
            public ?string $currency;
            public string $duration;
            public ?int $duration_in_months;
            public bool $valid;

            public function __construct(array $data)
            {
                $this->id = $data['id'] ?? 'coupon_test_123';
                $this->name = $data['name'] ?? 'Test Coupon';
                $this->percent_off = $data['percent_off'] ?? null;
                $this->amount_off = $data['amount_off'] ?? null;
                $this->currency = $data['currency'] ?? 'usd';
                $this->duration = $data['duration'] ?? 'once';
                $this->duration_in_months = $data['duration_in_months'] ?? null;
                $this->valid = $data['valid'] ?? true;
            }
        };
    }

    public function testIdReturnsCorrectValue(): void
    {
        $stripeCoupon = $this->createCoupon(['id' => 'coupon_abc']);
        $coupon = new Coupon($stripeCoupon);

        $this->assertEquals('coupon_abc', $coupon->id());
    }

    public function testNameReturnsCorrectValue(): void
    {
        $stripeCoupon = $this->createCoupon(['name' => 'Summer Sale']);
        $coupon = new Coupon($stripeCoupon);

        $this->assertEquals('Summer Sale', $coupon->name());
    }

    public function testPercentOffReturnsCorrectValue(): void
    {
        $stripeCoupon = $this->createCoupon(['percent_off' => 20.0]);
        $coupon = new Coupon($stripeCoupon);

        $this->assertEquals(20.0, $coupon->percentOff());
    }

    public function testAmountOffReturnsCorrectValue(): void
    {
        $stripeCoupon = $this->createCoupon(['amount_off' => 1500]);
        $coupon = new Coupon($stripeCoupon);

        $this->assertEquals(1500, $coupon->amountOff());
    }

    public function testIsPercentageReturnsTrueWhenPercentOffSet(): void
    {
        $stripeCoupon = $this->createCoupon(['percent_off' => 15.0]);
        $coupon = new Coupon($stripeCoupon);

        $this->assertTrue($coupon->isPercentage());
    }

    public function testIsPercentageReturnsFalseWhenAmountOffSet(): void
    {
        $stripeCoupon = $this->createCoupon(['amount_off' => 1000, 'percent_off' => null]);
        $coupon = new Coupon($stripeCoupon);

        $this->assertFalse($coupon->isPercentage());
    }

    public function testIsFixedAmountReturnsTrueWhenAmountOffSet(): void
    {
        $stripeCoupon = $this->createCoupon(['amount_off' => 2000, 'percent_off' => null]);
        $coupon = new Coupon($stripeCoupon);

        $this->assertTrue($coupon->isFixedAmount());
    }

    public function testDurationReturnsCorrectValue(): void
    {
        $stripeCoupon = $this->createCoupon(['duration' => 'repeating']);
        $coupon = new Coupon($stripeCoupon);

        $this->assertEquals('repeating', $coupon->duration());
    }

    public function testValidReturnsCorrectValue(): void
    {
        $stripeCoupon = $this->createCoupon(['valid' => false]);
        $coupon = new Coupon($stripeCoupon);

        $this->assertFalse($coupon->valid());
    }
}
