<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    /** The customer's own installment obligations, filterable by status. */
    public function index(Request $request): JsonResponse
    {
        $installments = InstallmentPayment::where('customer_id', $request->user()->id)
            ->when($request->boolean('outstanding_only'), fn ($q) => $q->outstanding())
            ->with('chitty:id,code,name')
            ->orderBy('due_date')
            ->paginate(20)
            ->through(fn (InstallmentPayment $p) => [
                'id' => $p->ulid,
                'chitty' => $p->chitty?->code,
                'periodNo' => $p->period_no,
                'dueDate' => $p->due_date?->toDateString(),
                'amountDue' => (float) $p->amount_due,
                'lateFee' => (float) $p->late_fee,
                'outstanding' => (float) $p->outstanding(),
                'status' => $p->status->value,
            ]);

        return ApiResponse::success($installments);
    }
}
