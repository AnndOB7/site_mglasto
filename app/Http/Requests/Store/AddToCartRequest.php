<?php

namespace App\Http\Requests\Store;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AddToCartRequest extends FormRequest
{
    private ?Product $product = null;

    private ?ProductVariant $variant = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only ids and quantity are accepted: prices come from the database.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.Cart::MAX_QUANTITY],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.min' => 'A quantidade mínima é 1.',
            'quantity.max' => 'A quantidade máxima é :max.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->product = Product::query()->active()->with('variants')->find($this->integer('product_id'));

                if ($this->product === null) {
                    $validator->errors()->add('product_id', 'Este produto não está mais disponível.');

                    return;
                }

                $activeVariants = $this->product->variants->where('is_active', true);

                if ($activeVariants->isNotEmpty()) {
                    $this->variant = $activeVariants->firstWhere('id', $this->integer('variant_id'));

                    if ($this->variant === null) {
                        $validator->errors()->add('variant_id', 'Escolha uma opção do produto.');

                        return;
                    }

                    $this->variant->setRelation('product', $this->product);
                } elseif ($this->filled('variant_id')) {
                    $validator->errors()->add('variant_id', 'Este produto não tem opções.');

                    return;
                }

                $inStock = $this->variant?->isInStock() ?? $this->product->isInStock();

                if (! $inStock) {
                    $validator->errors()->add('quantity', 'Este produto está indisponível no momento.');
                }
            },
        ];
    }

    public function product(): Product
    {
        return $this->product ?? throw new \LogicException('Validate the request first.');
    }

    public function variant(): ?ProductVariant
    {
        return $this->variant;
    }
}
