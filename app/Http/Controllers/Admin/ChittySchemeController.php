<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChittyScheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChittySchemeController extends Controller
{
    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->can('chitty-schemes.manage'), 403);
    }

    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    public function index(Request $request): Response
    {
        $this->authorizeManage($request);

        $schemes = ChittyScheme::where('company_id', $this->companyId($request))
            ->withCount('chitties')
            ->latest()
            ->get()
            ->map(fn (ChittyScheme $s) => [
                'id' => $s->ulid,
                'code' => $s->code,
                'name' => $s->name,
                'chitValue' => (float) $s->chit_value,
                'durationMonths' => $s->duration_months,
                'installmentAmount' => (float) $s->installment_amount,
                'foremanCommissionPercent' => (float) $s->foreman_commission_percent,
                'isActive' => $s->is_active,
                'chittiesCount' => $s->chitties_count,
            ]);

        return Inertia::render('admin/schemes/index', ['schemes' => $schemes]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        $data = $this->validated($request);

        $scheme = new ChittyScheme($data);
        $scheme->company_id = $this->companyId($request);
        $scheme->save();

        return back()->with('success', "Scheme {$scheme->code} created.");
    }

    public function update(Request $request, ChittyScheme $scheme): RedirectResponse
    {
        $this->authorizeManage($request);
        abort_unless($scheme->company_id === $this->companyId($request), 403);

        $scheme->update($this->validated($request, $scheme->id));

        return back()->with('success', 'Scheme updated.');
    }

    public function destroy(Request $request, ChittyScheme $scheme): RedirectResponse
    {
        $this->authorizeManage($request);
        abort_unless($scheme->company_id === $this->companyId($request), 403);

        if ($scheme->chitties()->exists()) {
            return back()->with('error', 'Cannot delete a scheme that has chitties.');
        }

        $scheme->delete();

        return back()->with('success', 'Scheme deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $companyId = $this->companyId($request);

        return $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('chitty_schemes', 'code')->where('company_id', $companyId)->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
            'is_active' => ['boolean'],
        ]);
    }
}
