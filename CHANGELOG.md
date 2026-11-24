# Changelog

All notable changes to `dgii-rnc-validator` will be documented in this file.

## 2.1.0 - Unreleased

- Read the real HTTP status code of the final response. Before, any status line with a "5" in it was treated as a server error, and 4xx codes other than 403 were accepted.
- Send the cookies of the DGII form page (load balancer and session) with the search request, as a browser does.
- Update the User-Agent to Chrome 142 and send `Origin` and `es-DO` language headers.
- `check()` now throws `DgiiServiceException::unexpectedResponse()` when DGII answers without taxpayer data and without the "not registered" message. Before, it returned `false`, so a DGII failure looked like "not found".
- `DgiiServiceException` messages now carry the reason (network error, HTTP status, DGII message), and `context()` returns the step and HTTP status for logs.
- `check()` asks DGII again once when the failure is short-lived: the DGII "Ocurrió un error al intentar realizar la búsqueda" page, a timeout, a network error or HTTP 5xx. Change it with `DgiiRncValidator::retry($attempts, $delayMs)`. `DgiiServiceException::isRetryable()` tells if a later call can work, and `context()['attempts']` gives the number of tries.
- Add `DgiiRncValidator::useTransport()` to fake DGII in tests.
- Tests run offline with saved DGII pages. The live test moved to `composer test-live`.
- **Breaking for PHP 8.1:** require PHP 8.2 or higher. PHP 8.1 has had no security fixes since 2025-12-31, and no secure PHPUnit version runs on it. Apps on PHP 8.1 stay on 2.0.x.
- Upgrade Pest to v3 (PHP 8.4 support), Pint and Ray. Test on PHP 8.2, 8.3 and 8.4 in CI.

## 1.1.7 - 2024-05-16

- Add validation for empty or non-numeric RNC input string. (Thanks to @elminson for the contribution) #Hotfix

## 1.1.6 - 2023-02-10

- Updating the Status enum by validating all possible Status types from the API. I matched these values by comparing them from the DGII data file shared with all the existing RNC.

## 1.1.5 - 2023-02-09

- Fixing the getNumbersFromString from the Utils helper function to correctly return all numbers from the given string.

## 1.1.4 - 2023-02-07

- Using Enum returns over direct values in the rncType function on the DgiiRncValidator library.
- Adding the toCode method to the Types enum.

## 1.1.3 - 2023-02-07

- Removing the unnecessary fromText method from the Types enum.
- Improving return text from the toString method from the Types enum.

## 1.1.2 - 2023-02-07

- Adding helper method on the Types enum.

## 1.1.1 - 2023-02-07

- Fixing namespaces.

## 1.1.0 - 2023-02-07

- New Feature: RNC Type.

## 1.0.0 - 2023-01-21

- Initial release.
