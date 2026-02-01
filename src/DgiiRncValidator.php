<?php

declare(strict_types=1);

namespace Seisigma\DgiiRncValidator;

use Seisigma\DgiiRncValidator\helpers\Types;
use Seisigma\DgiiRncValidator\helpers\Utils;

class DgiiRncValidator
{
    private const DGII_URL = 'https://dgii.gov.do/app/WebApps/ConsultasWeb2/ConsultasWeb/consultas/rnc.aspx';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    public static function validateRNC(string $string): bool
    {
        $cleanedId = Utils::getNumbers($string);

        if (!$cleanedId) {
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
     * @throws \Exception
     */
    public static function check(string $id): array|bool
    {
        if (! DgiiRncValidator::validateRNC($id)) {
            throw new \Exception('Provide a valid id.');
        }

        $initialHtml = self::fetchPage(self::DGII_URL);
        if ($initialHtml === false) {
            return false;
        }

        $viewState = self::extractHiddenField($initialHtml, '__VIEWSTATE');
        $viewStateGenerator = self::extractHiddenField($initialHtml, '__VIEWSTATEGENERATOR');
        $eventValidation = self::extractHiddenField($initialHtml, '__EVENTVALIDATION');

        if (!$viewState || !$eventValidation) {
            return false;
        }

        $postData = http_build_query([
            '__VIEWSTATE' => $viewState,
            '__VIEWSTATEGENERATOR' => $viewStateGenerator,
            '__EVENTVALIDATION' => $eventValidation,
            'ctl00$cphMain$txtRNCCedula' => $id,
            'ctl00$cphMain$btnBuscarPorRNC' => 'BUSCAR',
        ]);

        $resultHtml = self::fetchPage(self::DGII_URL, $postData);
        if ($resultHtml === false) {
            return false;
        }

        return self::parseResults($resultHtml, $id);
    }

    private static function fetchPage(string $url, ?string $postData = null): string|false
    {
        $options = [
            'http' => [
                'method' => $postData ? 'POST' : 'GET',
                'header' => implode("\r\n", [
                    'User-Agent: ' . self::USER_AGENT,
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Referer: ' . self::DGII_URL,
                ]),
                'timeout' => 30,
            ],
        ];

        if ($postData) {
            $options['http']['content'] = $postData;
        }

        $context = stream_context_create($options);

        return @file_get_contents($url, false, $context);
    }

    private static function extractHiddenField(string $html, string $fieldName): ?string
    {
        $pattern = '/name="' . preg_quote($fieldName, '/') . '"[^>]*value="([^"]*)"/';
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function parseResults(string $html, string $id): array|false
    {
        if (strpos($html, 'cphMain_dvDatosContribuyentes') === false) {
            return false;
        }

        $data = [];
        $fieldMappings = [
            'Nombre/Raz' => 'name',
            'Nombre Comercial' => 'commercial_name',
            'Estado' => 'status',
        ];

        foreach ($fieldMappings as $label => $key) {
            $pattern = '/<td[^>]*style="font-weight:bold;"[^>]*>' . preg_quote($label, '/') . '[^<]*<\/td><td>([^<]*)<\/td>/';
            if (preg_match($pattern, $html, $matches)) {
                $data[$key] = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        if (empty($data['name'])) {
            return false;
        }

        return [
            'rnc' => $id,
            'name' => $data['name'] ?? '',
            'commercial_name' => $data['commercial_name'] ?? '',
            'status' => $data['status'] ?? '',
        ];
    }
}
