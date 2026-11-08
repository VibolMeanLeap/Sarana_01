@extends('layout.web')
@section('web_title', 'My account & orders')
@section('content')
<style>
    .account-page{background:#f7faf7;min-height:70vh;padding:42px 0 70px}.account-title{font-weight:850;letter-spacing:-.05em;color:#183326}.account-card{background:#fff;border:1px solid #e6eee7;border-radius:22px;box-shadow:0 12px 34px rgba(24,56,38,.045)}.account-stat{font-size:1.55rem;font-weight:900;color:#167549}.account-order-row:hover{background:#fbfdfb}.account-order-link{font-weight:800;color:#176b45;text-decoration:none}.account-order-link:hover{text-decoration:underline}.account-pill{font-size:.72rem;font-weight:800;padding:7px 10px;border-radius:99px}
</style>
<section class="account-page">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.14em">Your Coffee Corner profile</div><h1 class="display-6 account-title mb-1">Welcome, {{ $customer?->name ?? auth()->user()->name }}.</h1><p class="text-muted mb-0">Your orders, payment updates and collection details in one place.</p></div>
            <a href="{{ route('web.shop') }}" class="btn btn-success rounded-pill px-4 py-2"><i class="bi bi-cup-hot me-2"></i>Browse menu</a>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="account-card p-4 h-100"><div class="small fw-bold text-muted text-uppercase mb-2">Customer name</div><div class="h5 fw-bold mb-0">{{ $customer?->name ?? auth()->user()->name }}</div></div></div>
            <div class="col-md-4"><div class="account-card p-4 h-100"><div class="small fw-bold text-muted text-uppercase mb-2">Phone number</div><div class="h5 fw-bold mb-0">{{ $customer?->phone ?? auth()->user()->phone ?? 'Not set' }}</div></div></div>
            <div class="col-md-4"><div class="account-card p-4 h-100"><div class="small fw-bold text-muted text-uppercase mb-2">Orders placed</div><div class="account-stat">{{ $orders->total() }}</div></div></div>
        </div>
        <div class="account-card p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3"><div><h2 class="h4 fw-bold mb-1">My order history</h2><p class="small text-muted mb-0">Open an order to view items, payment status and progress.</p></div><span class="badge text-bg-light rounded-pill">{{ $orders->total() }} total</span></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Order</th><th>Fulfilment</th><th>Order status</th><th>Payment</th><th>Total</th><th>Date</th><th></th></tr></thead><tbody>
                @forelse($orders as $order)
                    @php($orderState = strtolower((string)($order->order_status ?: 'pending')))
                    <tr class="account-order-row">
                        <td><a class="account-order-link" href="{{ route('web.orders.show', $order->order_id) }}">#{{ $order->order_id }}</a></td>
                        <td>{{ strtolower((string)$order->orderType?->order_name)==='takeaway' ? 'Pickup' : ($order->orderType->order_name ?? '—') }}</td>
                        <td><span class="badge account-pill {{ $orderState==='completed' ? 'text-bg-success' : ($orderState==='cancelled' ? 'text-bg-danger' : 'text-bg-warning') }}">{{ ucfirst(str_replace('_',' ',$orderState)) }}</span></td>
                        <td><span class="badge account-pill {{ $order->is_paid ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $order->is_paid ? 'Paid' : 'Unpaid' }}</span></td>
                        <td class="fw-bold">${{ number_format((float)$order->final_price, 2) }}</td>
                        <td class="text-muted small">{{ optional($order->order_date)->format('d M Y, h:i A') }}</td>
                        <td><a href="{{ route('web.orders.show', $order->order_id) }}" class="btn btn-sm btn-outline-success rounded-pill">Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5"><i class="bi bi-bag-heart display-6 text-success"></i><h3 class="h6 fw-bold mt-3">No orders yet</h3><p class="small text-muted mb-3">Your next coffee favourite is waiting.</p><a href="{{ route('web.shop') }}" class="btn btn-success rounded-pill px-4">Start shopping</a></td></tr>
                @endforelse
            </tbody></table></div>
            @if($orders->hasPages())<div class="pt-3">{{ $orders->links() }}</div>@endif
        </div>
    </div>
</section>
@endsection
