<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Payments\InitiateOnlinePayment;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Models\PaymentReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PaymentController extends Controller
{
    /** Payment + receipt history for the signed-in customer. */
    public function history(Request $request): Response
    {
        $user = $request->user();

        $transactions = $user->paymentTransactions()
            ->with(['chitty:id,code', 'receipt:id,ulid,payment_transaction_id,receipt_number'])
            ->latest()
            ->paginate(15)
            ->through(fn ($t) => [
                'id' => $t->ulid,
                'reference' => $t->reference,
                'chitty' => $t->chitty?->code,
                'method' => $t->method?->value,
                'amount' => (float) $t->amount,
                'status' => $t->status->value,
                'receiptNumber' => $t->receipt?->receipt_number,
                'receiptId' => $t->receipt?->ulid,
                'createdAt' => $t->created_at?->toIso8601String(),
            ]);

        return Inertia::render('portal/payments/history', ['transactions' => $transactions]);
    }

    /** Initiate an online payment for one of the customer's own installments. */
    public function initiate(Request $request, InitiateOnlinePayment $initiator): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'installment_id' => [
                'required',
                Rule::exists('installment_payments', 'ulid')->where('customer_id', $user->id),
            ],
            'method' => ['required', new Enum(PaymentMethod::class)],
        ]);

        abort_unless($user->can('payments.make'), 403);

        $installment = InstallmentPayment::where('ulid', $validated['installment_id'])
            ->where('customer_id', $user->id)
            ->firstOrFail();

        try {
            $result = $initiator->handle($installment, PaymentMethod::from($validated['method']));
        } catch (\Throwable $e) {
            // Typically raised when no gateway credentials are configured.
            return back()->with('error', 'Online payments are not available yet. Please pay at your branch.');
        }

        return back()->with('success', "Payment initiated (ref {$result['transaction']->reference}). Complete it in the gateway; your ledger updates once confirmed.");
    }

    /** Download a PDF receipt owned by the customer. */
    public function receipt(Request $request, PaymentReceipt $receipt): SymfonyResponse
    {
        abort_unless($receipt->customer_id === $request->user()->id, 403);

        $receipt->load(['transaction.chitty:id,code,name', 'customer:id,name,email,phone', 'company:id,name']);

        $pdf = Pdf::loadView('receipts.payment', ['receipt' => $receipt]);

        return $pdf->download("receipt_{$receipt->receipt_number}.pdf");
    }
}
