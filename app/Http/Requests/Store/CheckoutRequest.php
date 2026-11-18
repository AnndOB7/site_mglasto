<?php

namespace App\Http\Requests\Store;

use App\Rules\CpfOrCnpj;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Customer data for the quote. Masks ("(11) 99999-0000", "000.000.000-00")
 * are removed before validating, so only digits are stored.
 */
class CheckoutRequest extends FormRequest
{
    public const STATES = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digits = fn (string $field): ?string => $this->filled($field)
            ? preg_replace('/\D/', '', (string) $this->input($field))
            : null;

        $this->merge([
            'phone' => $digits('phone'),
            'document' => $digits('document'),
            'zip' => $digits('zip'),
            'state' => $this->filled('state') ? strtoupper(trim((string) $this->input('state'))) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'digits_between:10,11'],
            'email' => ['nullable', 'email', 'max:120'],
            'document' => ['nullable', new CpfOrCnpj],
            'zip' => ['required', 'digits:8'],
            'street' => ['required', 'string', 'max:150'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'in:'.implode(',', self::STATES)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The validated data with the types the order needs. Optional fields
     * left blank become null.
     *
     * @return array{name: string, phone: string, email: ?string, document: ?string, zip: string, street: string, number: string, complement: ?string, district: string, city: string, state: string, notes: ?string}
     */
    public function customer(): array
    {
        $optional = fn (string $field): ?string => $this->filled($field) ? trim($this->string($field)->toString()) : null;

        return [
            'name' => trim($this->string('name')->toString()),
            'phone' => $this->string('phone')->toString(),
            'email' => $optional('email'),
            'document' => $optional('document'),
            'zip' => $this->string('zip')->toString(),
            'street' => trim($this->string('street')->toString()),
            'number' => trim($this->string('number')->toString()),
            'complement' => $optional('complement'),
            'district' => trim($this->string('district')->toString()),
            'city' => trim($this->string('city')->toString()),
            'state' => $this->string('state')->toString(),
            'notes' => $optional('notes'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'name.min' => 'Informe seu nome completo.',
            'phone.required' => 'Informe um telefone para contato.',
            'phone.digits_between' => 'Informe o telefone com DDD, por exemplo (11) 99999-0000.',
            'email.email' => 'Informe um e-mail válido.',
            'zip.required' => 'Informe o CEP.',
            'zip.digits' => 'O CEP tem 8 números.',
            'street.required' => 'Informe a rua.',
            'number.required' => 'Informe o número (ou "s/n").',
            'district.required' => 'Informe o bairro.',
            'city.required' => 'Informe a cidade.',
            'state.required' => 'Informe o estado (UF).',
            'state.in' => 'Escolha um estado válido.',
        ];
    }
}
