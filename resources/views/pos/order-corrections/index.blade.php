@extends('layout.pos')

@section('content')
<style>
    .correction-wrap{max-width:1500px;margin:0 auto}
    .correction-hero{background:linear-gradient(125deg,#14583d,#237a55);color:#fff;border-radius:20px;padding:24px;box-shadow:0 12px 30px rgba(20,88,61,.15)}
    .correction-soft{background:#edf5ef;border:1px solid #d7e7db;border-radius:16px}
    .correction-card{background:#fff;border:1px solid #e0eae2;border-radius:15px;box-shadow:0 4px 15px rgba(22,67,43,.04)}
    .correction-table thead th{background:#edf5ef;color:#315f45;font-size:11px;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;border-bottom:1px solid #d8e7dc}
    .correction-table td{vertical-align:middle}
    .correction-pill{display:inline-flex;align-items:center;border-radius:100px;padding:5px 9px;font-size:11px;font-weight:800}
    .correction-pill.good{background:#e5f3e9;color:#176b4b}
    .correction-pill.pending{background:#fff4dc;color:#956311}
    .correction-btn{background:#176b4b;border-color:#176b4b;color:#fff;border-radius:10px;font-weight:700}
    .correction-btn:hover{background:#105239;border-color:#105239;color:#fff}
</style>

<div class="correction-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="mini-label mb-2">POS · Administrator control</div>
            <h1 class="h3 fw-bold mb-1">Completed order corrections</h1>
            <p class="text-muted mb-0">Find an eligible cash sale and correct its products, quantities, customer or discount with a full audit trail.</p>
        </div>
        <a href="{{ route('pos.dashboard') }}" class="btn btn-pos-outline"><i class="bi bi-arrow-left me-1"></i>Back to POS</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3">{{ session('error') }}</div>
    @endif

    <div class="correction-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-white-50 fw-bold text-uppercase mb-2">Protected sales workflow</div>
                <h2 class="h4 fw-bold mb-2">Correct mistakes without deleting the original sale</h2>
                <p class="mb-0 text-white-50">Every correction records the original values, corrected values, administrator, reason and any cash difference.</p>
            </div>
            <div class="text-end">
                <div class="display-6 fw-bold">{{ $orders->total() }}</div>
                <div class="small text-white-50">eligible orders found</div>
            </div>
        </div>
    </div>

    <div class="correction-soft p-3 p-lg-4 mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-search text-success fs-5"></i>
            <div>
                <div class="fw-bold">Find an order</div>
                <div class="small text-muted">Search by order number, customer name or phone number.</div>
            </div>
        </div>
        <form method="GET" action="{{ route('pos.corrections.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-9">
                <label for="orderCorrectionSearch" class="form-label small fw-semibold">Order / customer search</label>
                <input type="search" id="orderCorrectionSearch" name="search" value="{{ $search }}" class="form-control" placeholder="e.g. 125, Dara or 012 345 678">
            </div>
            <div class="col-6 col-lg-2">
                <button type="submit" class="btn correction-btn w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-6 col-lg-1">
                <a href="{{ route('pos.corrections.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>

    <div class="correction-card overflow-hidden">
        <div class="p-3 p-lg-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="h6 fw-bold mb-1">Eligible completed POS orders</h2>
                <div class="small text-muted">Cash · Paid · Completed · Non-delivery · No external payment or posted cash-ledger entry</div>
            </div>
            <span class="correction-pill good"><i class="bi bi-shield-check me-1"></i>Administrator only</span>
        </div>
        <div class="table-responsive">
            <table class="table correction-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Order</th>
                        <th>Date / time</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Cashier</th>
                        <th class="text-end">Paid total</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold">#{{ $order->order_id }}</div>
                                <div class="small text-muted">{{ $order->orderType?->order_name ?: 'POS sale' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold small">{{ optional($order->order_date)->format('d M Y') }}</div>
                                <div class="small text-muted">{{ optional($order->order_date)->format('h:i A') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->customer?->name ?: 'Walk-in customer' }}</div>
                                <div class="small text-muted">{{ $order->customer?->phone ?: '—' }}</div>
                            </td>
                            <td>{{ number_format((int) $order->order_items_count) }} line(s)</td>
                            <td>{{ $order->cashier?->name ?: '—' }}</td>
                            <td class="text-end fw-bold">${{ number_format((float) $order->final_price, 2) }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('pos.corrections.edit', $order->order_id) }}" class="btn btn-sm correction-btn">
                                    <i class="bi bi-pencil-square me-1"></i>Review / correct
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-receipt-cutoff fs-1 text-success d-block mb-2"></i>
                                <div class="fw-bold">No eligible completed orders found</div>
                                <div class="small text-muted">Try another search. KHQR, delivery, unpaid, cancelled or ledger-linked orders are not editable here.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="p-3 border-top">{{ $orders->links() }}</div>
        @endif
    </div>

    <div class="alert alert-warning border-0 rounded-3 mt-4 small mb-0">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Corrections are deliberately restricted to eligible completed Cash POS orders. Online/KHQR payments, delivery orders and sales already posted to the cash ledger require a separate payment or fulfillment adjustment workflow.
    </div>
</div>
@endsection
