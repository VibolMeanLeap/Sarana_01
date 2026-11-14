<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt · Order #{{ $order->order_id }}</title>
  <style>
    *{box-sizing:border-box}body{margin:0;padding:26px;background:#edf4ef;color:#22352a;font:14px/1.45 Arial,sans-serif}
    .receipt{max-width:760px;margin:0 auto;background:#fff;border:1px solid #d8e7dc;border-radius:18px;padding:32px;box-shadow:0 12px 32px rgba(25,70,43,.08)}
    .head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;border-bottom:2px solid #e5f0e8;padding-bottom:20px;margin-bottom:20px}
    .brand{font-size:22px;font-weight:800;color:#176b4b}.muted{color:#718078}.meta{display:grid;grid-template-columns:1fr 1fr;gap:8px 18px;margin:18px 0}
    table{width:100%;border-collapse:collapse;margin:20px 0}th,td{padding:10px 8px;border-bottom:1px solid #e8efea;text-align:left}th{background:#eaf4ed;color:#315b43;font-size:11px;text-transform:uppercase}td.num,th.num{text-align:right}
    .totals{width:min(300px,100%);margin-left:auto}.totals div{display:flex;justify-content:space-between;padding:5px 0}.grand{font-size:18px;font-weight:800;color:#176b4b;border-top:2px solid #dce9df;margin-top:6px;padding-top:12px!important}
    .audit{margin-top:22px;padding:14px;border-radius:12px;background:#f1f8f3;border:1px solid #dcebe0}.actions{display:flex;gap:8px;justify-content:flex-end;margin:0 auto 14px;max-width:760px}.btn{border:0;border-radius:9px;padding:10px 14px;background:#176b4b;color:#fff;font-weight:700;cursor:pointer;text-decoration:none}
    @media(max-width:600px){body{padding:10px}.receipt{padding:18px}.head{flex-direction:column}.meta{grid-template-columns:1fr}}
    @media print{body{background:#fff;padding:0}.receipt{max-width:none;border:0;border-radius:0;box-shadow:none;padding:0}.actions{display:none}}
  </style>
</head>
<body>
  <div class="actions"><button class="btn" onclick="window.print()">Print / Save PDF</button></div>
  <main class="receipt">
    <header class="head">
      <div><div class="brand">{{ $shopSettings?->shop_name ?: config('app.name', 'SK Coffee') }}</div><div class="muted">{{ $shopSettings?->address }}</div><div class="muted">{{ $shopSettings?->tel }}</div></div>
      <div style="text-align:right"><div style="font-size:12px;color:#718078;font-weight:700">ORDER RECEIPT</div><div style="font-size:24px;font-weight:800">#{{ $order->order_id }}</div><div class="muted">{{ $order->order_date?->format('d M Y, h:i A') ?: '—' }}</div></div>
    </header>
    <section class="meta">
      <div><span class="muted">Customer</span><br><strong>{{ $order->customer?->name ?: 'Walk-in' }}</strong></div>
      <div><span class="muted">Phone</span><br><strong>{{ $order->customer?->phone ?: '—' }}</strong></div>
      <div><span class="muted">Cashier</span><br><strong>{{ $order->cashier?->name ?: 'Website' }}</strong></div>
      <div><span class="muted">Order type / source</span><br><strong>{{ $order->orderType?->order_name ?: '—' }} / {{ ucfirst($order->order_source ?: 'pos') }}</strong></div>
      <div><span class="muted">Payment</span><br><strong>{{ $order->paymentMethod?->payment_name ?: ($order->is_paid ? 'Paid' : 'Unpaid') }}</strong></div>
      <div><span class="muted">Status</span><br><strong>{{ ucfirst(str_replace('_',' ', $order->order_status ?: ($order->is_paid ? 'completed' : 'pending'))) }} · {{ $order->is_paid ? 'Paid' : ($order->payment_status ?: 'Unpaid') }}</strong></div>
    </section>
    <table><thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Total</th></tr></thead><tbody>
      @forelse($order->orderItems as $item)
        <tr><td>{{ $item->product?->product_name ?: ('Product #'.($item->product_id ?? '')) }}</td><td class="num">{{ (int)$item->quantity }}</td><td class="num">${{ number_format((float)$item->price,2) }}</td><td class="num">${{ number_format((float)$item->price * (int)$item->quantity,2) }}</td></tr>
      @empty
        <tr><td colspan="4" class="muted">No item lines were recorded.</td></tr>
      @endforelse
    </tbody></table>
    <section class="totals">
      <div><span class="muted">Subtotal</span><strong>${{ number_format((float)$order->total_amount,2) }}</strong></div>
      <div><span class="muted">Discount</span><strong>{{ number_format((float)$order->discount,2) }}%</strong></div>
      <div class="grand"><span>Final total</span><span>${{ number_format((float)($order->final_price ?? $order->total_amount),2) }}</span></div>
    </section>
    @if($order->reviewFlags->isNotEmpty())
      <section class="audit"><strong>Internal review notes (not part of customer payment)</strong>
        @foreach($order->reviewFlags as $flag)<div style="margin-top:9px"><strong>{{ \Illuminate\Support\Str::headline($flag->reason) }}</strong> · {{ ucfirst($flag->status) }}<div class="muted">{{ $flag->notes }}</div>@if($flag->related_order_id)<div class="muted">Related order #{{ $flag->related_order_id }}</div>@endif</div>@endforeach
      </section>
    @endif
    <p class="muted" style="text-align:center;margin:26px 0 0">Thank you for choosing {{ $shopSettings?->shop_name ?: 'SK Coffee' }}.</p>
  </main>
</body>
</html>
