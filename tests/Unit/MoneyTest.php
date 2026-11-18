<?php

use App\Support\Money;

test('typed reais become cents', function (string $typed, int $cents) {
    expect(Money::toCents($typed))->toBe($cents);
})->with([
    'comma decimal' => ['88,60', 8_860],
    'one decimal digit' => ['88,6', 8_860],
    'no decimals' => ['88', 8_800],
    'thousand separator' => ['1.234,56', 123_456],
    'thousand separator without cents' => ['1.234', 123_400],
    'dot decimal' => ['88.60', 8_860],
    'currency symbol and spaces' => [' R$ 88,60 ', 8_860],
    'value that floats round badly' => ['0,29', 29],
]);

test('invalid amounts are rejected instead of guessed', function (string $typed) {
    expect(Money::toCents($typed))->toBeNull();
})->with(['abc', '88,605', '1,234,56', '-10,00', '12.34.5']);

test('empty input means no price', function () {
    expect(Money::toCents(''))->toBeNull()
        ->and(Money::toCents(null))->toBeNull();
});

test('cents become text for the form and back without changing the value', function (int $cents, string $text) {
    expect(Money::fromCents($cents))->toBe($text)
        ->and(Money::toCents(Money::fromCents($cents)))->toBe($cents);
})->with([
    [8_860, '88,60'],
    [123_456, '1.234,56'],
    [5, '0,05'],
]);

test('cents are formatted with the currency symbol', function () {
    expect(Money::format(8_860))->toBe('R$ 88,60');
});
