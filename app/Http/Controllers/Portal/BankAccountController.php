<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CustomerBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'regex:/^[0-9]{6,20}$/'],
            'ifsc' => ['required', 'string', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_name' => ['required', 'string', 'max:255'],
            'branch_name' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $makePrimary = $validated['is_primary'] ?? ! $user->bankAccounts()->exists();

            if ($makePrimary) {
                $user->bankAccounts()->update(['is_primary' => false]);
            }

            $user->bankAccounts()->create([
                'company_id' => $user->company_id,
                'account_holder_name' => $validated['account_holder_name'],
                'account_number' => $validated['account_number'], // encrypted by cast
                'account_number_last4' => substr($validated['account_number'], -4),
                'ifsc' => strtoupper($validated['ifsc']),
                'bank_name' => $validated['bank_name'],
                'branch_name' => $validated['branch_name'] ?? null,
                'is_primary' => $makePrimary,
            ]);
        });

        return back()->with('success', 'Bank account added.');
    }

    public function primary(Request $request, CustomerBankAccount $bankAccount): RedirectResponse
    {
        abort_unless($bankAccount->customer_id === $request->user()->id, 403);

        DB::transaction(function () use ($request, $bankAccount) {
            $request->user()->bankAccounts()->update(['is_primary' => false]);
            $bankAccount->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary account updated.');
    }

    public function destroy(Request $request, CustomerBankAccount $bankAccount): RedirectResponse
    {
        abort_unless($bankAccount->customer_id === $request->user()->id, 403);

        $bankAccount->delete();

        return back()->with('success', 'Bank account removed.');
    }
}
