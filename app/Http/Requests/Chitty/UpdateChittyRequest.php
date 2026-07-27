<?php

namespace App\Http\Requests\Chitty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChittyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-model-bound chitty; policy enforces permission + company match.
        return $this->user()->can('update', $this->route('chitty'));
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $chittyId = $this->route('chitty')->id;

        return [
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'chitty_scheme_id' => ['nullable', Rule::exists('chitty_schemes', 'id')->where('company_id', $companyId)],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('chitties', 'code')->where('company_id', $companyId)->ignore($chittyId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'chit_value' => ['required', 'numeric', 'gt:0'],
            'duration_months' => ['required', 'integer', 'min:2', 'max:120'],
            'total_subscribers' => ['required', 'integer', 'min:2', 'max:500'],
            'installment_amount' => ['required', 'numeric', 'gt:0'],
            'foreman_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'auction_frequency' => ['required', Rule::in(['monthly', 'fortnightly', 'weekly'])],
            'min_bid_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_bid_percent' => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:min_bid_percent'],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:60'],
            'late_fee_type' => ['required', Rule::in(['none', 'fixed', 'percent'])],
            'late_fee_value' => ['required', 'numeric', 'min:0'],
            'required_kyc_level' => ['required', 'integer', 'min:0', 'max:3'],
            'enrollment_opens_on' => ['nullable', 'date'],
            'enrollment_closes_on' => ['nullable', 'date', 'after_or_equal:enrollment_opens_on'],
            'start_date' => ['nullable', 'date'],
            'maturity_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'auction_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'auction_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
        ];
    }
}
