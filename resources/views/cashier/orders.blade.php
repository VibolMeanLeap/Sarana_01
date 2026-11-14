@extends('layout.cashier')
@section('page_title', 'Orders')
@section('content')
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div><div class="eyebrow mb-2">Order processing</div><h1 class="h3 fw-bold mb-1">Order queue</h1><p class="text-muted mb-0">Your POS sales and incoming website orders. Cash-on-delivery orders stay unpaid until the driver confirms cash collection at handoff.</p></div>
    <a href="{{ route('pos.dashboard') }}" class="btn btn-green"><i class="bi bi-plus-lg me-1"></i>New sale</a>
</div>
@if($errors->any())
    <div class="alert alert-danger"><strong>Unable to update order.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<section class="panel p-3 p-lg-4">
    <form method="GET" action="{{ route('cashier.orders') }}" class="mb-3" id="cashierOrdersFilterForm">
        <input type="hidden" name="period" id="cashierOrdersPeriod" value="{{ request('period') }}">
        <section class="p-3 p-lg-4 rounded-4 mb-3" style="background:linear-gradient(135deg,#e5f3e9 0%,#f3faf5 60%,#eaf6ee 100%);border:1px solid #cde4d4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div><div class="small fw-bold" style="color:#176b4b"><i class="bi bi-calendar-range me-1"></i>Date range</div><div class="small text-muted">Choose a quick period or enter a custom range.</div></div>
                <div class="d-flex flex-wrap gap-2" role="group" aria-label="Quick order date range">
                    <button type="button" class="btn btn-sm {{ request('period') === 'today' ? 'btn-green' : 'btn-outline-success' }}" data-cashier-period="today"><i class="bi bi-sun me-1"></i>Today</button>
                    <button type="button" class="btn btn-sm {{ request('period') === 'week' ? 'btn-green' : 'btn-outline-success' }}" data-cashier-period="week"><i class="bi bi-calendar-week me-1"></i>This week</button>
                    <button type="button" class="btn btn-sm {{ request('period') === 'month' ? 'btn-green' : 'btn-outline-success' }}" data-cashier-period="month"><i class="bi bi-calendar3 me-1"></i>This month</button>
                </div>
            </div>
            <div class="row g-2">
                <div class="col-12 col-sm-6 col-lg-3"><label for="cashierOrdersStartDate" class="form-label small fw-semibold mb-1">From date</label><input id="cashierOrdersStartDate" type="date" name="start_date" value="{{ request('start_date') }}" class="form-control"></div>
                <div class="col-12 col-sm-6 col-lg-3"><label for="cashierOrdersEndDate" class="form-label small fw-semibold mb-1">To date</label><input id="cashierOrdersEndDate" type="date" name="end_date" value="{{ request('end_date') }}" class="form-control"></div>
            </div>
        </section>
        <section class="p-3 p-lg-4 rounded-4" style="background:linear-gradient(135deg,#f1f8f3 0%,#fbfefc 100%);border:1px solid #d5e8da">
            <div class="d-flex align-items-center gap-2 mb-3"><span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:32px;height:32px;background:#dcefe2;color:#176b4b"><i class="bi bi-sliders"></i></span><div><div class="small fw-bold text-dark">Order filters</div><div class="small text-muted">Search and payment status</div></div></div>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5"><label class="form-label small fw-semibold">Search order</label><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Order number or payment status"></div>
                <div class="col-12 col-sm-6 col-lg-3"><label class="form-label small fw-semibold">Payment</label><select name="status" class="form-select"><option value="">All payments</option><option value="paid" @selected(request('status') === 'paid')>Paid</option><option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option></select></div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex gap-2"><button class="btn btn-green flex-grow-1"><i class="bi bi-funnel me-1"></i>Apply filters</button><a href="{{ route('cashier.orders') }}" class="btn btn-outline-secondary">Reset</a></div>
            </div>
        </section>
    </form>
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Order</th><th>Customer / phone</th><th>Items</th><th>Type / source</th><th>Payment</th><th>Total</th><th>Order status</th><th>Update</th><th>Date</th></tr></thead><tbody>
        @forelse($orders as $order)
            <tr>
                <td class="fw-bold">#{{ $order->order_id }}</td>
                <td><strong class="d-block">{{ $order->customer->name ?? 'Walk-in customer' }}</strong><span class="small text-muted">{{ $order->customer->phone ?? 'No phone recorded' }}</span></td>
                <td>{{ $order->orderItems->sum('quantity') }}</td>
                <td><strong class="d-block">{{ $order->orderType->order_name ?? '—' }}</strong><span class="badge text-bg-light">{{ ucfirst($order->order_source ?? 'pos') }}</span></td>
                <td>
                    <span class="badge {{ $order->is_paid ? 'text-bg-success' : 'text-bg-warning' }}">{{ $order->payment_status ?: ($order->is_paid ? 'Paid' : 'Unpaid') }}</span>
                    @if(in_array(strtolower(trim((string) $order->paymentMethod?->payment_name)), ['cash on delivery', 'cash-on-delivery', 'cod'], true))
                        <div class="small {{ $order->is_paid ? 'text-success' : 'text-warning-emphasis' }} mt-1">{{ $order->is_paid ? 'Collected' : 'Collect $'.number_format((float)($order->final_price ?? $order->total_amount), 2).' at handoff' }}</div>
                        @if($order->is_paid && $order->cod_collected_amount)<div class="small text-success">Received ${{ number_format((float) $order->cod_collected_amount, 2) }}</div>@endif
                        @if($order->is_paid && $order->cod_collected_at)<div class="small text-muted">{{ $order->cod_collected_at->format('d M, h:i A') }}</div>@endif
                        @if($order->is_paid && $order->cod_collection_note)<div class="small text-muted" title="{{ $order->cod_collection_note }}">{{ \Illuminate\Support\Str::limit($order->cod_collection_note, 60) }}</div>@endif
                    @endif
                </td>
                <td class="fw-bold">${{ number_format((float)($order->final_price ?? $order->total_amount), 2) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $order->order_status ?? 'pending')) }}</td>
                <td>
                    @if(($order->order_source ?? 'pos') === 'website')
                        <span class="small text-muted"><i class="bi bi-shield-lock me-1"></i>Administrator manages website orders</span>
                    @elseif(($order->order_status ?? 'pending') !== 'cancelled' && ($order->order_status ?? 'pending') !== 'completed')
                        @php
                            $isDeliveryOrder = strtolower(trim((string) $order->orderType?->order_name)) === 'delivery';
                            $paymentMethodName = strtolower(trim((string) $order->paymentMethod?->payment_name));
                            $isCod = in_array($paymentMethodName, ['cash on delivery', 'cash-on-delivery', 'cod'], true);
                            $hasDeliveryRecord = (int) ($order->delivery_count ?? 0) > 0;
                            $amountDue = (float) ($order->final_price ?? $order->total_amount ?? 0);
                            $next = $isDeliveryOrder
                                ? ['pending' => 'confirmed', 'confirmed' => 'preparing', 'preparing' => 'ready', 'ready' => 'out_for_delivery', 'out_for_delivery' => 'completed'][$order->order_status ?? 'pending'] ?? null
                                : ['pending' => 'confirmed', 'confirmed' => 'preparing', 'preparing' => 'ready', 'ready' => 'completed'][$order->order_status ?? 'pending'] ?? null;
                        @endphp
                        @if($isDeliveryOrder && $order->is_paid && !$hasDeliveryRecord)
                            <span class="small text-muted"><i class="bi bi-truck me-1"></i>Waiting for administrator to assign delivery</span>
                        @elseif(!$order->is_paid && !$isCod)
                            <form method="POST" action="{{ route('cashier.orders.status', $order->order_id) }}" class="d-flex gap-1">
                                @csrf
                                <input type="hidden" name="order_status" value="cancelled">
                                <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Cancel this unpaid order and return its reserved stock?')">Cancel / release stock</button>
                            </form>
                        @elseif($next && (!$isDeliveryOrder || $hasDeliveryRecord))
                            @if($isCod && !$order->is_paid && $next === 'completed')
                                <form method="POST" action="{{ route('cashier.orders.status', $order->order_id) }}" class="d-grid gap-2" style="min-width:220px">
                                    @csrf
                                    <input type="hidden" name="order_status" value="completed">
                                    <div class="rounded-3 border border-warning-subtle bg-warning-subtle p-2">
                                        <div class="small fw-bold text-dark mb-1"><i class="bi bi-cash-coin me-1"></i>Collect at handoff</div>
                                        <label class="form-label small mb-1" for="cod-amount-{{ $order->order_id }}">Cash received (USD)</label>
                                        <input id="cod-amount-{{ $order->order_id }}" type="number" name="cod_collected_amount" min="0" max="99999999" step="0.01" value="{{ number_format($amountDue, 2, '.', '') }}" class="form-control form-control-sm" required>
                                        <div class="small text-muted mt-1">Exact amount due: ${{ number_format($amountDue, 2) }}</div>
                                        <label class="form-label small mt-2 mb-1" for="cod-note-{{ $order->order_id }}">Collection note (optional)</label>
                                        <input id="cod-note-{{ $order->order_id }}" type="text" name="cod_collection_note" maxlength="500" class="form-control form-control-sm" data-optional placeholder="e.g. Received by driver">
                                        <label class="form-check small mt-2 mb-0"><input class="form-check-input" type="checkbox" name="cod_collected_confirmed" value="1" required><span class="form-check-label fw-semibold">I confirm cash was received.</span></label>
                                    </div>
                                    <button class="btn btn-sm btn-success" type="submit"><i class="bi bi-check-circle me-1"></i>Confirm delivered & paid</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('cashier.orders.status', $order->order_id) }}" class="d-flex gap-1">
                                    @csrf
                                    <input type="hidden" name="order_status" value="{{ $next }}">
                                    <button class="btn btn-sm btn-outline-success" type="submit">{{ $next === 'out_for_delivery' ? 'Dispatch' : ($next === 'completed' && $isDeliveryOrder ? 'Mark delivered' : 'Mark '.str_replace('_', ' ', $next)) }}</button>
                                </form>
                            @endif
                        @else
                            <span class="small text-muted">—</span>
                        @endif
                    @else
                        <span class="small text-muted">—</span>
                    @endif
                </td>
                <td class="text-nowrap" style="min-width:96px">
                    <div style="color:#294233;font-size:11px;font-weight:850;line-height:1.25">{{ optional($order->order_date)->format('d M Y') ?: '—' }}</div>
                    <div style="display:flex;align-items:center;gap:4px;margin-top:4px;color:#839087;font-size:10px;line-height:1.2"><i class="bi bi-clock" aria-hidden="true"></i><span>{{ optional($order->order_date)->format('h:i A') ?: '—' }}</span></div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted py-5">No orders match your search.</td></tr>
        @endforelse
    </tbody></table></div>
    <div class="mt-3">{{ $orders->links('pagination::simple-bootstrap-5') }}</div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('cashierOrdersFilterForm');
    const periodInput = document.getElementById('cashierOrdersPeriod');
    document.querySelectorAll('[data-cashier-period]').forEach(button => {
        button.addEventListener('click', () => {
            if (!form || !periodInput) return;
            periodInput.value = button.dataset.cashierPeriod || '';
            const start = form.querySelector('[name="start_date"]');
            const end = form.querySelector('[name="end_date"]');
            if (start) start.value = '';
            if (end) end.value = '';
            form.requestSubmit();
        });
    });
    ['start_date', 'end_date'].forEach(field => {
        form?.querySelector(`[name="${field}"]`)?.addEventListener('change', () => {
            if (periodInput) periodInput.value = '';
        });
    });
});
</script>
@endpush
