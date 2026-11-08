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
    .orders-filter { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:9px; padding:16px 20px; border-bottom:1px solid #edf1ee; }
    .orders-filter .form-control,.orders-filter .form-select { min-width:0; height:40px; font-size:12px; }
    .orders-filter .btn { height:40px; white-space:nowrap; font-size:12px; font-weight:700; }
    .orders-table-wrap { overflow-x:auto; }
    .order-summary-row { cursor:pointer; }
    .order-summary-row:focus { outline:2px solid #8cb99d; outline-offset:-2px; }
    .order-expand-hint { display:block; margin-top:3px; color:#8a978e; font-size:10px; font-weight:600; }
    .order-expand-hint i { transition:transform .18s ease; }
    .order-summary-row[aria-expanded="true"] .order-expand-hint i { transform:rotate(180deg); }
    .order-items-detail > td { padding:0 !important; background:#f7faf8 !important; }
    .order-items-panel { margin:10px 14px 14px; padding:14px 16px; border:1px solid #e1ebe4; border-radius:12px; background:#fff; }
    .order-line-table thead th { color:#829087; font-size:10px; letter-spacing:.5px; text-transform:uppercase; border-bottom:1px solid #edf1ee; }
    .order-line-table tbody td { font-size:11px; padding:9px 8px; }

    .orders-table { width:100%; min-width:1160px; border-collapse:collapse; }
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
    .order-sub-link { display:inline-flex; align-items:center; gap:4px; margin-top:3px; color:#7b887f; font-size:10px; font-weight:700; text-decoration:none; }
    .order-sub-link:hover { color:#176b4b; }
    .status-pill.compact { min-width:42px; justify-content:center; padding:4px 7px; font-size:9px; letter-spacing:.25px; }
    .orders-table tfoot td { padding:14px 13px; background:#f5f8f5; border-top:2px solid #dce8df; color:#26382e; font-size:12px; font-weight:800; }
    .orders-table tfoot .grand-total { color:#176b4b; font-size:15px; white-space:nowrap; }
    .orders-pagination { padding:13px 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
    .orders-pagination .pagination { margin:0; }
    .orders-pagination-info { color:#7c8980; font-size:11px; }
    .orders-filter .wide-search { grid-column:span 2; }
    .orders-filter .per-page-select { min-width:90px; }

    @media(max-width:1100px) { .orders-filter { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:700px) { .orders-summary { grid-template-columns:repeat(2,minmax(0,1fr)); } .orders-filter { grid-template-columns:repeat(2,minmax(0,1fr)); padding:13px; } .orders-filter .wide-search { grid-column:1/-1; } }
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
            <input class="form-control search-control wide-search" type="text" name="search" placeholder="Order ID, customer, phone, cashier, transaction..." value="{{ request('search') }}">
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
            <select class="form-select" name="sort_by" aria-label="Sort orders by"><option value="order_date" @selected(request('sort_by', 'order_date') === 'order_date')>Order date</option><option value="order_id" @selected(request('sort_by') === 'order_id')>Order number</option><option value="final_price" @selected(request('sort_by') === 'final_price')>Final total</option></select>
            <select class="form-select" name="sort_dir" aria-label="Sort direction"><option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('sort_dir') === 'asc')>Ascending</option></select>
            <select class="form-select per-page-select" name="per_page" aria-label="Rows per page">
                <option value="10" @selected(($perPage ?? 10) === 10)>10 / page</option>
                <option value="25" @selected(($perPage ?? 10) === 25)>25 / page</option>
                <option value="50" @selected(($perPage ?? 10) === 50)>50 / page</option>
                <option value="100" @selected(($perPage ?? 10) === 100)>100 / page</option>
            </select>
            <button type="submit" class="btn btn-success">Filter</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
        </form>

        <div class="px-3 px-md-4 py-3 d-flex justify-content-end">
            @include('admin.partials.export-actions', ['exportType' => 'orders'])
        </div>
        <div class="orders-table-wrap">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order</th><th>Customer</th><th>Phone</th><th>Cashier</th><th>Type</th><th>Source</th>
                        <th>Subtotal</th><th>Discount</th><th>Final</th><th>Method</th>
                        <th>Payment</th><th>Status</th><th>Date</th><th>Actions</th>
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
                    <tr class="order-summary-row" data-order-row="{{ $row->order_id }}" tabindex="0" aria-expanded="false" title="Click anywhere on this row to view sold items">
                        <td>
                            <span class="order-id">#{{ $row->order_id }}</span>
                            <span class="order-expand-hint"><i class="bi bi-chevron-down"></i> {{ (int) $row->order_items_count }} items</span>
                        </td>
                        <td>{{ $row->customer?->name ?: 'Walk-in' }}</td>
                        <td>{{ $row->customer?->phone ?: '—' }}</td>
                        <td>{{ $row->cashier?->name ?: 'Website' }}</td>
                        <td>{{ $row->orderType?->order_name ?: '—' }}</td>
                        <td><span class="status-pill {{ $row->order_source === 'website' ? 'confirmed' : 'ready' }}">{{ ucfirst($row->order_source ?: 'pos') }}</span></td>
                        <td>${{ number_format((float) $row->total_amount, 2) }}</td>
                        <td>{{ number_format((float) $row->discount, 2) }}%</td>
                        <td class="fw-bold">${{ number_format((float) $row->final_price, 2) }}</td>
                        <td>{{ $row->paymentMethod?->payment_name ?: ($payment?->provider === 'aba_payway' ? 'ABA KHQR' : '—') }}</td>
                        @php
                            $paymentShort = ['paid' => 'PAID', 'unpaid' => 'UNPD', 'pending' => 'PEND', 'failed' => 'FAIL', 'declined' => 'DECL', 'expired' => 'EXP', 'review_required' => 'REVIEW'][$paymentStatus] ?? strtoupper(substr($paymentStatus, 0, 5));
                            $orderShort = ['pending' => 'PEND', 'confirmed' => 'CONF', 'preparing' => 'PREP', 'ready' => 'READY', 'completed' => 'DONE', 'cancelled' => 'CXL'][$orderStatus] ?? strtoupper(substr($orderStatus, 0, 5));
                        @endphp
                        <td><span class="status-pill compact {{ $paymentStatus }}" title="Payment: {{ str_replace('_', ' ', ucfirst($paymentStatus)) }}" aria-label="Payment: {{ str_replace('_', ' ', ucfirst($paymentStatus)) }}">{{ $paymentShort }}</span></td>
                        <td><span class="status-pill compact {{ in_array($orderStatus, $orderStatuses, true) ? $orderStatus : 'pending' }}" title="Order status: {{ str_replace('_', ' ', ucfirst($orderStatus)) }}" aria-label="Order status: {{ str_replace('_', ' ', ucfirst($orderStatus)) }}">{{ $orderShort }}</span></td>
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
                    <tr class="order-items-detail d-none" id="order-items-{{ $row->order_id }}">
                        <td colspan="14">
                            <div class="order-items-panel">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                    <div>
                                        <strong>Items sold in order #{{ $row->order_id }}</strong>
                                        <span class="text-muted small ms-2">{{ $row->orderItems->count() }} line(s)</span>
                                    </div>
                                    <strong class="text-success">Order total: ${{ number_format((float) ($row->final_price ?? $row->total_amount), 2) }}</strong>
                                </div>
                                @if($row->orderItems->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0 order-line-table">
                                            <thead><tr><th>Product</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Line total</th></tr></thead>
                                            <tbody>
                                            @foreach($row->orderItems as $line)
                                                <tr>
                                                    <td>{{ $line->product?->product_name ?? ('Deleted product #'.($line->product_id ?? '')) }}</td>
                                                    <td><span class="badge {{ $line->product && $line->product->isStockTracked() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $line->product ? ($line->product->isStockTracked() ? 'Stock' : 'Non-stock') : 'Archived' }}</span></td>
                                                    <td class="text-end">{{ number_format((int) $line->quantity) }}</td>
                                                    <td class="text-end">${{ number_format((float) $line->price, 2) }}</td>
                                                    <td class="text-end fw-semibold">${{ number_format((float) $line->price * (int) $line->quantity, 2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted small py-2">No line items were recorded for this order.</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="text-center text-muted py-5">No orders match these filters.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="8">FILTERED FINAL TOTAL <span class="text-muted fw-normal">({{ number_format($filteredOrderCount ?? 0) }} matching orders · all pages)</span></td>
                        <td class="grand-total">${{ number_format((float) ($filteredFinalTotal ?? 0), 2) }}</td>
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="orders-pagination">
            <div class="orders-pagination-info">
                Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ number_format($orders->total()) }} orders
            </div>
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </section>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleOrderDetails = (row) => {
        const orderId = row.dataset.orderRow;
        const detail = document.getElementById(`order-items-${orderId}`);
        if (!detail) return;
        const opening = detail.classList.contains('d-none');
        detail.classList.toggle('d-none', !opening);
        row.setAttribute('aria-expanded', opening ? 'true' : 'false');
    };
    document.querySelectorAll('[data-order-row]').forEach(row => {
        row.addEventListener('click', event => {
            if (event.target.closest('a,button,form,input,select,textarea,label')) return;
            toggleOrderDetails(row);
        });
        row.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                if (event.target !== row && event.target.closest('a,button,form,input,select,textarea,label')) return;
                event.preventDefault();
                toggleOrderDetails(row);
            }
        });
    });
});
</script>
@endsection

@endsection
