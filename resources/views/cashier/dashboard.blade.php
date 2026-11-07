@extends('layout.cashier')
@section('page_title', 'Cashier overview')
@section('content')
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div><div class="eyebrow mb-2">Daily operations</div><h1 class="h3 fw-bold mb-1">Welcome, {{ explode(' ', auth()->user()->name)[0] }}.</h1><p class="text-muted mb-0">Your sales activity and recent transactions.</p></div>
    <a href="{{ route('pos.dashboard') }}" class="btn btn-green"><i class="bi bi-plus-lg me-1"></i>Start a sale</a>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="panel stat-card h-100"><div class="eyebrow mb-2">Today's orders</div><div class="stat-value">{{ number_format($summary['today_orders']) }}</div><div class="stat-label">Orders recorded by you today</div></div></div>
    <div class="col-6 col-xl-3"><div class="panel stat-card h-100"><div class="eyebrow mb-2">Paid orders</div><div class="stat-value">{{ number_format($summary['paid_orders']) }}</div><div class="stat-label">Completed transactions</div></div></div>
    <div class="col-6 col-xl-3"><div class="panel stat-card h-100"><div class="eyebrow mb-2">Awaiting payment</div><div class="stat-value">{{ number_format($summary['pending_orders']) }}</div><div class="stat-label">Unpaid orders</div></div></div>
    <div class="col-6 col-xl-3"><div class="panel stat-card h-100"><div class="eyebrow mb-2">Your revenue today</div><div class="stat-value">${{ number_format($summary['today_revenue'], 2) }}</div><div class="stat-label">Paid sales only</div></div></div>
</div>
<section class="panel p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><div><h2 class="h5 fw-bold mb-1">Recent orders</h2><p class="small text-muted mb-0">Only transactions assigned to your cashier account.</p></div><a href="{{ route('cashier.orders') }}" class="btn btn-outline-secondary btn-sm rounded-3">View all</a></div>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Order</th><th>Customer</th><th>Type</th><th>Payment</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>
        @forelse($recentOrders as $order)
            <tr><td class="fw-bold">#{{ $order->order_id }}</td><td>{{ $order->customer->name ?? 'Walk-in customer' }}</td><td>{{ $order->orderType->order_name ?? '—' }}</td><td>{{ $order->paymentMethod->payment_name ?? '—' }}</td><td class="fw-bold">${{ number_format((float)($order->final_price ?? $order->total_amount), 2) }}</td><td><span class="badge rounded-pill {{ $order->is_paid ? 'badge-paid' : 'badge-pending' }}">{{ $order->is_paid ? 'Paid' : 'Unpaid' }}</span></td><td>{{ optional($order->order_date)->format('d M, h:i A') }}</td></tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No transactions yet. Start your first sale from the POS.</td></tr>
        @endforelse
    </tbody></table></div>
</section>
@endsection
