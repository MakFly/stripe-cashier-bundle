<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

/**
 * Extends BillableInterface with entity-specific identity and contact methods.
 */
interface BillableEntityInterface extends BillableInterface
{
    public function getId(): ?int;
    public function getEmail(): string;
    public function getName(): ?string;
}
