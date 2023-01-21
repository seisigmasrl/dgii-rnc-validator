<?php

use Seisigma\DgiiRncValidator\Utils;

test('return a number from a given string', function () {
    expect(Utils::getNumbers("123456789"))->toBeString()
        ->and(Utils::getNumbers("asd123455"))->toBeString()
        ->and(Utils::getNumbers("asdfghjkl"))->toBeBool();

});

test('testing luhnAlgorithm with a valid id', function(){
    $validAccount = Utils::luhnAlgorithmValidation("79927398713");
    $invalidAccount = Utils::luhnAlgorithmValidation("79927398715");
    expect($validAccount)->toBeTrue()
        ->and($invalidAccount)->toBeFalse();
});
