# Validate any RNC with the DGII with PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/seisigmasrl/dgii-rnc-validator.svg?style=flat-square)](https://packagist.org/packages/seisigmasrl/dgii-rnc-validator)
[![Tests](https://img.shields.io/github/actions/workflow/status/seisigmasrl/dgii-rnc-validator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/seisigmasrl/dgii-rnc-validator/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/seisigmasrl/dgii-rnc-validator.svg?style=flat-square)](https://packagist.org/packages/seisigmasrl/dgii-rnc-validator)

A simple package to check a given RNC with the official Tax Authority, Dirección General de Impuestos Internos (DGII), in the Dominican Republic.

## Installation

You can install the package via composer:

```bash
composer require seisigmasrl/dgii-rnc-validator
```

## Usage

```php
$skeleton = new Seisigma\DgiiRncValidator();
echo $skeleton->echoPhrase('Hello, Seisigma!');
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
