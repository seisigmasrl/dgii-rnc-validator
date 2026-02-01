# Validate any RNC with the DGII

[![Latest Version on Packagist](https://img.shields.io/packagist/v/seisigmasrl/dgii-rnc-validator.svg?style=flat-square)](https://packagist.org/packages/seisigmasrl/dgii-rnc-validator)
[![Tests](https://img.shields.io/github/actions/workflow/status/seisigmasrl/dgii-rnc-validator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/seisigmasrl/dgii-rnc-validator/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/seisigmasrl/dgii-rnc-validator.svg?style=flat-square)](https://packagist.org/packages/seisigmasrl/dgii-rnc-validator)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fseisigmasrl%2Fdgii-rnc-validator.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2Fseisigmasrl%2Fdgii-rnc-validator?ref=badge_shield)

A simple package to check a given RNC with the official Tax Authority, Dirección General de Impuestos Internos (DGII), in the Dominican Republic and few more options.

## Requirements

- PHP >= 8.1

## Installation

You can install the package via composer:

```bash
composer require seisigmasrl/dgii-rnc-validator
```

## Usage
This package aims to bring you a simple way to check and validate if a given RNC is valid and its current status with the tax authorities.
Most existing solutions are based on parsing a monthly shared file with all the existing RNC by the Dirección General de Impuestos Internos (DGII), the official Tax Authority in the Dominican Republic.

This approach is excellent for improving performance, but it's not optimal for services requiring live, trusted information. For this reason, the current package provides a simple API to:
- Validate RNC's
- Verify if the given RNC is valid.

The package fetches data directly from the official DGII web portal to ensure you always get up-to-date information.

Here's the list of the provided methods by this package:

### Check
Validate if a given RNC is valid and returns the details of the Taxpayer, or `false` if not found.<br>
__How to use it:__
```php
use Seisigma\DgiiRncValidator\DgiiRncValidator;

// 132620951 is a valid RNC
$validatedRnc = DgiiRncValidator::check("132620951");
var_dump($validatedRnc);

// array(4) {
//    ["rnc"]=> string(9) "132620951"
//    ["name"]=> string(29) "KOI CORPORATION BY SAIKOV SRL"
//    ["commercial_name"]=> string(25) "KOI CORPORATION BY SAIKOV"
//    ["status"]=> string(6) "ACTIVO"
// }

// 123456789 is not registered in DGII
$validatedRnc = DgiiRncValidator::check("123456789");
var_dump($validatedRnc); // bool(false)
```

### validateRNC
Validate if a given string has a valid RNC format (9 digits) or Cedula format (11 digits).<br>
__How to use it:__
```php
use Seisigma\DgiiRncValidator\DgiiRncValidator;

$validatedRnc = DgiiRncValidator::validateRNC("132620951");
var_dump($validatedRnc); // bool(true) - valid 9-digit RNC format

$validatedRnc = DgiiRncValidator::validateRNC("12345678901");
var_dump($validatedRnc); // bool(true) - valid 11-digit Cedula format

$validatedRnc = DgiiRncValidator::validateRNC("12345");
var_dump($validatedRnc); // bool(false) - invalid format
```

### rncType
Returns the type of identifier (RNC or Cedula) based on the string length.<br>
__How to use it:__
```php
use Seisigma\DgiiRncValidator\DgiiRncValidator;
use Seisigma\DgiiRncValidator\helpers\Types;

// 9-digit identifier = RNC (business)
$rncType = DgiiRncValidator::rncType("132620951");
var_dump($rncType); // enum(Types::RNC)

// 11-digit identifier = Cedula (person)
$rncType = DgiiRncValidator::rncType("04800009577");
var_dump($rncType); // enum(Types::CEDULA)

// Invalid format returns false
$rncType = DgiiRncValidator::rncType("12345");
var_dump($rncType); // bool(false)
```

The Types enum includes two functions:
- `toString`: Return the string value from the returned enum.
Ex:
```php
var_dump(Types::RNC->toString()) // string(RNC)
```
- `toCode`: Return the DGII type code value from the returned enum.
  Ex:
```php
var_dump(Types::RNC->toCode()) // string(01)
```

### Status Enum
The `check()` method returns the taxpayer status as a normalized string. You can also use the Status enum directly to work with status values.

```php
use Seisigma\DgiiRncValidator\helpers\Status;

// Convert a string to Status enum
$status = Status::fromString("ACTIVO");
var_dump($status); // enum(Status::ACTIVE)

// Get the string representation
var_dump(Status::ACTIVE->toString()); // string(6) "ACTIVO"
```

Available status values:
| Enum | String Value | Description |
|------|--------------|-------------|
| `Status::ACTIVE` | ACTIVO | Active taxpayer |
| `Status::INACTIVE` | INACTIVO | Inactive taxpayer |
| `Status::SUSPENSE` | SUSPENDIDO | Suspended taxpayer |
| `Status::DECOMMISSIONED` | DADO DE BAJA | Decommissioned taxpayer |

## Helper Functions
Just in case you need a few extra tools, here's a list of utility functions:

### getNumbers
This function returns all numbers from any provided string.<br>
__How to use it:__

```php
use Seisigma\DgiiRncValidator\helpers\Utils;

$results = Utils::getNumbers("abc123456");
var_dump($results); // string(6) "123456"

$results = Utils::getNumbers("asdfasdfs");
var_dump($results); // bool(false)
```

### luhnAlgorithmValidation
This function validates if the given sequence of digits has a valid key (checksum).<br>
__How to use it:__

```php
use Seisigma\DgiiRncValidator\helpers\Utils;

$result = Utils::luhnAlgorithmValidation("79927398713");
var_dump($result); // bool(true)

$result = Utils::luhnAlgorithmValidation("79927398715");
var_dump($result); // bool(false)
```

### validateDominicanCitizenId
This function validates if the given sequence of digits is a valid Dominican Citizen Id.<br>
__How to use it:__

```php
use Seisigma\DgiiRncValidator\helpers\Utils;

$result = Utils::validateDominicanCitizenId("04800009575");
var_dump($result); // bool(true)

$result = Utils::validateDominicanCitizenId("04800009577");
var_dump($result); // bool(false)
```

## Exception Handling

The `check()` method may throw exceptions when there are issues connecting to the DGII service:

```php
use Seisigma\DgiiRncValidator\DgiiRncValidator;
use Seisigma\DgiiRncValidator\Exceptions\DgiiServiceException;

try {
    $result = DgiiRncValidator::check("132620951");
} catch (InvalidArgumentException $e) {
    // Invalid RNC format provided
} catch (DgiiServiceException $e) {
    // Service-related error (connection failed, access denied, timeout, etc.)
    echo $e->getMessage();
}
```

The `DgiiServiceException` is thrown in the following scenarios:
- **Connection failed**: Unable to connect to DGII service
- **Access denied**: Request was blocked by DGII (403 error)
- **Invalid page structure**: The DGII website structure has changed
- **Timeout**: Request to DGII service timed out

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ricardo A. Vargas R.](https://github.com/ricardov03)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2Fseisigmasrl%2Fdgii-rnc-validator.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2Fseisigmasrl%2Fdgii-rnc-validator?ref=badge_large)