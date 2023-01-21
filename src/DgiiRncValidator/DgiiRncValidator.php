<?php

namespace Seisigma\DgiiRncValidator;

class DgiiRncValidator
{
    private string $rnc;

    public static function validateRNC(string $string): bool
    {
        $cleanedId = Utils::getNumbers($string);
        preg_match('/^(\d{9}|\d{11})$/', $cleanedId,$matches);
        return (bool)count($matches);
    }
}
