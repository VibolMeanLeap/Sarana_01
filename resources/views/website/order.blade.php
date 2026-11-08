@extends('layout.web')
@section('web_title', 'Order #'.$order->order_id)
@section('content')
@php
    $state = strtolower((string)($order->order_status ?: 'pending'));
    $isPickup = strtolower((string)$order->orderType?->order_name) === 'takeaway';
    $deliveryFee = (float)($order->delivery?->cost ?? 0);
    $itemSubtotal = (float)$order->orderItems->sum(fn($item) => (float)$item->price * (int)$item->quantity);
    $isPendingPayment = !$order->is_paid && $state === 'pending';
    $latestPayment = $payment;
    $paymentCanContinue = $latestPayment && $latestPayment->status === 'pending' && (!$latestPayment->expires_at || $latestPayment->expires_at->isFuture());
@endphp
<style>
    .order-track-page{background:#f7faf7;padding:42px 0 70px;min-height:70vh}.order-track-card{background:#fff;border:1px solid #e6eee7;border-radius:22px;box-shadow:0 12px 34px rgba(24,56,38,.045)}.order-track-title{font-weight:850;letter-spacing:-.045em;color:#183326}.order-step{position:relative;padding-left:27px;padding-bottom:22px;border-left:2px solid #dfe9e1;margin-left:10px}.order-step:last-child{border-left-color:transparent;padding-bottom:0}.order-step:before{content:'';position:absolute;left:-8px;top:0;width:14px;height:14px;border-radius:50%;background:#d0ddd3;border:3px solid #fff;box-shadow:0 0 0 1px #cbd9ce}.order-step.done:before{background:#198754;box-shadow:0 0 0 1px #198754}.order-step strong{display:block;font-size:.9rem;color:#203a2b}.order-step small{color:#7b897e}.order-total{font-size:1.7rem;font-weight:900;color:#167549}.order-item-row{padding:13px 0;border-bottom:1px solid #edf2ee}.order-item-row:last-child{border-bottom:0}
</style>
<section class="order-track-page">
    <div class="container" style="max-width:1000px">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.14em">Order tracking</div><h1 class="display-6 order-track-title mb-1">Order #{{ $order->order_id }}</h1><p class="text-muted mb-0">Placed {{ optional($order->order_date)->format('d M Y, h:i A') }}</p></div><a href="{{ route('web.shop') }}" class="btn btn-outline-success rounded-pill px-4">Continue shopping</a></div>
        @include('admin.message.message')
        @if($errors->any())<div class="alert alert-danger rounded-4"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="order-track-card p-3 p-md-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Order progress</h2><span class="badge rounded-pill {{ $order->is_paid ? 'text-bg-success' : ($state==='cancelled' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst(str_replace('_',' ',$state)) }}</span></div>
                    @if($state === 'cancelled')
                        <div class="alert alert-secondary mb-0"><i class="bi bi-x-circle me-2"></i>This order has been cancelled. Any reserved stock has been returned to the shop.</div>
                    @else
                        @php($steps = ['confirmed' => 'Order confirmed', 'preparing' => 'Being prepared', 'ready' => $isPickup ? 'Ready for pickup' : 'Ready for dispatch', 'completed' => $isPickup ? 'Collected' : 'Delivered'])
                        @if(!$order->is_paid)<div class="alert alert-warning small"><i class="bi bi-hourglass-split me-2"></i>Your order is waiting for verified payment. We will begin preparing it after payment is confirmed.</div>@endif
                        <div class="mt-3">
                            @foreach($steps as $stepKey => $stepLabel)
                                @php($stepOrder = ['pending','confirmed','preparing','ready','completed'])
                                @php($currentIndex = array_search($state, $stepOrder, true))
                                @php($thisIndex = array_search($stepKey, $stepOrder, true))
                                <div class="order-step {{ $order->is_paid && $currentIndex !== false && $thisIndex <= $currentIndex ? 'done' : '' }}"><strong>{{ $stepLabel }}</strong><small>{{ $stepKey === 'confirmed' ? 'Payment has been accepted by the shop.' : ($stepKey === 'preparing' ? 'Our team is working on your order.' : ($stepKey === 'ready' ? 'We will update this when it is ready.' : 'Thank you for ordering with us.')) }}</small></div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="order-track-card p-3 p-md-4">
                    <h2 class="h5 fw-bold mb-3">Items in your order</h2>
                    @foreach($order->orderItems as $item)
                        <div class="order-item-row d-flex justify-content-between gap-3"><div><strong>{{ $item->product->product_name ?? 'Product' }}</strong><small class="d-block text-muted">{{ (int)$item->quantity }} × ${{ number_format((float)$item->price, 2) }}</small></div><strong>${{ number_format((float)$item->price * (int)$item->quantity, 2) }}</strong></div>
                    @endforeach
                    <div class="d-flex justify-content-between pt-3"><span class="text-muted">Items subtotal</span><strong>${{ number_format($itemSubtotal, 2) }}</strong></div>
                    @if($order->delivery)<div class="d-flex justify-content-between pt-2"><span class="text-muted">Delivery fee</span><strong>${{ number_format($deliveryFee, 2) }}</strong></div>@endif
                    <div class="d-flex justify-content-between align-items-end border-top mt-3 pt-3"><span class="fw-bold">Order total</span><span class="order-total">${{ number_format((float)$order->final_price, 2) }}</span></div>
                </div>
            </div>
            <div class="col-lg-5">
                <aside class="order-track-card p-3 p-md-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Payment & collection</h2>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Payment</span><strong class="{{ $order->is_paid ? 'text-success' : 'text-warning' }}">{{ $order->is_paid ? 'Paid' : ($order->payment_status ?: 'Unpaid') }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Method</span><strong>{{ $order->paymentMethod->payment_name ?? 'ABA KHQR' }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Fulfilment</span><strong>{{ $isPickup ? 'Store pickup' : ($order->orderType->order_name ?? '—') }}</strong></div>
                    @if($order->delivery)<div class="mt-2 p-3 rounded-3 bg-light small"><strong class="d-block mb-1">Delivery address</strong>{{ $order->delivery->address }}<div class="text-muted mt-2">Delivery status: {{ ucfirst(str_replace('_',' ',$order->delivery->status ?? 'pending')) }}</div></div>@endif
                    @if($order->customer_note)<div class="mt-3 small text-muted"><strong>Order note:</strong> {{ $order->customer_note }}</div>@endif
                    @if($payment && $payment->tran_id)<div class="small text-muted mt-3">Payment reference: <code>{{ $payment->tran_id }}</code></div>@endif
                    <div class="d-grid gap-2 mt-4">
                        @if($paymentCanContinue)
                            <a href="{{ route('web.payments.show', $payment->id) }}" class="btn btn-success rounded-pill py-2"><i class="bi bi-qr-code me-2"></i>Continue payment</a>
                        @elseif(!$order->is_paid && $state !== 'cancelled')
                            <form method="POST" action="{{ route('web.orders.payway.retry', $order->order_id) }}">@csrf<button class="btn btn-success rounded-pill py-2 w-100" type="submit"><i class="bi bi-arrow-repeat me-2"></i>Generate a new KHQR</button></form>
                        @endif
                        @if($isPendingPayment)
                            <form method="POST" action="{{ route('web.orders.cancel', $order->order_id) }}" onsubmit="return confirm('Cancel this unpaid order and return its reserved stock?')">@csrf<button type="submit" class="btn btn-outline-danger rounded-pill py-2"><i class="bi bi-x-circle me-2"></i>Cancel unpaid order</button></form>
                        @endif
                        <a href="{{ route('web.account') }}" class="btn btn-outline-secondary rounded-pill py-2">My account & order history</a>
                    </div>
                </aside>
                <div class="small text-muted px-2"><i class="bi bi-info-circle text-success me-1"></i>Order updates appear here automatically when the shop team advances your order.</div>
            </div>
        </div>
    </div>
</section>
<script>
(() => {
    const statusUrl = @json(route('web.orders.status', $order->order_id));
    const currentStatus = @json($state);
    const currentPaid = @json((bool)$order->is_paid);
    let checking = false;
    window.setInterval(async () => {
        if (checking || document.visibilityState !== 'visible') return;
        checking = true;
        try {
            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            if (data.order_status !== currentStatus || Boolean(data.paid) !== currentPaid) window.location.reload();
        } catch (error) {
            // Keep order details usable when the connection is temporarily unavailable.
        } finally {
            checking = false;
        }
    }, 15000);
})();
</script>
@endsection
