<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; margin: 0; padding: 32px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 24px; }
        .company { font-size: 18px; font-weight: bold; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 6px 4px; vertical-align: top; }
        .label { color: #6b7280; width: 40%; }
        .value { font-weight: bold; }
        .amount-box { margin-top: 24px; background: #f3f4f6; padding: 16px; border-radius: 8px; text-align: right; }
        .amount-box .amount { font-size: 22px; font-weight: bold; color: #047857; }
        .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company">{{ $receipt->company->name ?? 'ChittyFund' }}</div>
            <div style="color:#6b7280;">Payment Receipt</div>
        </div>
        <div style="text-align:right;">
            <div class="title">Receipt</div>
            <div>#{{ $receipt->receipt_number }}</div>
            <div style="color:#6b7280;">{{ $receipt->issued_at?->format('d M Y, h:i A') }}</div>
        </div>
    </div>

    <table>
        <tr>
            <td class="label">Received From</td>
            <td class="value">{{ $receipt->customer->name }}</td>
        </tr>
        <tr>
            <td class="label">Email / Phone</td>
            <td>{{ $receipt->customer->email }} @if($receipt->customer->phone) / {{ $receipt->customer->phone }} @endif</td>
        </tr>
        @if($receipt->transaction?->chitty)
        <tr>
            <td class="label">Chitty</td>
            <td>{{ $receipt->transaction->chitty->code }} — {{ $receipt->transaction->chitty->name }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Payment Reference</td>
            <td>{{ $receipt->transaction?->reference }}</td>
        </tr>
        <tr>
            <td class="label">Method</td>
            <td>{{ ucfirst($receipt->transaction?->gateway) }} / {{ ucfirst(str_replace('_', ' ', $receipt->transaction?->method?->value ?? '')) }}</td>
        </tr>
    </table>

    <div class="amount-box">
        <div style="color:#6b7280; font-size:11px;">Amount Received</div>
        <div class="amount">&#8377; {{ number_format((float) $receipt->amount, 2) }}</div>
    </div>

    <div class="footer">
        This is a system-generated receipt and does not require a signature.<br>
        {{ $receipt->company->name ?? 'ChittyFund' }} — generated on {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
