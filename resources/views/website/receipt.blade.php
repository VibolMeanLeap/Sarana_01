<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt · Order #{{ $order->order_id }}</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #24372c; font: 11px/1.5 "DejaVu Sans", sans-serif; }
        .topline { height: 5px; background: #176b4b; margin-bottom: 22px; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        .header td { vertical-align: top; }
        .brand { color: #176b4b; font-size: 21px; font-weight: bold; }
        .muted { color: #718078; }
        .right { text-align: right; }
        .receipt-label { color: #718078; font-size: 9px; font-weight: bold; letter-spacing: 1.5px; }
        .order-number { color: #183326; font-size: 25px; font-weight: bold; }
        .status { display: inline-block; margin-top: 7px; padding: 5px 9px; border-radius: 12px; background: #e8f4ec; color: #176b4b; font-size: 9px; font-weight: bold; }
        .section-title { margin: 20px 0 8px; color: #315b43; font-size: 10px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .details { width: 100%; border-collapse: collapse; }
        .details td { width: 50%; padding: 8px 10px; border: 1px solid #e4ece6; vertical-align: top; }
        .label { display: block; margin-bottom: 3px; color: #718078; font-size: 9px; }
        .value { color: #24372c; font-size: 10px; font-weight: bold; }
        .items { width: 100%; border-collapse: collapse; margin-top: 9px; }
        .items th { padding: 9px 8px; background: #eaf4ed; color: #315b43; font-size: 9px; text-align: left; text-transform: uppercase; }
        .items td { padding: 9px 8px; border-bottom: 1px solid #e8efea; }
        .num { text-align: right !important; white-space: nowrap; }
        .totals { width: 55%; margin: 15px 0 0 auto; border-collapse: collapse; }
        .totals td { padding: 5px 7px; }
        .totals .amount { text-align: right; font-weight: bold; }
        .grand td { padding-top: 10px; border-top: 2px solid #dce9df; color: #176b4b; font-size: 14px; font-weight: bold; }
        .note { margin-top: 20px; padding: 11px 13px; border: 1px solid #dcebe0; background: #f1f8f3; color: #53675a; }
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e4ece6; color: #718078; text-align: center; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $shopName = trim((string) ($shopSettings?->shop_name ?? '')) ?: config('app.name', 'SK Coffee');
        $isCod = in_array(strtolower(trim((string) $order->paymentMethod?->payment_name)), ['cash on delivery', 'cash-on-delivery', 'cod'], true);
        $paymentLabel = $order->is_paid ? 'PAID' : ($isCod ? 'DUE ON DELIVERY' : strtoupper((string) ($order->payment_status ?: 'UNPAID')));
        $itemSubtotal = (float) $order->orderItems->sum(fn ($item) => (float) $item->price * (int) $item->quantity);
        $deliveryFee = (float) ($order->delivery?->cost ?? 0);
        $discountAmount = $order->discount_amount !== null
            ? (float) $order->discount_amount
            : round((float) $order->total_amount * (float) $order->discount / 100, 2);
        $orderStatus = ucfirst(str_replace('_', ' ', (string) ($order->order_status ?: 'pending')));
        $latestPaymentReference = $payment?->tran_id;
    @endphp

    <div class="topline"></div>
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ $shopName }}</div>
                @if(filled($shopSettings?->address))<div class="muted">{{ $shopSettings->address }}</div>@endif
                @if(filled($shopSettings?->tel))<div class="muted">Tel: {{ $shopSettings->tel }}</div>@endif
            </td>
            <td class="right">
                <div class="receipt-label">CUSTOMER RECEIPT</div>
                <div class="order-number">#{{ $order->order_id }}</div>
                <div class="muted">{{ optional($order->order_date)->format('d M Y, h:i A') ?: '—' }}</div>
                <div class="status">{{ $paymentLabel }}</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Order details</div>
    <table class="details">
        <tr>
            <td><span class="label">Customer</span><span class="value">{{ $order->customer?->name ?: 'Customer' }}</span></td>
            <td><span class="label">Phone</span><span class="value">{{ $order->customer?->phone ?: '—' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Fulfilment</span><span class="value">{{ $order->orderType?->order_name ?: '—' }}</span></td>
            <td><span class="label">Order status</span><span class="value">{{ $orderStatus }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Payment method</span><span class="value">{{ $order->paymentMethod?->payment_name ?: '—' }}</span></td>
            <td><span class="label">Payment status</span><span class="value">{{ $paymentLabel }}</span></td>
        </tr>
        @if($order->delivery?->address)
            <tr><td colspan="2"><span class="label">Delivery address</span><span class="value">{{ $order->delivery->address }}</span></td></tr>
        @endif
        @if(filled($latestPaymentReference))
            <tr><td colspan="2"><span class="label">Payment reference</span><span class="value">{{ $latestPaymentReference }}</span></td></tr>
        @endif
    </table>

    <div class="section-title">Items purchased</div>
    <table class="items">
        <thead>
            <tr><th>Item</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Amount</th></tr>
        </thead>
        <tbody>
            @forelse($order->orderItems as $item)
                <tr>
                    <td>{{ $item->product?->product_name ?: ('Product #'.($item->product_id ?? '')) }}</td>
                    <td class="num">{{ (int) $item->quantity }}</td>
                    <td class="num">${{ number_format((float) $item->price, 2) }}</td>
                    <td class="num">${{ number_format((float) $item->price * (int) $item->quantity, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">No item lines were recorded for this order.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Items subtotal</td><td class="amount">${{ number_format($itemSubtotal, 2) }}</td></tr>
        @if($deliveryFee > 0)
            <tr><td class="muted">Delivery fee</td><td class="amount">${{ number_format($deliveryFee, 2) }}</td></tr>
        @endif
        @if($discountAmount > 0)
            <tr><td class="muted">Discount</td><td class="amount">−${{ number_format($discountAmount, 2) }}</td></tr>
        @endif
        <tr class="grand"><td>Order total</td><td class="amount">${{ number_format((float) ($order->final_price ?? $order->total_amount), 2) }}</td></tr>
    </table>

    @if($order->customer_note)
        <div class="note"><strong>Customer note:</strong> {{ $order->customer_note }}</div>
    @endif

    <div class="footer">
        Thank you for choosing {{ $shopName }}.<br>
        This receipt was generated electronically on {{ now()->format('d M Y, h:i A') }}.
    </div>
</body>
</html>
