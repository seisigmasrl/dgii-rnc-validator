# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A PHP package that validates RNC (Registro Nacional de Contribuyentes) numbers against the Dominican Republic's official tax authority (DGII) via their undocumented SOAP web service.

## Commands

```bash
# Run tests (uses Pest PHP)
composer test

# Run single test file
./vendor/bin/pest tests/DgiiRncValidatorTest.php

# Run tests with coverage
composer test-coverage

# Format code (uses Laravel Pint)
composer format
```

## Architecture

**Main Entry Point**: `src/DgiiRncValidator.php`
- Static methods for RNC validation
- `check()` - Validates RNC against DGII SOAP API and returns taxpayer details
- `validateRNC()` - Validates RNC format (9 digits for business, 11 for cedula)
- `rncType()` - Returns whether input is RNC or CEDULA type

**Helpers** (`src/helpers/`):
- `Utils.php` - Luhn algorithm validation, number extraction, Dominican ID validation
- `Types.php` - Enum for RNC types (RNC=01, CEDULA=02, PASSPORT=03)
- `Status.php` - Enum for taxpayer status (ACTIVO, INACTIVO, SUSPENDIDO, DADO DE BAJA)

**External Dependency**: Web scrapes the official DGII portal at `https://dgii.gov.do/app/WebApps/ConsultasWeb2/ConsultasWeb/consultas/rnc.aspx`

## Requirements

- PHP >= 8.1