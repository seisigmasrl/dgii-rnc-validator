<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator\helpers;

enum Status: int
{
    case ACTIVE = 2;
    case INACTIVE = 1;

    public function toString(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive'
        };
    }
}
