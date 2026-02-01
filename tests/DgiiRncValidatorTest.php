<?php

use Seisigma\DgiiRncValidator\DgiiRncValidator;
use Seisigma\DgiiRncValidator\helpers\Types;

test('check if the given string is a valid RNC', function () {
    expect(DgiiRncValidator::validateRNC('123'))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC('123456789'))->toBeTrue()
        ->and(DgiiRncValidator::validateRNC('1234567890'))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC('12345678901'))->toBeTrue();
});

test('check rncType return the type name', function () {
    expect(DgiiRncValidator::rncType('123456789'))->toBe(Types::RNC)
        ->and(DgiiRncValidator::rncType('12345678901'))->toBe(Types::CEDULA);
});

it('can return the details of an RNC if true', function () {
    $id = '132620951';
    expect(DgiiRncValidator::check($id))
        ->toBeArray()
        ->toMatchArray([
            'rnc' => $id,
            'name' => 'KOI CORPORATION BY SAIKOV SRL',
            'commercial_name' => 'KOI CORPORATION BY SAIKOV',
            'status' => 'ACTIVO',
        ])
        ->and(DgiiRncValidator::check('123456789'))
        ->toBeFalse();
});

test('check if the given string without numbers is a valid RNC', function () {
    $id = 'abc cdd';
    expect(DgiiRncValidator::validateRNC($id))->toBeFalse();
    $id = ' ';
    expect(DgiiRncValidator::validateRNC($id))->toBeFalse();
    $id = '';
    expect(DgiiRncValidator::validateRNC($id))->toBeFalse();
});
