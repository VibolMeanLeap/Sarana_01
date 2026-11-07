@extends('layout.admin')

@section('page_title', 'Orders')
@section('topbar_title', 'Order management')

@push('styles')
<style>
    .orders-page { display:flex; flex-direction:column; gap:20px; }
    .orders-summary { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
    .orders-stat { background:#fff; border:1px solid #e8eee9; border-radius:15px; padding:17px 19px; box-shadow:0 5px 17px rgba(23,65,42,.035); }
    .orders-stat-label { color:#87938b; font-size:11px; font-weight:700; }
    .orders-stat-value { color:#24332b; font:800 25px 'Manrope',sans-serif; margin-top:7px; }
    .orders-stat.pending .orders-stat-value { color:#a8792f; }
    .orders-stat.completed .orders-stat-value { color:#238052; }
    .orders-stat.revenue .orders-stat-value { color:#5869ad; }
    .orders-panel { background:#fff; border:1px solid #e8eee9; border-radius:16px; box-shadow:0 5px 17px rgba(23,65,42,.035); overflow:hidden; }
    .orders-panel-head { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:18px 20px; border-bottom:1px solid #edf1ee; }
    .orders-panel-title { font:800 16px 'Manrope',sans-serif; margin:0; color:#24332b; }
    .orders-filter { display:grid; grid-template-columns:minmax(190px,1.5fr) repeat(4,minmax(130px,1fr)) auto auto; gap:9px; padding:16px 20px; border-bottom:1px solid #edf1ee; }
    .orders-filter .form-control,.orders-filter .form-select { min-width:0; height:40px; font-size:12px; }
    .orders-filter .btn { height:40px; white-space:nowrap; font-size:12px; font-weight:700; }
    .orders-table-wrap { overflow-x:auto; }
    .orders-table { width:100%; min-width:1380px; border-collapse:collapse; }
    .orders-table thead th { padding:12px 13px; text-align:left; color:#89948d; font-size:10px; letter-spacing:.7px; text-transform:uppercase; white-space:nowrap; border-bottom:1px solid #edf1ee; }
    .orders-table tbody td { padding:13px; color:#4f5c54; font-size:12px; vertical-align:middle; border-bottom:1px solid #f0f3f0; }
    .orders-table tbody tr:hover { background:#fbfcfb; }
    .order-id { font-weight:800; color:#26382e; }
    .status-pill { display:inline-flex; align-items:center; border-radius:30px; padding:5px 9px; font-size:10px; font-weight:800; white-space:nowrap; text-transform:capitalize; }
    .status-pill.pending { background:#fff5e5;color:#9b6a1b; }
    .status-pill.confirmed,.status-pill.preparing { background:#edf1ff;color:#5869ad; }
    .status-pill.ready { background:#e8f5ed;color:#28774e; }
    .status-pill.completed,.status-pill.paid { background:#e8f5ed;color:#20744d; }
    .status-pill.cancelled,.status-pill.failed,.status-pill.declined,.status-pill.expired { background:#fff0ef;color:#a74c45; }
    .status-pill.unpaid { background:#fff5e5;color:#9b6a1b; }
    .status-pill.review_required { background:#f3eafa;color:#8055a3; }
    .orders-actions { display:flex; flex-direction:column; align-items:stretch; gap:6px; min-width:150px; }
    .orders-actions form { margin:0; }
    .orders-actions .form-select { font-size:11px; min-width:145px; }
    .orders-actions .btn { font-size:11px; font-weight:700; }
    .items-link { color:#176b4b; font-weight:800; text-decoration:none; }
    .items-link:hover { color:#104d38; text-decoration:underline; }
    .orders-pagination { padding:14px 20px; display:flex; justify-content:flex-end; }
    .orders-pagination .pagination { margin:0; }
    @media(max-width:1100px) { .orders-filter { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:700px) { .orders-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } .orders-filter { grid-template-columns:repeat(2,minmax(0,1fr)); padding:13px; } .orders-filter .search-control { grid-column:1/-1; } }
</style>
@endpush

@section('content')
@php
    $orderStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
@endphp
<div class="orders-page">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-0">{{ $errors->first() }}</div>
    @endif

    <section class="orders-summary">
        <div class="orders-stat"><div class="orders-stat-label">Total orders</div><div class="orders-stat-value">{{ number_format($summary['total'] ?? 0) }}</div></div>
        <div class="orders-stat pending"><div class="orders-stat-label">Awaiting payment</div><div class="orders-stat-value">{{ number_format($summary['pending'] ?? 0) }}</div></div>
        <div class="orders-stat completed"><div class="orders-stat-label">Completed orders</div><div class="orders-stat-value">{{ number_format($summary['completed'] ?? 0) }}</div></div>
        <div class="orders-stat revenue"><div class="orders-stat-label">Verified revenue today</div><div class="orders-stat-value">${{ number_format($summary['revenue_today'] ?? 0, 2) }}</div></div>
    </section>

    <section class="orders-panel">
        <div class="orders-panel-head">
            <div><h1 class="orders-panel-title">Order list</h1><div class="small text-muted mt-1">Search, filter, and process POS and website orders.</div></div>
            <div class="d-flex align-items-center gap-3"><span class="small text-muted">{{ $orders->total() }} result(s)</span><a href="{{ route('admin.orders.create') }}" class="btn btn-success btn-sm fw-bold"><i class="bi bi-plus-lg me-1"></i> New order</a></div>
        </div>

        <form action="{{ route('admin.orders.index') }}" method="GET" class="orders-filter" id="ordersFilterForm">
            <input class="form-control search-control" type="text" name="search" placeholder="Order ID, customer, phone, cashier, transaction..." value="{{ request('search') }}">
            <select class="form-select" name="payment_status" aria-label="Payment status">
                <option value="">All payments</option>
                <option value="paid" @selected(request('payment_status', request('status')) === 'paid')>Paid</option>
                <option value="unpaid" @selected(request('payment_status', request('status')) === 'unpaid')>Unpaid</option>
            </select>
            <select class="form-select" name="order_status" aria-label="Order status">
                <option value="">All order statuses</option>
                @foreach($orderStatuses as $status)
                    <option value="{{ $status }}" @selected(request('order_status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select class="form-select" name="source" aria-label="Order source">
                <option value="">All sources</option>
                <option value="pos" @selected(request('source') === 'pos')>POS</option>
                <option value="website" @selected(request('source') === 'website')>Website</option>
            </select>
            <input class="form-control" type="date" name="start_date" aria-label="Start date" value="{{ request('start_date') }}">
            <input class="form-control" type="date" name="end_date" aria-label="End date" value="{{ request('end_date') }}">
            <button type="submit" class="btn btn-success">Filter</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
        </form>

        <div class="orders-table-wrap">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order</th><th>Customer</th><th>Phone</th><th>Cashier</th><th>Type</th><th>Source</th>
                        <th>Items</th><th>Subtotal</th><th>Discount</th><th>Final price</th><th>Payment method</th>
                        <th>Payment</th><th>Order status</th><th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $row)
                    @php
                        $orderStatus = strtolower((string) ($row->order_status ?: ($row->is_paid ? 'completed' : 'pending')));
                        $payment = $row->payments->sortByDesc('id')->first();
                        $paymentStatus = $row->is_paid ? 'paid' : strtolower((string) ($payment?->status ?: 'unpaid'));
                        $canDelete = ! $row->is_paid
                            && (int) $row->order_items_count === 0
                            && (int) $row->payments_count === 0
                            && (int) $row->delivery_count === 0
                            && (int) $row->review_count === 0
                            && (int) $row->cash_transactions_count === 0;
                        $nextStatus = ['pending' => 'confirmed', 'confirmed' => 'preparing', 'preparing' => 'ready', 'ready' => 'completed'][$orderStatus] ?? null;
                    @endphp
                    <tr>
                        <td><span class="order-id">#{{ $row->order_id }}</span></td>
                        <td>{{ $row->customer?->name ?: 'Walk-in' }}</td>
                        <td>{{ $row->customer?->phone ?: '—' }}</td>
                        <td>{{ $row->cashier?->name ?: 'Website' }}</td>
                        <td>{{ $row->orderType?->order_name ?: '—' }}</td>
                        <td><span class="status-pill {{ $row->order_source === 'website' ? 'confirmed' : 'ready' }}">{{ ucfirst($row->order_source ?: 'pos') }}</span></td>
                        <td><a class="items-link" href="{{ route('admin.older_items', ['order_id' => $row->order_id]) }}">{{ (int) $row->order_items_count }} · View</a></td>
                        <td>${{ number_format((float) $row->total_amount, 2) }}</td>
                        <td>{{ number_format((float) $row->discount, 2) }}%</td>
                        <td class="fw-bold">${{ number_format((float) $row->final_price, 2) }}</td>
                        <td>{{ $row->paymentMethod?->payment_name ?: ($payment?->provider === 'aba_payway' ? 'ABA KHQR' : '—') }}</td>
                        <td><span class="status-pill {{ $paymentStatus }}">{{ str_replace('_', ' ', ucfirst($paymentStatus)) }}</span></td>
                        <td><span class="status-pill {{ in_array($orderStatus, $orderStatuses, true) ? $orderStatus : 'pending' }}">{{ str_replace('_', ' ', ucfirst($orderStatus)) }}</span></td>
                        <td>{{ $row->order_date?->format('d M Y, h:i A') ?: '—' }}</td>
                        <td>
                            <div class="orders-actions">
                                @if($row->order_status !== 'cancelled' && $row->order_status !== 'completed')
                                    @if(!$row->is_paid)
                                        <form action="{{ route('admin.orders.status', $row->order_id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="order_status" value="cancelled">
                                            <button class="btn btn-sm btn-outline-danger w-100" type="submit" onclick="return confirm('Cancel this unpaid order and return its reserved stock?')">Cancel unpaid order</button>
                                        </form>
                                    @elseif($nextStatus)
                                        <form action="{{ route('admin.orders.status', $row->order_id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="order_status" value="{{ $nextStatus }}">
                                            <button class="btn btn-sm btn-outline-success w-100" type="submit">Mark {{ $nextStatus }}</button>
                                        </form>
                                    @endif
                                @endif
                                @if($canDelete)
                                    <form action="{{ route('admin.orders.destroy', $row->order_id) }}" method="POST" onsubmit="return confirm('Permanently delete this empty order?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Delete empty order</button>
                                    </form>
                                @else
                                    <span class="small text-muted">History protected</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="15" class="text-center text-muted py-5">No orders match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="orders-pagination">{{ $orders->links('pagination::bootstrap-5') }}</div>
    </section>
</div>
@endsection
