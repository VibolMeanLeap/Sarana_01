@extends('layout.admin')

@section('page_title', 'Edit purchase')
@section('topbar_title', 'Edit purchase')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Inventory & suppliers</div>
            <h1 class="h3 fw-bold mb-1">Edit purchase #{{ $purchase->purchase_id }}</h1>
            <p class="text-muted mb-0">Update purchase details while keeping the stock and expense records aligned.</p>
        </div>
        <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary rounded-pill px-3"><i class="bi bi-arrow-left me-2"></i>Back to purchases</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-4 shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please review the highlighted fields.</div>
            <div class="small">{{ $errors->first() }}</div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white px-4 py-3"><h2 class="h5 fw-bold mb-1">Purchase details</h2><p class="small text-muted mb-0">All fields are required.</p></div>
                <form action="{{ route('purchase.update', $purchase->purchase_id) }}" method="POST" novalidate>
                    @csrf @method('PUT')
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="supplier_id" class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                                <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">Choose a supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->supplier_id }}" @selected((string) old('supplier_id', $purchase->supplier_id) === (string) $supplier->supplier_id)>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="product_id" class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                                <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                    <option value="">Choose a product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->product_id }}" @selected((string) old('product_id', $purchase->product_id) === (string) $product->product_id)>{{ $product->product_name }}</option>
                                    @endforeach
                                </select>
                                @error('product_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="quantity" class="form-label fw-semibold">Quantity received <span class="text-danger">*</span></label>
                                <div class="input-group"><input id="quantity" type="number" name="quantity" min="1" step="1" inputmode="numeric" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', $purchase->quantity) }}" required><span class="input-group-text">units</span></div>
                                @error('quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="cost_price" class="form-label fw-semibold">Unit cost price <span class="text-danger">*</span></label>
                                <div class="input-group"><span class="input-group-text">$</span><input id="cost_price" type="number" name="cost_price" min="0" step="0.01" inputmode="decimal" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', $purchase->cost_price) }}" required></div>
                                @error('cost_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="purchase_date" class="form-label fw-semibold">Purchase date <span class="text-danger">*</span></label>
                                <input id="purchase_date" type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : '') }}" required>
                                @error('purchase_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 px-4 py-3 d-flex justify-content-end gap-2">
                        <a href="{{ route('purchase.index') }}" class="btn btn-light border rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-success rounded-pill px-4"><i class="bi bi-check2-circle me-2"></i>Save changes</button>
                    </div>
                </form>
            </section>
        </div>
        <div class="col-12 col-xl-4">
            <aside class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:44px;height:44px;background:#fff4df;color:#96651a"><i class="bi bi-exclamation-circle fs-4"></i></span>
                <h2 class="h5 fw-bold">Inventory adjustment notice</h2>
                <p class="small text-muted mb-0">Changing this purchase reverses the original received quantity and applies the revised quantity within a database transaction. If the original quantity is no longer available in stock, the update is blocked to protect inventory accuracy.</p>
            </div></aside>
        </div>
    </div>
</div>
@endsection
