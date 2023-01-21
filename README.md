# Validate any RNC with the DGII

[![Latest Version on Packagist](https://img.shields.io/packagist/v/seisigmasrl/dgii-rnc-validator.svg?style=flat-square)](https://packagist.org/packages/seisigmasrl/dgii-rnc-validator)
[![Tests](https://img.shields.io/github/actions/workflow/status/seisigmasrl/dgii-rnc-validator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/seisigmasrl/dgii-rnc-validator/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/seisigmasrl/dgii-rnc-validator.svg?style=flat-square)](https://packagist.org/packages/seisigmasrl/dgii-rnc-validator)

A simple package to check a given RNC with the official Tax Authority, Dirección General de Impuestos Internos (DGII), in the Dominican Republic and few more options.

## Installation

You can install the package via composer:

```bash
composer require seisigmasrl/dgii-rnc-validator
```

## Usage
This package aims to bring you a simple way to check and validate if a given RNC is valid and its current status with the tax authorities.
Most existing solutions are based on parsing a monthly shared file with all the existing RNC by the Dirección General de Impuestos Internos (DGII), the official Tax Authority in the Dominican Republic.

This approach is excellent for improving performance, but It's not optimal for services requiring life, trusted information. For this reason, the current package provides a simple API to:
- Validate RNC's
- Verify if the Given RNC it's valid.

But how? There's 'somewhere' 😅, a "real" public non-documented endpoint used by the Tax Authorities that provide a set of tools to validate the information with the DGII. This endpoint provides several options, but we will only use the RNC Validation for the scope of this package.

Here's the list of the provided methods by this package:

### Check
Validate if a given RNC is valid and returns the details of the Taxpayer but false if not valid.<br>
__How to use it:__
```php
require Seisigma\DgiiRncValidator\DgiiRncValidator;
...
// 132620951 is a valid RNC
$validatedRnc = DgiiRncValidator::check("132620951");
var_dump($validatedRnc);

// array(4) {
//    ["rnc"]=> string(9) "132620951"
//    ["name"]=> string(29) "KOI CORPORATION BY SAIKOV SRL"
//    ["commercial_name"]=> string(25) "KOI CORPORATION BY SAIKOV"
//    ["status"]=> string(6) "Active"
// }

// 123456789 is an invalid RNC
$validatedRnc = DgiiRncValidator::check("123456789");
var_dump($validatedRnc); // bool(false)
```

### validateRNC
Validate if a given string is a valid RNC.<br>
__How to use it:__
```php
require Seisigma\DgiiRncValidator\DgiiRncValidator;
...
// 132620951 is a valid RNC
$validatedRnc = DgiiRncValidator::validateRNC("132620951");
var_dump($validatedRnc); // bool(true)

// 123456789 is an invalid RNC
$validatedRnc = DgiiRncValidator::check("123456789");
var_dump($validatedRnc); // bool(false)
```

## Helper Functions
Just in case you need a few extra tools, here's a list of utility functions:

### getNumbers
This function returns all numbers from any provided string.<br>
__How to use it:__
```php
require Seisigma\DgiiRncValidator\Helpers\Utils;
...
$results = Utils::getNumbers("abc123456");
var_dump($results); // string(6) "123456"

$results = Utils::getNumbers("asdfasdfs");
var_dump($results); // bool(false)
```

### luhnAlgorithmValidation
This function validates if the given sequence of digits has a valid key (checksum).<br>
__How to use it:__
```php
require Seisigma\DgiiRncValidator\Helpers\Utils;
...
$result = Utils::luhnAlgorithmValidation("79927398713");
var_dump($result); // bool(true)

$result = Utils::luhnAlgorithmValidation("79927398715");
var_dump($result); // bool(false)
```

### validateDominicanCitizenId
This function validates if the given sequence of digits is a valid Dominican Citizen Id.<br>
__How to use it:__
```php
require Seisigma\DgiiRncValidator\Helpers\Utils;
...
$result = Utils::validateDominicanCitizenId("04800009575");
var_dump($result); // bool(true)

$result = Utils::validateDominicanCitizenId("04800009577");
var_dump($result); // bool(false)
```

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
