<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator\helpers;

enum Types: int
{
    case RNC = 1;
    case CEDULA = 2;
    case PASSPORT = 3;

    public function toString(): string
    {
        return match ($this) {
            self::RNC => 'RNC',
            self::CEDULA => 'CEDULA',
            self::PASSPORT => 'PASSPORT',
        };
    }
}
