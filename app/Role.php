<?php

namespace App;

enum Role: string
{
    case SUPER_ADMIN = 'super_admin';
    case BRANCH_ADMIN = 'branch_admin';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::BRANCH_ADMIN => 'Admin Cabang',
            self::CUSTOMER => 'Pelanggan',
        };
    }
}
