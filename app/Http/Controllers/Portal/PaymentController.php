<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Payments\GenerateReceipt;
use App\Domain\Payments\InitiateOnlinePayment;
use App\Domain\Payments\PaymentGatewayManager;
use App\Domain\Payments\SettlePayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Models\PaymentReceipt;
use App\Models\PaymentTransaction;
use App\Notifications\PaymentReceived;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Create a gateway order and return the parameters the Razorpay Checkout
     * popup needs. Called via fetch (JSON) from the ledger "Pay" button. If no
     * gateway credentials are configured we respond with {configured:false} so
     * the UI can fall back gracefully instead of erroring.
     */
    public function checkout(Request $request, InitiateOnlinePayment $initiator): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'installment_id' => [
                'required',
                Rule::exists('installment_payments', 'ulid')->where('customer_id', $user->id),
            ],
        ]);

        abort_unless($user->can('payments.make'), 403);

        $installment = InstallmentPayment::where('ulid', $validated['installment_id'])
            ->where('customer_id', $user->id)
            ->firstOrFail();

        try {
            $result = $initiator->handle($installment, PaymentMethod::Upi);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'configured' => false,
                'message' => 'Online payments are not enabled yet. Please pay at your branch.',
            ]);
        }

        $gateway = $result['gateway'];

        return response()->json([
            'configured' => true,
            'key' => $gateway['key'] ?? null,
            'orderId' => $gateway['order_id'] ?? null,
            'amount' => $gateway['amount'] ?? null,
            'currency' => $gateway['currency'] ?? 'INR',
            'reference' => $result['transaction']->reference,
            'name' => $user->name,
            'email' => $user->email,
            'contact' => $user->phone,
        ]);
    }

    /**
     * Confirm a completed Razorpay Checkout. Verifies the signature the browser
     * received, then settles the installment and issues a receipt. This mirrors
     * the webhook path and is fully idempotent, so whichever arrives first
     * (client callback or webhook) wins and the other is a no-op.
     */
    public function verify(Request $request, SettlePayment $settlePayment, GenerateReceipt $generateReceipt): RedirectResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
            'reference' => ['required', 'string'],
        ]);

        $transaction = PaymentTransaction::where('reference', $validated['reference'])
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        $gateway = app(PaymentGatewayManager::class)->gateway('razorpay');

        $valid = $gateway->verifyCheckoutSignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
        );

        if (! $valid) {
            return back()->with('error', 'Payment could not be verified. If money was debited it will be reconciled automatically.');
        }

        DB::transaction(function () use ($transaction, $validated, $settlePayment, $generateReceipt) {
            if ($transaction->status === PaymentStatus::Success) {
                return; // already settled by the webhook — idempotent
            }

            $transaction->update([
                'status' => PaymentStatus::Success->value,
                'gateway_payment_id' => $validated['razorpay_payment_id'],
                'reconciled_at' => now(),
            ]);

            $installmentId = data_get($transaction->meta, 'installment_payment_id');
            if ($installmentId && ($installment = InstallmentPayment::find($installmentId))) {
                $settlePayment->settle($transaction, $installment);
            }

            $generateReceipt->forTransaction($transaction);

            $transaction->customer?->notify(new PaymentReceived($transaction->fresh()->load('receipt', 'chitty')));
        });

        return back()->with('success', 'Payment successful. Your receipt is available in Payment history.');
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
