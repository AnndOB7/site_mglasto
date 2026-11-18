<?php

use App\Support\BrazilianFormat;

test('phones, documents and zips are formatted for reading', function (string $method, ?string $digits, ?string $expected) {
    expect(BrazilianFormat::$method($digits))->toBe($expected);
})->with([
    'mobile phone' => ['phone', '11999990000', '(11) 99999-0000'],
    'landline' => ['phone', '1133330000', '(11) 3333-0000'],
    'cpf' => ['document', '52998224725', '529.982.247-25'],
    'cnpj' => ['document', '11222333000181', '11.222.333/0001-81'],
    'zip' => ['zip', '01001000', '01001-000'],
    'unexpected length stays as is' => ['phone', '123', '123'],
    'empty stays null' => ['document', null, null],
]);
