@extends('layout.admin')
@section('page_title','Update delivery')
@section('topbar_title','Delivery fulfillment')
@section('content')
@php
    $paymentMethodName = strtolower(trim((string) $delivery->order?->paymentMethod?->payment_name));
    $isUnpaidCod = in_array($paymentMethodName, ['cash on delivery', 'cash-on-delivery', 'cod'], true) && ! (bool) $delivery->order?->is_paid;
    $codAmountDue = (float) ($delivery->order?->final_price ?? $delivery->order?->total_amount ?? 0);
@endphp
<div class="page-heading"><div><div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Order fulfillment</div><h1>Delivery #{{ $delivery->delivery_id }}</h1><p>Update the drop-off details and fulfillment status.</p></div><a href="{{ route('delivery.index') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-1"></i>Back to deliveries</a></div>
<div class="row g-4"><div class="col-12 col-xl-8"><div class="card"><div class="card-body p-4">
    @if($errors->any())<div class="alert alert-danger"><strong>Unable to save changes.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="rounded-3 p-3 mb-4" style="background:#f4f8f5"><div class="small text-muted">Related order</div><div class="h5 fw-bold mb-1">Order #{{ $delivery->order_id }}</div><div>{{ $delivery->order?->customer?->name ?? 'Walk-in customer' }} · {{ $delivery->order?->order_date?->format('d M Y H:i') ?? '—' }}<div class="small mt-2">Payment: <strong class="{{ $delivery->order?->is_paid ? 'text-success' : 'text-warning' }}">{{ $delivery->order?->is_paid ? 'Paid' : ($delivery->order?->paymentMethod?->payment_name ?: 'Unpaid') }}</strong>@if($isUnpaidCod) · Amount due: <strong>${{ number_format($codAmountDue, 2) }}</strong>@endif</div></div></div>
    <form action="{{ route('delivery.update',$delivery->delivery_id) }}" method="POST" novalidate>@csrf @method('PUT')
        <div class="mb-3"><label class="form-label fw-semibold">Delivery address <span class="text-danger">*</span></label><textarea name="address" rows="3" maxlength="255" class="form-control @error('address') is-invalid @enderror" required>{{ old('address',$delivery->address) }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="row g-3 mb-4"><div class="col-md-6"><label class="form-label fw-semibold">Delivery fee (USD)</label><div class="input-group"><span class="input-group-text">$</span><input type="number" name="cost" min="0" step="0.01" max="99999999" value="{{ old('cost',$delivery->cost) }}" class="form-control @error('cost') is-invalid @enderror" required>@error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div><div class="col-md-6"><label class="form-label fw-semibold">Fulfillment status</label><select name="status" class="form-select @error('status') is-invalid @enderror" required>@foreach($allowedStatuses as $value=>$label)<option value="{{ $value }}" @selected(old('status',strtolower($delivery->status))===$value)>{{ $label }}</option>@endforeach</select>@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
        @if(in_array(strtolower($delivery->status),['delivered','cancelled'],true))<div class="alert alert-warning small">This delivery is in a final state. Its status cannot be reopened or changed.</div>@else<div class="alert alert-info small">The next status is the only forward transition available. Order and delivery status will be saved together.</div>@endif
        @if($isUnpaidCod && strtolower((string) $delivery->status) === 'out_for_delivery')
            <div id="codCollectionPanel" class="rounded-3 border border-warning-subtle bg-warning-subtle p-3 mb-4" hidden>
                <div class="fw-bold mb-1"><i class="bi bi-cash-coin me-2"></i>Cash-on-delivery collection</div>
                <p class="small text-muted mb-3">Only select Delivered after the driver has physically received the full payment from the customer. This confirmation marks the order as Paid and records the collector, time and note.</p>
                <div class="row g-3">
                    <div class="col-md-5"><label class="form-label small fw-bold" for="codCollectedAmount">Cash received (USD)</label><input id="codCollectedAmount" type="number" name="cod_collected_amount" min="0" max="99999999" step="0.01" value="{{ old('cod_collected_amount', number_format($codAmountDue, 2, '.', '')) }}" class="form-control"><div class="form-text">Must equal ${{ number_format($codAmountDue, 2) }}.</div>@error('cod_collected_amount')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    <div class="col-md-7"><label class="form-label small fw-bold" for="codCollectionNote">Collection note (optional)</label><input id="codCollectionNote" type="text" name="cod_collection_note" maxlength="500" data-optional value="{{ old('cod_collection_note') }}" class="form-control" placeholder="e.g. Driver received cash from customer"></div>
                    <div class="col-12"><label class="form-check"><input id="codCollectedConfirmed" class="form-check-input" type="checkbox" name="cod_collected_confirmed" value="1" @checked(old('cod_collected_confirmed'))><span class="form-check-label fw-semibold">I confirm that the full cash amount was received at the delivery handoff.</span></label>@error('cod_collected_confirmed')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                </div>
            </div>
        @endif
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('delivery.index') }}" class="btn btn-light border">Cancel</a><button class="btn btn-cc"><i class="bi bi-check2-circle me-1"></i>Save changes</button></div>
    </form>
</div></div></div><div class="col-12 col-xl-4"><div class="card"><div class="card-body p-4"><h2 class="h6 fw-bold">Current status</h2><div class="fs-5 fw-bold text-capitalize">{{ str_replace('_',' ',$delivery->status) }}</div><p class="small text-muted mt-2 mb-0">Allowed flow: Pending → Confirmed → Preparing → Ready → Out for delivery → Delivered. Each save advances one step. Cancellation is recorded and does not automatically refund a paid order.</p></div></div></div></div>
@if($isUnpaidCod && strtolower((string) $delivery->status) === 'out_for_delivery')
<script>
(() => {
    const status = document.querySelector('select[name="status"]');
    const panel = document.getElementById('codCollectionPanel');
    const amount = document.getElementById('codCollectedAmount');
    const confirmed = document.getElementById('codCollectedConfirmed');
    if (!status || !panel || !amount || !confirmed) return;
    const sync = () => { const active = status.value === 'delivered'; panel.hidden = !active; amount.required = active; confirmed.required = active; };
    status.addEventListener('change', sync);
    sync();
})();
</script>
@endif
@endsection
