<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

interface BillableEntityInterface extends BillableInterface
{
    public function getId(): ?int;
    public function getEmail(): string;
    public function getName(): ?string;
}
