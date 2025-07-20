<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Payment Invoice</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #222; }
        .header { background: #4f46e5; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; }
        .container { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #eee; padding: 30px; margin: 0 auto; max-width: 600px; }
        .title { font-size: 2em; font-weight: bold; margin-bottom: 10px; }
        .subtitle { font-size: 1.1em; color: #555; margin-bottom: 20px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .label { font-weight: 500; color: #4f46e5; }
        .value { font-weight: 600; }
        .amount { font-size: 1.3em; font-weight: bold; color: #10b981; }
        .footer { margin-top: 30px; font-size: 0.95em; color: #888; text-align: center; }
        .divider { border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">Salary Payment Invoice</div>
            <div class="subtitle">Invoice #: {{ $salaryPayment->invoice_number ?? $salaryPayment->id }}</div>
        </div>
        <div class="divider"></div>
        <div class="row">
            <div class="label">Cashier Name:</div>
            <div class="value">{{ $salaryPayment->cashier->name ?? '-' }}</div>
        </div>
        <div class="row">
            <div class="label">Payment Date:</div>
            <div class="value">{{ \Carbon\Carbon::parse($salaryPayment->payment_date)->format('M d, Y') }}</div>
        </div>
        <div class="row">
            <div class="label">Payment Period:</div>
            <div class="value">{{ $salaryPayment->payment_period }}</div>
        </div>
        <div class="row">
            <div class="label">Payment Method:</div>
            <div class="value">{{ ucfirst($salaryPayment->payment_method) }}</div>
        </div>
        <div class="row">
            <div class="label">Status:</div>
            <div class="value">{{ $salaryPayment->status }}</div>
        </div>
        <div class="divider"></div>
        <div class="row">
            <div class="label">Base Salary:</div>
            <div class="value">LKR {{ number_format($salaryPayment->base_salary, 2) }}</div>
        </div>
        <div class="row">
            <div class="label">Additions (OT, Bonus):</div>
            <div class="value">LKR {{ number_format($salaryPayment->additions, 2) }}</div>
        </div>
        <div class="row">
            <div class="label">Deductions (Advance, Leave):</div>
            <div class="value">LKR {{ number_format($salaryPayment->deductions, 2) }}</div>
        </div>
        <div class="divider"></div>
        <div class="row">
            <div class="label">Net Pay:</div>
            <div class="amount">LKR {{ number_format($salaryPayment->net_pay, 2) }}</div>
        </div>
        <div class="divider"></div>
        <div class="row">
            <div class="label">Notes:</div>
            <div class="value">{{ $salaryPayment->notes ?? '-' }}</div>
        </div>
        <div class="footer">
            Generated on {{ \Carbon\Carbon::now()->format('M d, Y H:i') }}<br>
            Thank you for your service!
        </div>
    </div>
</body>
</html>
