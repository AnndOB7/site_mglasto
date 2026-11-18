<?php

use App\Rules\CpfOrCnpj;

function passesCpfOrCnpj(string $value): bool
{
    $failed = false;
    (new CpfOrCnpj)->validate('document', $value, function () use (&$failed) {
        $failed = true;
    });

    return ! $failed;
}

test('valid CPF and CNPJ pass, with or without the mask', function (string $value) {
    expect(passesCpfOrCnpj($value))->toBeTrue();
})->with([
    'cpf with mask' => ['529.982.247-25'],
    'cpf digits' => ['52998224725'],
    'cnpj with mask' => ['11.222.333/0001-81'],
    'cnpj digits' => ['11222333000181'],
]);

test('invalid documents fail', function (string $value) {
    expect(passesCpfOrCnpj($value))->toBeFalse();
})->with([
    'cpf wrong digit' => ['529.982.247-26'],
    'cpf repeated digits' => ['111.111.111-11'],
    'cnpj wrong digit' => ['11.222.333/0001-82'],
    'cnpj repeated digits' => ['00.000.000/0000-00'],
    'wrong length' => ['1234567890'],
    'letters' => ['abc'],
]);
