<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChittyMembership;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only chitty endpoints for the customer's own memberships (mobile app).
 */
class ChittyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $memberships = ChittyMembership::where('customer_id', $request->user()->id)
            ->with('chitty:id,ulid,code,name,installment_amount,duration_months,status,maturity_date')
            ->get()
            ->map(fn (ChittyMembership $m) => [
                'id' => $m->chitty?->ulid,
                'code' => $m->chitty?->code,
                'name' => $m->chitty?->name,
                'ticketNumber' => $m->ticket_number,
                'installmentAmount' => (float) ($m->chitty?->installment_amount ?? 0),
                'status' => $m->status->value,
                'isPrized' => $m->is_prized,
            ]);

        return ApiResponse::success($memberships);
    }

    public function show(Request $request, string $chitty): JsonResponse
    {
        $membership = ChittyMembership::where('customer_id', $request->user()->id)
            ->whereHas('chitty', fn ($q) => $q->where('ulid', $chitty))
            ->with(['chitty', 'installmentPayments' => fn ($q) => $q->orderBy('period_no')])
            ->first();

        if (! $membership) {
            return ApiResponse::error('Chitty not found.', 404);
        }

        $c = $membership->chitty;

        return ApiResponse::success([
            'id' => $c->ulid,
            'code' => $c->code,
            'name' => $c->name,
            'installmentAmount' => (float) $c->installment_amount,
            'durationMonths' => $c->duration_months,
            'ticketNumber' => $membership->ticket_number,
            'status' => $c->status->value,
            'installments' => $membership->installmentPayments->map(fn ($p) => [
                'id' => $p->ulid,
                'periodNo' => $p->period_no,
                'dueDate' => $p->due_date?->toDateString(),
                'amountDue' => (float) $p->amount_due,
                'outstanding' => (float) $p->outstanding(),
                'status' => $p->status->value,
            ]),
        ]);
    }
}
