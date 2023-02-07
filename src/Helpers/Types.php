<?php

namespace Seisigma\DgiiRncValidator\Helpers;

enum Types: int
{
    case RNC = 1;
    case CEDULA = 2;
    case PASSPORT = 3;

    public function toString(): string
    {
        return match ($this) {
            self::RNC => 'RNC',
            self::CEDULA => 'Cédula',
            self::PASSPORT => 'Pasaporte',
        };
    }
}
