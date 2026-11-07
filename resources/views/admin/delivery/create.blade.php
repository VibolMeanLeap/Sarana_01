@extends('layout.admin')

@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">New Delivery</h5></div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('delivery.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Order</label>
                <select name="order_id" class="form-select" required>
                    <option value="">-- Select order --</option>
                    @foreach($orders as $o)
                        <option value="{{ $o->order_id }}">#{{ $o->order_id }} - {{ $o->customer->name ?? 'Walk-in' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Delivery Address</label>
                <textarea name="address" class="form-control" rows="2" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Delivery Cost</label>
                <input type="number" step="0.01" name="cost" class="form-control" value="0" required>
            </div>
            <button class="btn btn-primary">Create Delivery</button>
            <a href="{{ route('delivery.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
