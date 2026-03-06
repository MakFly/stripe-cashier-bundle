<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Support;

use Stripe\StripeClient;

/** Fake Stripe client for isolated testing. */
final class TestStripeClient extends StripeClient
{
    /**
     * @var array<string, object>
     */
    private array $services = [];

    public function __construct()
    {
        parent::__construct('sk_test_dummy');
    }

    public function withService(string $name, object $service): self
    {
        $this->services[$name] = $service;

        return $this;
    }

    /**
     * @param string $name
     */
    public function __get($name): mixed
    {
        if (array_key_exists($name, $this->services)) {
            return $this->services[$name];
        }

        return parent::__get($name);
    }
}
