<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator\Exceptions;

use Exception;

class DgiiServiceException extends Exception
{
    public static function connectionFailed(): self
    {
        return new self('Unable to connect to DGII service. The service may be unavailable or blocking requests.');
    }

    public static function accessDenied(): self
    {
        return new self('Access denied by DGII service. Request was blocked.');
    }

    public static function invalidPageStructure(): self
    {
        return new self('Unable to parse DGII page. The page structure may have changed.');
    }

    public static function searchRequestFailed(): self
    {
        return new self('Failed to submit search request to DGII service.');
    }

    public static function timeout(): self
    {
        return new self('Request to DGII service timed out.');
    }
}
