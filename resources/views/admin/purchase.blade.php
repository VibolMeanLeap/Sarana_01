@extends('layout.admin')
@section('page_title', 'Purchases')
@section('topbar_title', 'Purchasing & suppliers')
@section('content')
<div class="page-heading">
    <div><div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Inventory operations</div><h1>Purchases</h1><p>Record supplier purchases, receive stock and keep purchase expenses aligned.</p></div>
    <div class="d-flex flex-wrap gap-2"><a href="{{ route('purchase.create') }}" class="btn btn-cc"><i class="bi bi-plus-lg me-1"></i> Record purchase</a></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="small text-muted">Matching purchase records</div><div class="fs-3 fw-bold">{{ number_format($summary['count']) }}</div></div><span class="rounded-3 p-3" style="background:#e9f4ee;color:#176b4b"><i class="bi bi-receipt fs-4"></i></span></div></div></div>
    <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="small text-muted">Units received</div><div class="fs-3 fw-bold">{{ number_format($summary['quantity']) }}</div></div><span class="rounded-3 p-3" style="background:#edf4ff;color:#3567a8"><i class="bi bi-box-seam fs-4"></i></span></div></div></div>
    <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="small text-muted">Purchase outflow (filtered)</div><div class="fs-3 fw-bold text-success">${{ number_format($summary['spend'], 2) }}</div></div><span class="rounded-3 p-3" style="background:#fff4df;color:#9b6a1c"><i class="bi bi-cash-stack fs-4"></i></span></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h2 class="h5 fw-bold mb-1">Purchase register</h2><div class="small text-muted">Search and filter purchase history. Totals update with your selected filters.</div></div>@include('admin.partials.export-actions', ['exportType' => 'purchases'])</div>
    <div class="card-body">
        <form method="GET" action="{{ route('purchase.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-12 col-lg-3"><label class="form-label small fw-semibold">Search</label><input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Purchase ID, product or supplier"></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">Product</label><select name="product_id" class="form-select form-select-sm"><option value="">All products</option>@foreach($products as $product)<option value="{{ $product->product_id }}" @selected(request('product_id') == $product->product_id)>{{ $product->product_name }}</option>@endforeach</select></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">Supplier</label><select name="supplier_id" class="form-select form-select-sm"><option value="">All suppliers</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->supplier_id }}" @selected(request('supplier_id') == $supplier->supplier_id)>{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="col-6 col-lg-1"><label class="form-label small fw-semibold">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm"></div>
            <div class="col-6 col-lg-1"><label class="form-label small fw-semibold">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm"></div>
            <div class="col-6 col-lg-1"><label class="form-label small fw-semibold">Sort</label><select name="sort_dir" class="form-select form-select-sm"><option value="desc" @selected(request('sort_dir','desc')==='desc')>Newest</option><option value="asc" @selected(request('sort_dir')==='asc')>Oldest</option></select></div>
            <div class="col-6 col-lg-1"><label class="form-label small fw-semibold">Rows</label><select name="per_page" class="form-select form-select-sm">@foreach([10,15,25,50,100] as $size)<option value="{{ $size }}" @selected((string)request('per_page',15)===(string)$size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-12 col-lg-1 d-flex gap-1"><button class="btn btn-cc btn-sm flex-grow-1">Apply</button><a href="{{ route('purchase.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset filters"><i class="bi bi-arrow-counterclockwise"></i></a></div>
        </form>
        @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Purchase</th><th>Product</th><th>Supplier</th><th class="text-end">Qty</th><th class="text-end">Unit cost</th><th class="text-end">Total</th><th>Recorded by</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($purchases as $purchase)
                <tr><td><span class="fw-bold">#{{ $purchase->purchase_id }}</span></td><td><div class="fw-semibold">{{ $purchase->product->product_name ?? 'Deleted product' }}</div><div class="small text-muted">{{ $purchase->product?->isStockTracked() ? 'Stock item' : 'Product unavailable' }}</div></td><td>{{ $purchase->supplier->name ?? 'Unknown supplier' }}</td><td class="text-end">{{ number_format($purchase->quantity) }}</td><td class="text-end">${{ number_format($purchase->cost_price,2) }}</td><td class="text-end fw-bold">${{ number_format($purchase->total_cost,2) }}</td><td>{{ $purchase->user->name ?? '—' }}</td><td class="text-nowrap">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</td><td class="text-end text-nowrap"><a href="{{ route('purchase.edit',$purchase->purchase_id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a><form action="{{ route('purchase.destroy',$purchase->purchase_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse and remove purchase #{{ $purchase->purchase_id }}? This is allowed only when sufficient stock remains.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Reverse purchase"><i class="bi bi-arrow-counterclockwise"></i></button></form></td></tr>
            @empty<tr><td colspan="9" class="text-center py-5"><i class="bi bi-inbox fs-1 d-block text-muted mb-2"></i><div class="fw-semibold">No purchases found</div><div class="small text-muted">Try changing your filters or record a new purchase.</div></td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><div class="small text-muted">Showing {{ $purchases->firstItem() ?? 0 }}–{{ $purchases->lastItem() ?? 0 }} of {{ $purchases->total() }} records · Filtered total: <strong class="text-dark">${{ number_format($summary['spend'],2) }}</strong></div>{{ $purchases->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
