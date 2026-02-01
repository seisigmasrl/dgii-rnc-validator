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

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

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
     * @throws InvalidArgumentException
     * @throws DgiiServiceException
     */
    public static function check(string $id): array|bool
    {
        if (! DgiiRncValidator::validateRNC($id)) {
            throw new InvalidArgumentException('Provide a valid id.');
        }

        $initialResponse = self::fetchPage(self::DGII_URL);
        self::validateResponse($initialResponse, isInitialRequest: true);

        $viewState = self::extractHiddenField($initialResponse['body'], '__VIEWSTATE');
        $viewStateGenerator = self::extractHiddenField($initialResponse['body'], '__VIEWSTATEGENERATOR');
        $eventValidation = self::extractHiddenField($initialResponse['body'], '__EVENTVALIDATION');

        if (! $viewState || ! $eventValidation) {
            throw DgiiServiceException::invalidPageStructure();
        }

        $postData = http_build_query([
            '__VIEWSTATE' => $viewState,
            '__VIEWSTATEGENERATOR' => $viewStateGenerator,
            '__EVENTVALIDATION' => $eventValidation,
            'ctl00$cphMain$txtRNCCedula' => $id,
            'ctl00$cphMain$btnBuscarPorRNC' => 'BUSCAR',
        ]);

        $resultResponse = self::fetchPage(self::DGII_URL, $postData);
        self::validateResponse($resultResponse, isInitialRequest: false);

        return self::parseResults($resultResponse['body'], $id);
    }

    /**
     * @return array{body: string|false, headers: array, error: string|null}
     */
    private static function fetchPage(string $url, ?string $postData = null): array
    {
        $options = [
            'http' => [
                'method' => $postData ? 'POST' : 'GET',
                'header' => implode("\r\n", [
                    'User-Agent: '.self::USER_AGENT,
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Referer: '.self::DGII_URL,
                ]),
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ];

        if ($postData) {
            $options['http']['content'] = $postData;
        }

        $context = stream_context_create($options);

        $body = @file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
        $error = null;

        if ($body === false) {
            $lastError = error_get_last();
            $error = $lastError['message'] ?? 'Unknown error';
        }

        return [
            'body' => $body,
            'headers' => $headers,
            'error' => $error,
        ];
    }

    /**
     * @param  array{body: string|false, headers: array, error: string|null}  $response
     *
     * @throws DgiiServiceException
     */
    private static function validateResponse(array $response, bool $isInitialRequest): void
    {
        if ($response['body'] === false) {
            $error = $response['error'] ?? '';

            if (str_contains($error, 'timed out')) {
                throw DgiiServiceException::timeout();
            }

            throw DgiiServiceException::connectionFailed();
        }

        $statusLine = $response['headers'][0] ?? '';

        if (str_contains($statusLine, '403')) {
            throw DgiiServiceException::accessDenied();
        }

        if (str_contains($statusLine, '5')) {
            throw DgiiServiceException::connectionFailed();
        }

        if (str_contains($response['body'], 'Acceso Denegado') || str_contains($response['body'], 'Error 403')) {
            throw DgiiServiceException::accessDenied();
        }

        if ($isInitialRequest && ! str_contains($response['body'], 'Consulta RNC')) {
            throw DgiiServiceException::invalidPageStructure();
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

    private static function parseResults(string $html, string $id): array|false
    {
        if (! str_contains($html, 'cphMain_dvDatosContribuyentes')) {
            return false;
        }

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

        if (empty($data['name'])) {
            return false;
        }

        $status = Status::fromString($data['status'] ?? '');

        return [
            'rnc' => $id,
            'name' => $data['name'],
            'commercial_name' => $data['commercial_name'] ?? '',
            'status' => $status?->toString() ?? $data['status'] ?? '',
        ];
    }
}
