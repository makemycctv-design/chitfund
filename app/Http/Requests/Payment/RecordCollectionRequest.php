<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RecordCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('collections.record') ?? false;
    }

    public function rules(): array
    {
        return [
            'installment_id' => [
                'required',
                Rule::exists('installment_payments', 'ulid')
                    ->where('company_id', $this->user()->company_id),
            ],
            'method' => ['required', new Enum(PaymentMethod::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ];
    }
}
