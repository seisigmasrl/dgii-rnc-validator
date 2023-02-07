<?php

namespace Seisigma\DgiiRncValidator\Helpers;

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
