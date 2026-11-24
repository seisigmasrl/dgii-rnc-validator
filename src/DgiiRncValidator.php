<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator;

use InvalidArgumentException;
use Seisigma\DgiiRncValidator\Exceptions\DgiiServiceException;
use Seisigma\DgiiRncValidator\helpers\Status;
use Seisigma\DgiiRncValidator\helpers\Types;
use Seisigma\DgiiRncValidator\helpers\Utils;

class DgiiRncValidator
{
    private const DGII_URL = 'https://dgii.gov.do/app/WebApps/ConsultasWeb2/ConsultasWeb/consultas/rnc.aspx';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36';

    private const TIMEOUT_SECONDS = 30;

    /** Text DGII shows when the RNC/Cédula is not registered. */
    private const NOT_REGISTERED_TEXT = 'no se encuentra inscrito';

    /** Text DGII shows when its own search fails ("Ocurrió un error al intentar realizar la búsqueda"). */
    private const SEARCH_ERROR_TEXT = 'error al intentar realizar la b';

    private static ?\Closure $transport = null;

    private static int $attempts = 2;

    private static int $retryDelayMs = 1000;

    public static function validateRNC(string $string): bool
    {
        $cleanedId = Utils::getNumbers($string);

        if (! $cleanedId) {
            return false;
        }

        preg_match('/^(\d{9}|\d{11})$/', $cleanedId, $matches);

        return (bool) count($matches);
    }

    public static function rncType(string $string): bool|Types
    {
        if (self::validateRNC($string)) {
            return (strlen($string) === 9) ? Types::RNC : Types::CEDULA;
        }

        return false;
    }

    /**
     * Replace the HTTP transport. Pass null to restore the default. Meant for tests.
     *
     * The callable gets (string $url, ?string $postData, array $headers) and returns
     * array{body: string|false, headers: array, error: string|null}.
     */
    public static function useTransport(?callable $transport): void
    {
        self::$transport = $transport === null ? null : $transport(...);
    }

    /**
     * Set how many times check() asks DGII before it gives up, and the wait between tries.
     * Only short-lived failures are tried again: timeouts, network errors, HTTP 5xx and
     * the DGII "error while searching" page. Default: 2 attempts, 1000 ms apart.
     */
    public static function retry(int $attempts = 2, int $delayMs = 1000): void
    {
        self::$attempts = max(1, $attempts);
        self::$retryDelayMs = max(0, $delayMs);
    }

    /**
     * Returns the taxpayer data, or false when DGII says the RNC/Cédula is not registered.
     *
     * @throws InvalidArgumentException
     * @throws DgiiServiceException When DGII cannot be reached or gives an answer we cannot read.
     */
    public static function check(string $id): array|bool
    {
        if (! DgiiRncValidator::validateRNC($id)) {
            throw new InvalidArgumentException('Provide a valid id.');
        }

        for ($attempt = 1; ; $attempt++) {
            try {
                return self::lookup($id);
            } catch (DgiiServiceException $e) {
                if (! $e->isRetryable() || $attempt >= self::$attempts) {
                    throw $e->withAttempts($attempt);
                }

                if (self::$retryDelayMs > 0) {
                    usleep(self::$retryDelayMs * 1000);
                }
            }
        }
    }

    /**
     * One full round with DGII: load the form, then submit the search.
     *
     * @throws DgiiServiceException
     */
    private static function lookup(string $id): array|false
    {
        $initialResponse = self::fetchPage(self::DGII_URL);
        self::validateResponse($initialResponse, step: 'form');

        $viewState = self::extractHiddenField($initialResponse['body'], '__VIEWSTATE');
        $viewStateGenerator = self::extractHiddenField($initialResponse['body'], '__VIEWSTATEGENERATOR');
        $eventValidation = self::extractHiddenField($initialResponse['body'], '__EVENTVALIDATION');

        if (! $viewState || ! $eventValidation) {
            throw DgiiServiceException::invalidPageStructure('form', 'Missing __VIEWSTATE or __EVENTVALIDATION field.');
        }

        $postData = http_build_query([
            '__VIEWSTATE' => $viewState,
            '__VIEWSTATEGENERATOR' => $viewStateGenerator,
            '__EVENTVALIDATION' => $eventValidation,
            'ctl00$cphMain$txtRNCCedula' => $id,
            'ctl00$cphMain$btnBuscarPorRNC' => 'BUSCAR',
        ]);

        // Send back the cookies of the first page (load balancer and session), as a browser does.
        $cookies = self::extractCookies($initialResponse['headers']);

        $resultResponse = self::fetchPage(self::DGII_URL, $postData, $cookies);
        self::validateResponse($resultResponse, step: 'search');

        return self::parseResults($resultResponse['body'], $id);
    }

