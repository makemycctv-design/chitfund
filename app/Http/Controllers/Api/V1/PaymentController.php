<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payments\InitiateOnlinePayment;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class PaymentController extends Controller
{
    /** Payment history for the mobile app. */
    public function index(Request $request): JsonResponse
    {
        $transactions = $request->user()->paymentTransactions()
            ->with('receipt:id,payment_transaction_id,receipt_number')
            ->latest()
            ->paginate(20)
            ->through(fn ($t) => [
                'id' => $t->ulid,
                'reference' => $t->reference,
                'amount' => (float) $t->amount,
                'method' => $t->method?->value,
                'status' => $t->status->value,
                'receiptNumber' => $t->receipt?->receipt_number,
                'createdAt' => $t->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success($transactions);
    }

    /** Initiate an online payment for one of the customer's installments. */
    public function initiate(Request $request, InitiateOnlinePayment $initiator): JsonResponse
    {
        abort_unless($request->user()->can('payments.make'), 403);

        $validated = $request->validate([
            'installment_id' => [
                'required',
                Rule::exists('installment_payments', 'ulid')->where('customer_id', $request->user()->id),
            ],
            'method' => ['required', new Enum(PaymentMethod::class)],
        ]);

        $installment = InstallmentPayment::where('ulid', $validated['installment_id'])
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        try {
            $result = $initiator->handle($installment, PaymentMethod::from($validated['method']));
        } catch (\Throwable $e) {
            return ApiResponse::error('Online payments are not available yet.', 503);
        }

        return ApiResponse::success([
            'reference' => $result['transaction']->reference,
            'gateway' => $result['gateway'],
        ], 'Payment initiated.');
    }
}
