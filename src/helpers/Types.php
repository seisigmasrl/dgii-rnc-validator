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
            self::CEDULA => 'Cédula',
            self::PASSPORT => 'Pasaporte',
        };
    }

    public function fromString(string $string): self
    {
        return match ($string) {
            'RNC' => self::RNC,
            'Cédula' => self::CEDULA,
            'Pasaporte' => self::PASSPORT,
        };
    }
}