    /**
     * @return array{body: string|false, headers: array, error: string|null}
     */
    private static function fetchPage(string $url, ?string $postData = null, ?string $cookies = null): array
    {
        $headers = [
            'User-Agent: '.self::USER_AGENT,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: es-DO,es;q=0.9,en;q=0.8',
            'Referer: '.self::DGII_URL,
        ];

        if ($postData !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $headers[] = 'Origin: https://dgii.gov.do';
        }

        if ($cookies) {
            $headers[] = 'Cookie: '.$cookies;
        }

        if (self::$transport !== null) {
            return (self::$transport)($url, $postData, $headers);
        }

        $options = [
            'http' => [
                'method' => $postData !== null ? 'POST' : 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => self::TIMEOUT_SECONDS,
                'ignore_errors' => true,
            ],
        ];

        if ($postData !== null) {
            $options['http']['content'] = $postData;
        }

        $context = stream_context_create($options);

        $body = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $error = null;

        if ($body === false) {
            $lastError = error_get_last();
            $error = $lastError['message'] ?? 'Unknown error';
        }

        return [
            'body' => $body,
            'headers' => $responseHeaders,
            'error' => $error,
        ];
    }

    /**
     * The HTTP status code of the final response (after any redirects), or null when there is none.
     */
    private static function statusCode(array $headers): ?int
    {
        $status = null;

        foreach ($headers as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string) $line, $matches)) {
                $status = (int) $matches[1];
            }
        }

        return $status;
    }

    private static function extractCookies(array $headers): ?string
    {
        $cookies = [];

        foreach ($headers as $line) {
            if (preg_match('/^Set-Cookie:\s*([^=;\s]+=[^;]*)/i', (string) $line, $matches)) {
                $cookies[] = trim($matches[1]);
            }
        }

        return $cookies ? implode('; ', $cookies) : null;
    }

    /**
     * @param  array{body: string|false, headers: array, error: string|null}  $response
     *
     * @throws DgiiServiceException
     */
    private static function validateResponse(array $response, string $step): void
    {
        if ($response['body'] === false) {
            $error = $response['error'] ?? '';

            if (stripos($error, 'timed out') !== false) {
                throw DgiiServiceException::timeout($step);
            }

            throw DgiiServiceException::connectionFailed($step, $error);
        }

        $status = self::statusCode($response['headers']);

        if ($status === 403) {
            throw DgiiServiceException::accessDenied($step, $status);
        }

        if ($status !== null && $status >= 500) {
            throw DgiiServiceException::serverError($status, $step);
        }

        if ($status !== null && $status !== 200) {
            throw DgiiServiceException::unexpectedStatus($status, $step);
        }

        if (str_contains($response['body'], 'Acceso Denegado') || str_contains($response['body'], 'Error 403')) {
            throw DgiiServiceException::accessDenied($step, $status);
        }

        if ($step === 'form' && ! str_contains($response['body'], 'Consulta RNC')) {
            throw DgiiServiceException::invalidPageStructure($step, 'The search form page has no "Consulta RNC" title.');
        }
    }

    private static function extractHiddenField(string $html, string $fieldName): ?string
    {
        $pattern = '/name="'.preg_quote($fieldName, '/').'"[^>]*value="([^"]*)"/';
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @throws DgiiServiceException When the page has no data and no "not registered" message.
     */
    private static function parseResults(string $html, string $id): array|false
    {
        $data = [];
        $fieldMappings = [
            'Nombre/Raz' => 'name',
            'Nombre Comercial' => 'commercial_name',
            'Estado' => 'status',
        ];

        foreach ($fieldMappings as $label => $key) {
            $pattern = '/<td[^>]*style="font-weight:bold;"[^>]*>'.preg_quote($label, '/').'[^<]*<\/td><td>([^<]*)<\/td>/';
            if (preg_match($pattern, $html, $matches)) {
                $data[$key] = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        if (! empty($data['name'])) {
            $status = Status::fromString($data['status'] ?? '');

            return [
                'rnc' => $id,
                'name' => $data['name'],
                'commercial_name' => $data['commercial_name'] ?? '',
                'status' => $status?->toString() ?? $data['status'] ?? '',
            ];
        }

        $message = self::extractInformationMessage($html);

        if ($message !== null && stripos($message, self::NOT_REGISTERED_TEXT) !== false) {
            return false;
        }

        throw DgiiServiceException::unexpectedResponse(
            $message !== null && $message !== ''
                ? "DGII message: {$message}"
                : 'The page has no taxpayer data and no information message.',
            retryable: $message !== null && stripos($message, self::SEARCH_ERROR_TEXT) !== false,
        );
    }

    private static function extractInformationMessage(string $html): ?string
    {
        if (preg_match('/<span[^>]*id="cphMain_lblInformacion"[^>]*>(.*?)<\/span>/s', $html, $matches)) {
            return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return null;
    }
}
