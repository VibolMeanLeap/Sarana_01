@extends('layout.web')
@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div><span class="badge rounded-pill px-3 py-2 mb-2" style="background:#e8f5ed;color:#176b4b">Customer account</span><h1 class="h2 fw-bold mb-1">Hello, {{ $customer?->name ?? auth()->user()->name }}.</h1><p class="text-muted mb-0">Your profile and order history at {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}.</p></div>
            <a href="{{ route('web.shop') }}" class="btn btn-success rounded-3"><i class="bi bi-cup-hot me-1"></i>Browse menu</a>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><div class="bg-white border rounded-4 p-4 h-100"><div class="text-uppercase small fw-bold text-muted mb-2">Name</div><div class="fs-5 fw-bold">{{ $customer?->name ?? auth()->user()->name }}</div></div></div>
            <div class="col-md-6"><div class="bg-white border rounded-4 p-4 h-100"><div class="text-uppercase small fw-bold text-muted mb-2">Phone</div><div class="fs-5 fw-bold">{{ $customer?->phone ?? auth()->user()->phone }}</div></div></div>
        </div>
        <div class="bg-white border rounded-4 p-3 p-lg-4">
            <div class="d-flex align-items-center justify-content-between mb-3"><div><h2 class="h5 fw-bold mb-1">My order history</h2><p class="text-muted small mb-0">Orders linked to your customer profile.</p></div><span class="badge text-bg-light">{{ $customer?->orders?->count() ?? 0 }} orders</span></div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Order</th><th>Order type</th><th>Items</th><th>Total</th><th>Payment status</th><th>Date</th></tr></thead><tbody>
                @forelse($customer?->orders ?? [] as $order)
                    <tr><td class="fw-bold">#{{ $order->order_id }}</td><td>{{ $order->orderType->order_name ?? '—' }}</td><td>{{ $order->orderItems->sum('quantity') }}</td><td class="fw-bold">${{ number_format((float)($order->final_price ?? $order->total_amount), 2) }}</td><td><span class="badge rounded-pill {{ $order->is_paid ? 'text-bg-success' : 'text-bg-warning' }}">{{ $order->is_paid ? 'Paid' : 'Unpaid' }}</span></td><td>{{ optional($order->order_date)->format('d M Y') }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No orders are linked to this account yet.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</section>
@endsection
