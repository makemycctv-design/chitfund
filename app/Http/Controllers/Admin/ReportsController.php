<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Exports\CollectionsExport;
use App\Exports\OverdueExport;
use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Models\PaymentTransaction;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportsController extends Controller
{
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    private function dateRange(Request $request): array
    {
        $from = $request->date('from') ?? Carbon::now()->startOfMonth();
        $to = $request->date('to') ?? Carbon::now()->endOfMonth();

        return [$from->toDateString(), $to->toDateString()];
    }

    /** Collections report: daily totals + summary within a date range. */
    public function collections(Request $request): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);
        [$from, $to] = $this->dateRange($request);

        $base = PaymentTransaction::query()
            ->where('company_id', $this->companyId($request))
            ->where('status', PaymentStatus::Success->value)
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        $daily = (clone $base)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => [
                'day' => $r->day,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ]);

        return Inertia::render('admin/reports/collections', [
            'range' => ['from' => $from, 'to' => $to],
            'summary' => [
                'total' => (float) (clone $base)->sum('amount'),
                'count' => (clone $base)->count(),
            ],
            'daily' => $daily,
        ]);
    }

    /** Overdue installments report. */
    public function overdue(Request $request): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $query = InstallmentPayment::query()
            ->where('company_id', $this->companyId($request))
            ->overdue();

        $totalOutstanding = (clone $query)->get()->reduce(
            fn (string $carry, InstallmentPayment $p) => Money::add($carry, $p->outstanding()),
            '0.00',
        );

        $rows = (clone $query)
            ->with(['customer:id,name,phone', 'chitty:id,code'])
            ->orderBy('due_date')
            ->limit(200)
            ->get()
            ->map(fn (InstallmentPayment $p) => [
                'customer' => $p->customer?->name,
                'phone' => $p->customer?->phone,
                'chitty' => $p->chitty?->code,
                'periodNo' => $p->period_no,
                'dueDate' => $p->due_date?->toDateString(),
                'outstanding' => (float) $p->outstanding(),
            ]);

        return Inertia::render('admin/reports/overdue', [
            'summary' => [
                'count' => (clone $query)->count(),
                'totalOutstanding' => (float) $totalOutstanding,
            ],
            'rows' => $rows,
        ]);
    }

    public function exportCollections(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);
        [$from, $to] = $this->dateRange($request);

        return Excel::download(
            new CollectionsExport($this->companyId($request), $from, $to),
            "collections_{$from}_to_{$to}.xlsx",
        );
    }

    public function exportOverdue(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        return Excel::download(
            new OverdueExport($this->companyId($request)),
            'overdue_installments.xlsx',
        );
    }
}
