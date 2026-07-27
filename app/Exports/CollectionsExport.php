<?php

namespace App\Exports;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Successful collections within a date range, scoped to a company.
 */
class CollectionsExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly int $companyId,
        private readonly string $from,
        private readonly string $to,
    ) {}

    public function collection()
    {
        return PaymentTransaction::query()
            ->where('company_id', $this->companyId)
            ->where('status', PaymentStatus::Success->value)
            ->whereBetween('created_at', [$this->from.' 00:00:00', $this->to.' 23:59:59'])
            ->with(['customer:id,name', 'chitty:id,code'])
            ->latest()
            ->get()
            ->map(fn (PaymentTransaction $t) => [
                $t->reference,
                $t->created_at?->toDateString(),
                $t->customer?->name,
                $t->chitty?->code,
                ucfirst($t->gateway),
                $t->method?->label(),
                number_format((float) $t->amount, 2, '.', ''),
            ]);
    }

    public function headings(): array
    {
        return ['Reference', 'Date', 'Customer', 'Chitty', 'Gateway', 'Method', 'Amount (INR)'];
    }
}
