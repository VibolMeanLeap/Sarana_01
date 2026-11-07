@extends('layout.admin')
@section('page_title', 'Edit stock log')
@section('topbar_title', 'Inventory')
@section('content')
<div class="page-heading"><div><h1>Edit stock movement #{{ $log->log_id }}</h1><p>Update the stock record and synchronize the product quantity.</p></div><a href="{{ route('inventory.index') }}" class="btn btn-cc-light">Back to inventory</a></div>
<div class="card"><div class="card-body p-4">
<form action="{{ route('inventory.update', $log->log_id) }}" method="POST">@csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Product</label><select name="product_id" class="form-select" required>@foreach($products as $product)<option value="{{ $product->product_id }}" @selected(old('product_id', $log->product_id) == $product->product_id)>{{ $product->product_name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Movement</label><select name="type" class="form-select" required><option value="in" @selected(strtolower($log->type) === 'in')>Stock in</option><option value="out" @selected(strtolower($log->type) === 'out')>Stock out</option></select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Quantity</label><input type="number" min="1" name="quantity" class="form-control" value="{{ old('quantity', $log->quantity) }}" required></div>
        <div class="col-12"><label class="form-label fw-semibold">Reason</label><input type="text" name="reason" class="form-control" value="{{ old('reason', $log->reason) }}" required></div>
        <div class="col-12"><label class="form-label fw-semibold">Note</label><textarea name="note" class="form-control" rows="3">{{ old('note', $log->note) }}</textarea></div>
    </div>
    @if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif
    <div class="mt-4 d-flex gap-2"><button class="btn btn-cc">Save changes</button><a href="{{ route('inventory.index') }}" class="btn btn-cc-light">Cancel</a></div>
</form>
</div></div>
@endsection
