<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator\Exceptions;

use Exception;

class DgiiServiceException extends Exception
{
    /**
     * @param  array<string, mixed>  $context  Details about the failed request, safe to log.
     */
    final public function __construct(
        string $message,
        private readonly array $context = [],
        private readonly bool $retryable = false,
    ) {
        parent::__construct($message);
    }

    /**
     * True when the failure is short-lived (timeout, network error, HTTP 5xx, DGII search error),
     * so asking again later can work.
     */
    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    /**
     * A copy of this exception that records how many times DGII was asked.
     */
    public function withAttempts(int $attempts): self
    {
        return new static($this->getMessage(), [...$this->context, 'attempts' => $attempts], $this->retryable);
    }

    /**
     * Details about the failed request (step, HTTP status, network error), safe to log.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public static function connectionFailed(string $step = '', string $reason = ''): self
    {
        return new self(
            self::withReason('Unable to connect to DGII service. The service may be unavailable or blocking requests.', $reason),
            ['step' => $step, 'reason' => $reason],
            retryable: true,
        );
    }

    public static function serverError(int $status, string $step = ''): self
    {
        return new self(
            "DGII service returned HTTP {$status}. The service is having problems.",
            ['step' => $step, 'http_status' => $status],
            retryable: true,
        );
    }

    public static function unexpectedStatus(int $status, string $step = ''): self
    {
        return new self(
            "DGII service returned an unexpected HTTP {$status}.",
            ['step' => $step, 'http_status' => $status],
        );
    }

    public static function accessDenied(string $step = '', ?int $status = null): self
    {
        return new self(
            'Access denied by DGII service. Request was blocked.',
            ['step' => $step, 'http_status' => $status],
        );
    }

    public static function invalidPageStructure(string $step = '', string $reason = ''): self
    {
        return new self(
            self::withReason('Unable to parse DGII page. The page structure may have changed.', $reason),
            ['step' => $step, 'reason' => $reason],
        );
    }

    /**
     * DGII answered the search with a page that has no taxpayer data and no "not registered" message.
     */
    public static function unexpectedResponse(string $reason = '', bool $retryable = false): self
    {
        return new self(
            self::withReason('DGII answered the search without taxpayer data or a "not registered" message.', $reason),
            ['step' => 'search', 'reason' => $reason],
            $retryable,
        );
    }

    public static function searchRequestFailed(string $reason = ''): self
    {
        return new self(
            self::withReason('Failed to submit search request to DGII service.', $reason),
            ['step' => 'search', 'reason' => $reason],
        );
    }

    public static function timeout(string $step = ''): self
    {
        return new self('Request to DGII service timed out.', ['step' => $step], retryable: true);
    }

    private static function withReason(string $message, string $reason): string
    {
        return $reason === '' ? $message : "{$message} Reason: {$reason}";
    }
}
