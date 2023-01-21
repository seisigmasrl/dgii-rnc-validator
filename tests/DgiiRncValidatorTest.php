<?php

use Seisigma\DgiiRncValidator\DgiiRncValidator;

test('check if the given string is a valid RNC', function () {
    expect(DgiiRncValidator::validateRNC("123"))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC("123456789"))->toBeTrue()
        ->and(DgiiRncValidator::validateRNC("1234567890"))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC("12345678901"))->toBeTrue();
});

it('can validate a Legal Person RNC value is good', function () {
    $rnc = DgiiRncValidator::validateFormat("132620951");
    expect($rnc)->toBeTrue();
});

it('can validate a Physical person RNC values is good', function () {
    $rnc = DgiiRncValidator::validateFormat("04800009575");
    expect($rnc)->toBeTrue();
});

it('can validate a Dominican official ID number', function () {
    // Good ID
    $rnc = DgiiRncValidator::validatePersonId("04800009575");
    expect($rnc)->toBeTrue();

    // Wrong ID
    $rnc = DgiiRncValidator::validatePersonId("04800009577");
    expect($rnc)->toBeFalse();
});

it('can return the details of an RNC if true', function () {
    $taxpayer = DgiiRncValidator::check("132620951");
    expect($taxpayer)
        ->toBeArray()
        ->toHaveProperties(['name', 'commercial_name', 'status'])
        ->toMatchArray([
            "name" => "KOI CORPORATION BY SAIKOV SRL",
            "commercial_name" => "KOI CORPORATION BY SAIKOV",
            "status" => "Active"
        ]);
});

it('can return false if the RNC is invalid', function () {
    $taxpayer = DgiiRncValidator::check("123456789");
    expect($taxpayer)
        ->toBeFalse();
});
