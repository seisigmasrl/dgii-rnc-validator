<?php

declare(strict_types=1);

/*
 * This file is part of the DgiiRncValidator.
 *
 * (c) Ricardo A. Vargas R. <im@ricardovargas.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with the source code.
 */

namespace Seisigma\DgiiRncValidator\helpers;

final class Utils
{
    private function getNumbersFromString(string $string): string|bool
    {
        preg_match_all('/\d+/', $string, $matches);

        return count($matches[0]) ? implode($matches[0]) : false;
    }

    public static function getNumbers(string $string): string|bool
    {
        return (new self())->getNumbersFromString($string);
    }

    public static function luhnAlgorithmValidation(string $entry): bool
    {
        $pairs = [];
        $number = Utils::getNumbers($entry);
        $check = substr($entry, 0, (strlen($number) - 1));
        $verificationDigit = substr($entry, -1, 1);
        for ($i = 0; $i < strlen($check); $i++) {
            if ($i % 2) {
                $double = $check[$i] * 2;
                $pairs[] = ($double >= 10) ? array_sum(str_split((string) $double)) : $double;
            } else {
                $pairs[] = (int) $check[$i];
            }
        }
        $result = substr((string) (array_sum($pairs) * 9), -1, 1);

        return $result === $verificationDigit;
    }

    /**
     * @throws \Exception
     */
    public static function validateDominicanCitizenId(string $id): bool
    {
        $id = Utils::getNumbers($id);
        preg_match('/^(\d{11})$/', $id, $matches);
        if (empty($matches)) {
            throw new \Exception('Please provide a legit Dominican Citizen Id.');
        }

        return Utils::luhnAlgorithmValidation($id);
    }
}
