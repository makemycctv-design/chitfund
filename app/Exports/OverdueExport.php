<?php

namespace App\Exports;

use App\Models\InstallmentPayment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Overdue installments (outstanding and past due date), scoped to a company.
 */
class OverdueExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly int $companyId) {}

    public function collection()
    {
        return InstallmentPayment::query()
            ->where('company_id', $this->companyId)
            ->overdue()
            ->with(['customer:id,name,phone', 'chitty:id,code'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (InstallmentPayment $p) => [
                $p->customer?->name,
                $p->customer?->phone,
                $p->chitty?->code,
                $p->period_no,
                $p->due_date?->toDateString(),
                number_format((float) $p->amount_due, 2, '.', ''),
                number_format((float) $p->late_fee, 2, '.', ''),
                number_format((float) $p->outstanding(), 2, '.', ''),
            ]);
    }

    public function headings(): array
    {
        return ['Customer', 'Phone', 'Chitty', 'Period', 'Due Date', 'Amount Due', 'Late Fee', 'Outstanding'];
    }
}
