<?php

declare(strict_types=1);

namespace App\Auth;

final class DemoUsers
{
    /**
     * @return list<array{name: string, email: string, password: string}>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'Alice Martin',
                'email' => 'alice.demo@sfcashier.local',
                'password' => 'DemoPass123!',
            ],
            [
                'name' => 'Bob Durand',
                'email' => 'bob.demo@sfcashier.local',
                'password' => 'DemoPass123!',
            ],
            [
                'name' => 'Chloe Bernard',
                'email' => 'chloe.demo@sfcashier.local',
                'password' => 'DemoPass123!',
            ],
        ];
    }
}
