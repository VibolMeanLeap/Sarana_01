@extends('layout.web')
@section('web_title', 'Order #'.$order->order_id)
@section('content')
<section class="py-5">
    <div class="container" style="max-width:900px">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><span class="text-success fw-bold small text-uppercase">Order tracking</span><h1 class="fw-bold mb-1">Order #{{ $order->order_id }}</h1><p class="text-muted mb-0">Placed {{ optional($order->order_date)->format('d M Y, h:i A') }}</p></div><a href="{{ route('web.shop') }}" class="btn btn-outline-secondary rounded-pill">Continue shopping</a></div>
        @include('admin.message.message')
        <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><div class="row g-3"><div class="col-sm-4"><small class="text-muted d-block">Order status</small><strong>{{ ucfirst(str_replace('_', ' ', $order->order_status ?? 'pending')) }}</strong></div><div class="col-sm-4"><small class="text-muted d-block">Payment status</small><strong class="{{ $order->is_paid ? 'text-success' : 'text-warning' }}">{{ $order->payment_status ?: 'Unpaid' }}</strong></div><div class="col-sm-4"><small class="text-muted d-block">Order type</small><strong>{{ $order->orderType->order_name ?? 'Store order' }}</strong></div></div></div></div>
        <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Items</h2>
            @foreach($order->orderItems as $item)<div class="d-flex justify-content-between gap-3 py-2 border-bottom"><span>{{ $item->product->product_name ?? 'Product' }} × {{ $item->quantity }}</span><strong>${{ number_format((float)$item->price * (int)$item->quantity, 2) }}</strong></div>@endforeach
            <div class="d-flex justify-content-between pt-3 fs-5"><strong>Total</strong><strong class="text-success">${{ number_format((float)$order->final_price, 2) }}</strong></div>
            @if($order->delivery)<div class="mt-3 small text-muted"><strong>Delivery address:</strong> {{ $order->delivery->address }}</div>@endif
            @if($order->customer_note)<div class="mt-2 small text-muted"><strong>Note:</strong> {{ $order->customer_note }}</div>@endif
            <div class="d-flex flex-wrap gap-2 mt-4">
                @if($payment && $payment->status === 'pending')<a href="{{ route('web.payments.show', $payment->id) }}" class="btn btn-success rounded-pill">Continue payment</a>
                @elseif(!$order->is_paid && $order->order_status !== 'cancelled')<form method="POST" action="{{ route('web.orders.payway.retry', $order->order_id) }}">@csrf<button class="btn btn-success rounded-pill" type="submit">Generate a new KHQR</button></form>@endif
                <a href="{{ route('web.account') }}" class="btn btn-outline-secondary rounded-pill">My account</a>
            </div>
        </div></div>
    </div>
</section>
@endsection
