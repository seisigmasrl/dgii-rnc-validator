<?php

use Seisigma\DgiiRncValidator\DgiiRncValidator;

test('check if the given string is a valid RNC', function () {
    expect(DgiiRncValidator::validateRNC("123"))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC("123456789"))->toBeTrue()
        ->and(DgiiRncValidator::validateRNC("1234567890"))->toBeFalse()
        ->and(DgiiRncValidator::validateRNC("12345678901"))->toBeTrue();
});

it('can return the details of an RNC if true', function () {
    $id = "132620951";
    expect(DgiiRncValidator::check($id))
        ->toBeArray()
        ->toMatchArray([
            "rnc" => $id,
            "name" => "KOI CORPORATION BY SAIKOV SRL",
            "commercial_name" => "KOI CORPORATION BY SAIKOV",
            "status" => "Active"
        ])
        ->and(DgiiRncValidator::check("123456789"))
        ->toBeFalse();
});
