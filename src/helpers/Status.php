<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator\helpers;

enum Status: int
{
    case DECOMMISSIONED = 3;
    case ACTIVE = 2;
    case INACTIVE = 1;
    case SUSPENSE = 0;

    public function toString(): string
    {
        return match ($this) {
            self::DECOMMISSIONED => 'DADO DE BAJA',
            self::ACTIVE => 'ACTIVO',
            self::INACTIVE => 'INACTIVO',
            self::SUSPENSE => 'SUSPENDIDO',
        };
    }
}
