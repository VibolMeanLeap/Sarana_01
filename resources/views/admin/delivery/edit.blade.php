@extends('layout.admin')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="small text-muted text-uppercase fw-semibold mb-1">Operations / Delivery</div>
        <h1 class="h3 fw-bold mb-1">Update delivery #{{ $delivery->delivery_id }}</h1>
        <p class="text-muted mb-0">Edit the address, delivery cost, or fulfillment status.</p>
    </div>
    <a href="{{ route('delivery.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to deliveries</a>
</div>
<div class="card border-0 shadow-sm" style="max-width:760px">
    <div class="card-body p-4">
        <div class="rounded-3 bg-light p-3 mb-4">
            <div class="small text-muted">Related order</div>
            <div class="fw-bold">Order #{{ $delivery->order_id }}</div>
            <div class="small text-muted">{{ $delivery->order?->customer?->name ?? 'Walk-in customer' }}</div>
        </div>
        <form action="{{ route('delivery.update', $delivery->delivery_id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Delivery address</label>
                <textarea name="address" rows="3" class="form-control" maxlength="255" required>{{ old('address', $delivery->address) }}</textarea>
                @error('address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Delivery cost (USD)</label>
                    <input type="number" name="cost" step="0.01" min="0" class="form-control" value="{{ old('cost', $delivery->cost) }}" required>
                    @error('cost')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Fulfillment status</label>
                    <select name="status" class="form-select" required>
                        @foreach(['pending' => 'Pending', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', strtolower($delivery->status)) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('delivery.index') }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Save delivery</button>
            </div>
        </form>
    </div>
</div>
@endsection
