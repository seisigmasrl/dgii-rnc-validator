<?php

namespace Seisigma\DgiiRncValidator;

use Seisigma\DgiiRncValidator\Helpers\Status;
use Seisigma\DgiiRncValidator\Helpers\Utils;
use SoapClient;

class DgiiRncValidator
{
    private string $rnc;

    private function parseJson(string $string): array
    {
        ray($string);
        return json_decode(json_encode($string));
    }

    public static function validateRNC(string $string): bool
    {
        $cleanedId = Utils::getNumbers($string);
        preg_match('/^(\d{9}|\d{11})$/', $cleanedId,$matches);
        return (bool)count($matches);
    }

    /**
     * @throws \Exception
     */
    public static function check(string $id): array | bool
    {
        if (!DgiiRncValidator::validateRNC($id))
            throw new \Exception("Provide a valid id.");

        $client = new SoapClient("https://dgii.gov.do/wsMovilDGII/WSMovilDGII.asmx?wsdl");

        $params = [
            "value" => $id,
            "patronBusqueda" => 0,
            "inicioFilas" => 0,
            "filaFilas" => 10,
            "IMEI" => '',
        ];

        $response = $client->__soapCall("GetContribuyentes", [$params]);
        $results = [
            "RGE_NOMBRE" => $name,
            "NOMBRE_COMERCIAL" => $commercialName,
            "ESTATUS" => $status
        ] = json_decode($response->GetContribuyentesResult, true);

        return [
            "rnc" => $id,
            "name" => $name,
            "commercial_name" => $commercialName,
            "status" => Status::from($status)->toString()
        ];
    }
}
