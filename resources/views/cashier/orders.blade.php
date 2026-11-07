@extends('layout.cashier')
@section('page_title', 'Orders')
@section('content')
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div><div class="eyebrow mb-2">Order processing</div><h1 class="h3 fw-bold mb-1">Order queue</h1><p class="text-muted mb-0">Your POS sales and incoming website orders. Unpaid orders can be cancelled to release reserved stock.</p></div>
    <a href="{{ route('pos.dashboard') }}" class="btn btn-green"><i class="bi bi-plus-lg me-1"></i>New sale</a>
</div>
<section class="panel p-3 p-lg-4">
    <form method="GET" action="{{ route('cashier.orders') }}" class="row g-2 align-items-end mb-3">
        <div class="col-12 col-md-4"><label class="form-label small fw-semibold">Search order</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Order number or payment status"></div>
        <div class="col-6 col-md-2"><label class="form-label small fw-semibold">Payment</label><select name="status" class="form-select"><option value="">All</option><option value="paid" @selected(request('status') === 'paid')>Paid</option><option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option></select></div>
        <div class="col-6 col-md-2"><label class="form-label small fw-semibold">From</label><input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control"></div>
        <div class="col-6 col-md-2"><label class="form-label small fw-semibold">To</label><input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control"></div>
        <div class="col-6 col-md-2 d-flex gap-2"><button class="btn btn-green flex-grow-1">Filter</button><a href="{{ route('cashier.orders') }}" class="btn btn-outline-secondary">Reset</a></div>
    </form>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Source / type</th><th>Payment</th><th>Total</th><th>Order status</th><th>Update</th><th>Date</th></tr></thead><tbody>
        @forelse($orders as $order)
            <tr>
                <td class="fw-bold">#{{ $order->order_id }}</td>
                <td>{{ $order->customer->name ?? 'Walk-in customer' }}</td>
                <td>{{ $order->orderItems->sum('quantity') }}</td>
                <td><span class="badge text-bg-light">{{ ucfirst($order->order_source ?? 'pos') }}</span><div class="small text-muted">{{ $order->orderType->order_name ?? '—' }}</div></td>
                <td><span class="badge {{ $order->is_paid ? 'text-bg-success' : 'text-bg-warning' }}">{{ $order->payment_status ?: ($order->is_paid ? 'Paid' : 'Unpaid') }}</span></td>
                <td class="fw-bold">${{ number_format((float)($order->final_price ?? $order->total_amount), 2) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $order->order_status ?? 'pending')) }}</td>
                <td>
                    @if(($order->order_status ?? 'pending') !== 'cancelled' && ($order->order_status ?? 'pending') !== 'completed')
                        <form method="POST" action="{{ route('cashier.orders.status', $order->order_id) }}" class="d-flex gap-1">
                            @csrf
                            <select name="order_status" class="form-select form-select-sm" required style="min-width:125px">
                                @if(!$order->is_paid)
                                    <option value="cancelled">Cancel / release stock</option>
                                @else
                                    @php $next = ['pending' => 'confirmed', 'confirmed' => 'preparing', 'preparing' => 'ready', 'ready' => 'completed'][$order->order_status ?? 'pending'] ?? null; @endphp
                                    @if($next)<option value="{{ $next }}">{{ ucfirst($next) }}</option>@endif
                                @endif
                            </select>
                            <button class="btn btn-sm btn-outline-success" type="submit">Save</button>
                        </form>
                    @else
                        <span class="small text-muted">—</span>
                    @endif
                </td>
                <td class="small">{{ optional($order->order_date)->format('d M Y, h:i A') }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted py-5">No orders match your search.</td></tr>
        @endforelse
    </tbody></table></div>
    <div class="mt-3">{{ $orders->links('pagination::simple-bootstrap-5') }}</div>
</section>
@endsection
