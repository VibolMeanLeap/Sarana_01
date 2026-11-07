@extends(strtolower((string) auth()->user()->role?->role_name) === 'cashier' ? 'layout.cashier' : 'layout.admin')
@section('page_title', 'Payment successful')
@section('topbar_title', 'Payment confirmed')
@section('content')
@php
    $order = $payment->order;
    $currency = strtoupper((string) $payment->currency);
    $symbol = $currency === 'KHR' ? '៛' : '$';
    $amount = number_format((float) $payment->amount, $currency === 'KHR' ? 0 : 2);
    $roleName = strtolower((string) auth()->user()->role?->role_name);
    $queueUrl = $roleName === 'cashier' ? route('cashier.orders') : route('admin.orders.index');
@endphp
@push('styles')
<style>
    .pos-paid-wrap{max-width:980px;margin:18px auto 36px}.pos-paid-card{overflow:hidden;border:1px solid #e5eee6;border-radius:23px;background:#fff;box-shadow:0 18px 48px rgba(28,76,43,.09)}.pos-paid-head{position:relative;overflow:hidden;padding:34px 28px 28px;text-align:center;color:#fff;background:linear-gradient(120deg,#0f4d37,#218052)}.pos-paid-head:after{content:"";position:absolute;width:300px;height:300px;border:1px solid #ffffff24;border-radius:50%;top:-190px;right:-45px}.pos-paid-icon{width:70px;height:70px;margin:0 auto 14px;border-radius:50%;display:grid;place-items:center;background:#fff;color:#218052;font-size:32px;box-shadow:0 0 0 9px #ffffff20;animation:pos-paid-pop .45s ease both}.pos-paid-head h1{font:800 29px 'Manrope',sans-serif;letter-spacing:-.8px;margin-bottom:7px}.pos-paid-head p{font-size:12px;color:#d9efe1;margin:0}.pos-paid-body{padding:26px clamp(18px,4vw,36px) 30px}.pos-paid-amount{font:800 32px 'Manrope',sans-serif;color:#176b4b;letter-spacing:-1px}.pos-paid-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px;margin:20px 0}.pos-paid-meta{padding:13px;border:1px solid #edf1ed;border-radius:12px;background:#fbfcfb}.pos-paid-meta span{display:block;color:#869187;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px}.pos-paid-meta strong{display:block;color:#2c3b31;font-size:13px;margin-top:5px;overflow-wrap:anywhere}.pos-paid-items{border:1px solid #edf1ed;border-radius:13px;padding:14px 16px}.pos-paid-item{display:flex;justify-content:space-between;gap:12px;padding:8px 0;font-size:12px;border-bottom:1px solid #f0f3f0}.pos-paid-item:last-child{border-bottom:0}.pos-paid-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:22px}.pos-paid-actions .btn{border-radius:10px;font-weight:800;font-size:12px;padding:11px 16px}@keyframes pos-paid-pop{0%{transform:scale(.65);opacity:0}80%{transform:scale(1.07)}100%{transform:scale(1);opacity:1}}@media(max-width:575px){.pos-paid-grid{grid-template-columns:1fr}.pos-paid-head{padding:28px 18px}.pos-paid-head h1{font-size:24px}}
</style>
@endpush
<div class="pos-paid-wrap">
    <div class="pos-paid-card">
        <div class="pos-paid-head">
            <div class="pos-paid-icon"><i class="bi bi-check-lg"></i></div>
            <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:1.3px;color:#c8e9d2">ABA PayWay · Verified</div>
            <h1>Payment received!</h1>
            <p>Transaction approved and the POS order has been marked as paid.</p>
        </div>
        <div class="pos-paid-body">
            <div class="text-center pb-3 border-bottom"><div class="small text-muted">Amount received</div><div class="pos-paid-amount">{{ $symbol }}{{ $amount }} <span class="fs-6">{{ $currency }}</span></div><div class="small text-muted mt-1">Order #{{ $order->order_id }} · {{ $payment->tran_id }}</div></div>
            <div class="pos-paid-grid">
                <div class="pos-paid-meta"><span>Customer</span><strong>{{ $order->customer->name ?? 'Walk-in customer' }}</strong></div>
                <div class="pos-paid-meta"><span>Payment method</span><strong>{{ $order->paymentMethod->payment_name ?? 'ABA PayWay KHQR' }}</strong></div>
                <div class="pos-paid-meta"><span>Approval reference</span><strong>{{ $payment->apv ?: $payment->bank_ref ?: $payment->tran_id }}</strong></div>
            </div>
            <div class="pos-paid-items">
                <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.8px">Sale details</div>
                @foreach($order->orderItems as $item)
                    <div class="pos-paid-item"><span>{{ $item->product->product_name ?? 'Product' }} × {{ $item->quantity }}</span><strong>${{ number_format((float)$item->price * (int)$item->quantity, 2) }}</strong></div>
                @endforeach
            </div>
            <div class="alert alert-success border-0 rounded-3 small mt-3 mb-0"><i class="bi bi-shield-check me-2"></i>Payment status was verified server-to-server with ABA PayWay. This sale is recorded as paid.</div>
            <div class="pos-paid-actions"><a href="{{ $queueUrl }}" class="btn btn-success"><i class="bi bi-receipt me-2"></i>Back to order queue</a><a href="{{ route('pos.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-plus-circle me-2"></i>Start another sale</a><button type="button" class="btn btn-outline-secondary ms-sm-auto" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print confirmation</button></div>
        </div>
    </div>
</div>
@endsection
