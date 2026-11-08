@extends('layout.web')
@section('web_title', 'Payment successful')
@section('content')
@php
    $order = $payment->order;
    $currency = strtoupper((string) $payment->currency);
    $symbol = $currency === 'KHR' ? '៛' : '$';
    $amount = number_format((float) $payment->amount, $currency === 'KHR' ? 0 : 2);
@endphp
<style>
    .paid-page{min-height:70vh;padding:54px 0 72px;background:radial-gradient(circle at 50% 0%,#e4f4e8,transparent 43%),#f7faf7}.paid-card{max-width:760px;margin:auto;background:#fff;border:1px solid #e5eee6;border-radius:26px;overflow:hidden;box-shadow:0 24px 65px rgba(28,76,43,.10)}.paid-banner{padding:38px 25px 28px;text-align:center;background:linear-gradient(135deg,#0f4d37,#237a50);color:#fff}.paid-check{width:78px;height:78px;border-radius:50%;margin:0 auto 17px;display:grid;place-items:center;background:#fff;color:#218052;font-size:36px;box-shadow:0 0 0 10px rgba(255,255,255,.13);animation:paid-pop .5s ease both}.paid-banner h1{font:800 30px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-1px;margin:0 0 8px}.paid-banner p{color:rgba(255,255,255,.75);font-size:13px;margin:0}.paid-body{padding:28px clamp(20px,5vw,42px) 35px}.paid-amount{font:800 34px 'Manrope',sans-serif;color:#176b4b;letter-spacing:-1px}.paid-reference{font-size:11px;color:#87938a}.paid-line{display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid #edf1ed;font-size:12px}.paid-line:last-child{border-bottom:0}.paid-line span{color:#7e8a80}.paid-line strong{color:#2d3b31;text-align:right}.paid-item{display:flex;justify-content:space-between;gap:15px;padding:9px 0;font-size:12px}.paid-actions{display:flex;gap:10px;flex-wrap:wrap}.paid-actions .btn{border-radius:11px;padding:11px 17px;font-size:12px;font-weight:800}@keyframes paid-pop{0%{transform:scale(.65);opacity:0}75%{transform:scale(1.08)}100%{transform:scale(1);opacity:1}}
</style>
<section class="paid-page">
    <div class="container px-3">
        <div class="paid-card">
            <div class="paid-banner">
                <div class="paid-check"><i class="bi bi-check-lg"></i></div>
                <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:1.5px;color:#c8e9d2">ABA PayWay · Payment verified</div>
                <h1>Payment successful!</h1>
                <p>Your payment has been verified by ABA. Your order is awaiting the shop's confirmation.</p>
            </div>
            <div class="paid-body">
                <div class="text-center pb-4 border-bottom">
                    <div class="small text-muted mb-1">You paid</div>
                    <div class="paid-amount">{{ $symbol }}{{ $amount }} <span class="fs-6">{{ $currency }}</span></div>
                    <div class="paid-reference mt-2">Order #{{ $order->order_id }} <span class="mx-1">·</span> {{ $payment->tran_id }}</div>
                </div>
                <div class="py-3">
                    <div class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing:1px">Order summary</div>
                    @php($itemSubtotal = (float)$order->orderItems->sum(fn($item) => (float)$item->price * (int)$item->quantity))
                    @foreach($order->orderItems as $item)
                        <div class="paid-item"><span>{{ $item->product->product_name ?? 'Product' }} × {{ $item->quantity }}</span><strong>{{ $symbol }}{{ number_format((float)$item->price * (int)$item->quantity, $currency === 'KHR' ? 0 : 2) }}</strong></div>
                    @endforeach
                    <div class="paid-line"><span>Items subtotal</span><strong>{{ $symbol }}{{ number_format($itemSubtotal, $currency === 'KHR' ? 0 : 2) }}</strong></div>
                    @if($order->delivery)<div class="paid-line"><span>Delivery fee</span><strong>{{ $symbol }}{{ number_format((float)$order->delivery->cost, $currency === 'KHR' ? 0 : 2) }}</strong></div>@endif
                    <div class="paid-line"><span>Order total</span><strong>{{ $symbol }}{{ number_format((float)$order->final_price, $currency === 'KHR' ? 0 : 2) }}</strong></div>
                    <div class="paid-line"><span>Fulfilment</span><strong>{{ strtolower((string)$order->orderType?->order_name)==='takeaway' ? 'Store pickup' : ($order->orderType->order_name ?? 'Store order') }}</strong></div>
                    @if($order->delivery)<div class="paid-line"><span>Delivery address</span><strong>{{ $order->delivery->address }}</strong></div>@endif
                    <div class="paid-line"><span>Payment method</span><strong>{{ $order->paymentMethod->payment_name ?? 'ABA PayWay KHQR' }}</strong></div>
                    <div class="paid-line"><span>Payment reference</span><strong>{{ $payment->apv ?: $payment->bank_ref ?: $payment->tran_id }}</strong></div>
                    <div class="paid-line"><span>Order status</span><strong class="text-success">{{ ucfirst(str_replace('_', ' ', $order->order_status ?? 'pending')) }}</strong></div>
                </div>
                <div class="alert alert-success border-0 rounded-3 small"><i class="bi bi-shield-check me-2"></i>Verified directly with ABA PayWay. @if(($order->order_status ?? 'pending') === 'pending')Your paid order is waiting for an administrator to confirm it. You can track each update from your order page.@elseYou can safely close this page or continue tracking your order.@endif</div>
                <div class="paid-actions mt-4">
                    <a href="{{ route('web.orders.show', $order->order_id) }}" class="btn btn-success flex-grow-1"><i class="bi bi-box-seam me-2"></i>Track my order</a>
                    <a href="{{ route('web.shop') }}" class="btn btn-outline-secondary"><i class="bi bi-cup-hot me-2"></i>Continue shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
