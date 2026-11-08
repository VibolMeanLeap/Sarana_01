@extends('layout.admin')

@section('page_title', 'Record purchase')
@section('topbar_title', 'Record purchase')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Inventory & suppliers</div>
            <h1 class="h3 fw-bold mb-1">Record a purchase</h1>
            <p class="text-muted mb-0">Add received stock and record its purchase cost in one step.</p>
        </div>
        <a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-2"></i>Back to purchases
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-4 shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please review the highlighted fields.</div>
            <div class="small">{{ $errors->first() }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger rounded-4" role="alert">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:44px;height:44px;background:#eaf5ee;color:#176b4b"><i class="bi bi-box-seam fs-4"></i></span>
                        <div>
                            <h2 class="h5 fw-bold mb-1">Purchase details</h2>
                            <p class="small text-muted mb-0">Fields marked with <span class="text-danger">*</span> are required.</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('purchase.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="supplier_id" class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                                <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">Choose a supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->supplier_id }}" @selected((string) old('supplier_id') === (string) $supplier->supplier_id)>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="product_id" class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                                <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                    <option value="">Choose a product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->product_id }}" @selected((string) old('product_id') === (string) $product->product_id)>
                                            {{ $product->product_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('product_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="quantity" class="form-label fw-semibold">Quantity received <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input id="quantity" type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" min="1" step="1" inputmode="numeric" placeholder="e.g. 24" required>
                                    <span class="input-group-text">units</span>
                                </div>
                                @error('quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">Enter a whole number greater than zero.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cost_price" class="form-label fw-semibold">Unit cost price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input id="cost_price" type="number" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price') }}" min="0" step="0.01" inputmode="decimal" placeholder="0.00" required>
                                </div>
                                @error('cost_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <div class="form-text">Cost for one unit, in USD.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="purchase_date" class="form-label fw-semibold">Purchase date <span class="text-danger">*</span></label>
                                <input id="purchase_date" type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" required>
                                @error('purchase_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 px-4 py-3 d-flex flex-wrap justify-content-end gap-2">
                        <a href="{{ route('purchase.index') }}" class="btn btn-light border rounded-pill px-4">Cancel</a>
                        <button type="submit" class="btn btn-success rounded-pill px-4"><i class="bi bi-check2-circle me-2"></i>Save purchase</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <aside class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width:44px;height:44px;background:#fff4df;color:#96651a"><i class="bi bi-info-circle fs-4"></i></span>
                    <h2 class="h5 fw-bold">What happens after saving?</h2>
                    <p class="text-muted small">Coffee Corner Aura records the purchase and updates related inventory and accounting records together.</p>
                    <div class="d-flex gap-3 mb-3"><i class="bi bi-box-arrow-in-down text-success fs-5"></i><div><div class="fw-semibold">Stock increases</div><div class="small text-muted">Received quantity is added to the selected product.</div></div></div>
                    <div class="d-flex gap-3 mb-3"><i class="bi bi-journal-check text-success fs-5"></i><div><div class="fw-semibold">Stock movement is logged</div><div class="small text-muted">An IN movement records the purchase reference.</div></div></div>
                    <div class="d-flex gap-3"><i class="bi bi-cash-coin text-success fs-5"></i><div><div class="fw-semibold">Expense is linked</div><div class="small text-muted">The purchase cost is included in the expense records.</div></div></div>
                    <div class="alert alert-light border rounded-3 mt-4 mb-0 small"><i class="bi bi-shield-check text-success me-2"></i>All changes are processed in a database transaction to keep stock and purchase records in sync.</div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
