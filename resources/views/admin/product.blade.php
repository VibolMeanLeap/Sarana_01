@extends('layout.admin')
@section('page_title', 'Products')
@section('topbar_title', 'Product catalogue')

@section('content')
<style>
    .cc-products{--cc-green:#176b4b;--cc-green-dark:#104d38;--cc-green-soft:#e9f4ec;--cc-green-border:#d4e7d9;--cc-ink:#20312a;--cc-muted:#748178;--cc-line:#e5eee8;background:linear-gradient(180deg,#edf5ef 0,#f5f8f5 280px,#f5f8f5 100%);border-radius:22px;padding:clamp(15px,2.1vw,27px);color:var(--cc-ink)}
    .cc-products *{box-sizing:border-box}
    .cc-prod-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:19px}
    .cc-prod-kicker{font-size:10px;font-weight:850;letter-spacing:.15em;text-transform:uppercase;color:#4b8768}
    .cc-prod-title{font-size:clamp(24px,3vw,31px);font-weight:850;letter-spacing:-.045em;margin:4px 0;color:#1d3529}
    .cc-prod-subtitle{color:var(--cc-muted);font-size:13px;margin:0;max-width:650px}
    .cc-prod-actions{display:flex;gap:9px;align-items:center;flex-wrap:wrap}
    .cc-prod-add{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 16px;border:0;border-radius:12px;background:var(--cc-green);color:#fff;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 7px 16px #176b4b22;transition:transform .15s ease,background .15s ease}
    .cc-prod-add:hover{background:var(--cc-green-dark);color:#fff;transform:translateY(-1px)}
    .cc-prod-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
    .cc-prod-stat{min-width:0;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 17px;border:1px solid var(--cc-green-border);border-radius:15px;background:linear-gradient(135deg,#fff 0,#f1f8f3 100%);box-shadow:0 5px 15px #204b3010}
    .cc-prod-stat-label{font-size:10px;font-weight:850;letter-spacing:.07em;text-transform:uppercase;color:#77857b}
    .cc-prod-stat-value{margin-top:3px;color:#1b3f2e;font-size:24px;font-weight:850;letter-spacing:-.04em;line-height:1.15}
    .cc-prod-stat-note{font-size:10px;color:#8a968e;margin-top:3px}
    .cc-prod-stat-icon{width:41px;height:41px;flex:0 0 41px;display:grid;place-items:center;border-radius:13px;background:#dff0e4;color:var(--cc-green);font-size:19px}
    .cc-prod-filter{margin-bottom:16px;padding:16px 17px;border:1px solid #cfe4d4;border-radius:17px;background:linear-gradient(120deg,#e4f1e7 0,#f0f7f1 100%);box-shadow:inset 0 1px 0 #ffffffbf}
    .cc-prod-filter-head{display:flex;align-items:center;gap:10px;margin-bottom:13px}
    .cc-prod-filter-icon{width:34px;height:34px;display:grid;place-items:center;border-radius:11px;background:#cfe8d5;color:var(--cc-green);font-size:15px}
    .cc-prod-filter-title{margin:0;color:#214b35;font-size:14px;font-weight:850}
    .cc-prod-filter-hint{margin:2px 0 0;color:#688071;font-size:11px}
    .cc-prod-filter-grid{display:grid;grid-template-columns:minmax(205px,2fr) repeat(5,minmax(118px,1fr)) auto;gap:9px;align-items:end}
    .cc-prod-field{min-width:0}
    .cc-prod-field label{display:block;margin:0 0 6px;color:#53705e;font-size:9px;font-weight:850;letter-spacing:.07em;text-transform:uppercase}
    .cc-products .form-control,.cc-products .form-select{width:100%;height:41px;min-width:0;border:1px solid #d7e4da;border-radius:10px;background:#fff;color:#24382c;font-size:12px;box-shadow:none}
    .cc-products .form-control:focus,.cc-products .form-select:focus{border-color:#64a77b;box-shadow:0 0 0 3px #176b4b18}
    .cc-prod-filter-actions{display:flex;gap:7px}
    .cc-prod-apply,.cc-prod-reset{height:41px;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 13px;border-radius:10px;font-size:11px;font-weight:800;white-space:nowrap;text-decoration:none}
    .cc-prod-apply{border:0;background:var(--cc-green);color:white}
    .cc-prod-apply:hover{background:var(--cc-green-dark);color:#fff}
    .cc-prod-reset{border:1px solid #cbdacf;background:#f8fbf8;color:#496452}
    .cc-prod-reset:hover{background:#e7f2e9;color:#176b4b}
    .cc-prod-table-card{overflow:hidden;border:1px solid var(--cc-line);border-radius:17px;background:#fff;box-shadow:0 8px 24px #204b300c}
    .cc-prod-table-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:15px 18px;border-bottom:1px solid var(--cc-line);background:linear-gradient(90deg,#f1f7f2,#fff)}
    .cc-prod-table-title{margin:0;color:#1d3d2c;font-size:15px;font-weight:850}
    .cc-prod-table-meta{margin-top:3px;color:#7b8980;font-size:11px}
    .cc-prod-table-wrap{overflow-x:auto}
    .cc-prod-table{width:100%;min-width:1050px;border-collapse:separate;border-spacing:0}
    .cc-prod-table thead th{padding:12px 13px;border-bottom:1px solid #dce9df;background:#edf5ef;color:#567362;font-size:9px;font-weight:850;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap}
    .cc-prod-table tbody td{padding:12px 13px;border-bottom:1px solid #edf2ed;color:#2c3c32;font-size:12px;vertical-align:middle}
    .cc-prod-table tbody tr:last-child td{border-bottom:0}
    .cc-prod-table tbody tr:hover{background:#f6faf6}
    .cc-prod-thumb{width:44px;height:44px;flex:0 0 44px;display:grid;place-items:center;overflow:hidden;border:1px solid #e0ebe3;border-radius:12px;background:#edf5ef;color:#7ba489}
    .cc-prod-thumb img{width:100%;height:100%;object-fit:cover}
    .cc-prod-name{color:#244a33;font-size:12px;font-weight:850;line-height:1.35}
    .cc-prod-meta{margin-top:3px;color:#89958d;font-size:10px}
    .cc-prod-price{color:#1b5037;font-weight:850;font-variant-numeric:tabular-nums;white-space:nowrap}
    .cc-prod-qty{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border:1px solid #dcebe0;border-radius:999px;background:#f2f8f3;color:#276543;font-size:10px;font-weight:850;white-space:nowrap}
    .cc-prod-type,.cc-prod-status{display:inline-flex;align-items:center;justify-content:center;padding:5px 9px;border-radius:999px;font-size:9px;font-weight:850;white-space:nowrap}
    .cc-prod-type.stock{background:#e8f4eb;color:#286a45}
    .cc-prod-type.nonstock{background:#edf0f1;color:#637079}
    .cc-prod-status.active{background:#e4f4e8;color:#237247}
    .cc-prod-status.inactive{background:#fff0ef;color:#ad4945}
    .cc-prod-actions-cell{display:flex;justify-content:flex-end;gap:5px}
    .cc-prod-icon-btn{width:32px;height:32px;display:inline-grid;place-items:center;border:1px solid #e2ece5;border-radius:10px;background:#fff;color:#527461;text-decoration:none;cursor:pointer}
    .cc-prod-icon-btn:hover{border-color:#bddac5;background:#e9f4ee;color:#176b4b}
    .cc-prod-icon-btn.danger{color:#b65353}
    .cc-prod-icon-btn.danger:hover{border-color:#f0caca;background:#fff0f0;color:#a53c3c}
    .cc-prod-empty{padding:45px 20px;text-align:center;color:#829087}
    .cc-prod-empty i{font-size:31px;color:#8ab99a}
    .cc-prod-pagination{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:13px 17px;border-top:1px solid #edf2ed;background:#fbfdfb}
    .cc-prod-pagination nav p{display:none}
    .cc-prod-pagination .pagination{gap:4px;margin:0}
    .cc-prod-pagination .page-link{border-color:#dce8df;border-radius:9px!important;color:#3d664e;font-size:11px}
    .cc-prod-pagination .page-item.active .page-link{border-color:#176b4b;background:#176b4b;color:#fff}
    .cc-prod-alert{margin-bottom:13px;padding:11px 14px;border:1px solid #c8e4cf;border-radius:12px;background:#eef8f0;color:#285f3b;font-size:12px}
    .cc-prod-alert.error{border-color:#f1cccc;background:#fff3f3;color:#a53c3c}
    @media(max-width:1399px){.cc-prod-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.cc-prod-field.search-field{grid-column:span 2}.cc-prod-filter-actions{align-self:end}}
    @media(max-width:767px){.cc-products{padding:13px;border-radius:16px}.cc-prod-stats{grid-template-columns:1fr}.cc-prod-filter{padding:13px}.cc-prod-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.cc-prod-field.search-field{grid-column:1/-1}.cc-prod-filter-actions{grid-column:1/-1}.cc-prod-filter-actions>*{flex:1}.cc-prod-pagination{align-items:flex-start}}
    @media(max-width:420px){.cc-prod-filter-grid{grid-template-columns:1fr}.cc-prod-field.search-field{grid-column:auto}.cc-prod-filter-actions{grid-column:auto}}
</style>

<div class="cc-products">
    @if(session('success'))
        <div class="cc-prod-alert"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="cc-prod-alert error"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="cc-prod-alert error"><strong>Please review:</strong> {{ $errors->first() }}</div>
    @endif

    <header class="cc-prod-head">
        <div>
            <div class="cc-prod-kicker">Catalogue management</div>
            <h1 class="cc-prod-title">Products</h1>
            <p class="cc-prod-subtitle">Manage product details, pricing, suppliers and stock visibility from one clean catalogue.</p>
        </div>
        <div class="cc-prod-actions">
            @include('admin.partials.export-actions', ['exportType' => 'products'])
            <a href="{{ route('products.create') }}" class="cc-prod-add"><i class="bi bi-plus-lg"></i> Add product</a>
        </div>
    </header>

    <div class="cc-prod-stats">
        <div class="cc-prod-stat">
            <div><div class="cc-prod-stat-label">Products matching filters</div><div class="cc-prod-stat-value">{{ number_format($products->total()) }}</div><div class="cc-prod-stat-note">Total records in this result</div></div>
            <span class="cc-prod-stat-icon"><i class="bi bi-box-seam"></i></span>
        </div>
        <div class="cc-prod-stat">
            <div><div class="cc-prod-stat-label">Shown on this page</div><div class="cc-prod-stat-value">{{ number_format($products->count()) }}</div><div class="cc-prod-stat-note">{{ $products->perPage() }} rows per page</div></div>
            <span class="cc-prod-stat-icon"><i class="bi bi-grid-3x3-gap"></i></span>
        </div>
        <div class="cc-prod-stat">
            <div><div class="cc-prod-stat-label">Available categories</div><div class="cc-prod-stat-value">{{ number_format($categories->count()) }}</div><div class="cc-prod-stat-note">Used to organise the catalogue</div></div>
            <span class="cc-prod-stat-icon"><i class="bi bi-tags"></i></span>
        </div>
    </div>

    <section class="cc-prod-filter" aria-labelledby="ccProductFilterTitle">
        <div class="cc-prod-filter-head">
            <span class="cc-prod-filter-icon"><i class="bi bi-funnel-fill"></i></span>
            <div><h2 class="cc-prod-filter-title" id="ccProductFilterTitle">Find and organise products</h2><p class="cc-prod-filter-hint">Search the catalogue, narrow by product type or status, and choose how records are sorted.</p></div>
        </div>
        <form action="{{ route('products.index') }}" method="GET" class="cc-prod-filter-grid" id="productFilterForm">
            <div class="cc-prod-field search-field"><label for="productSearch">Search catalogue</label><input type="search" id="productSearch" name="search" class="form-control" placeholder="Product name, description or type..." value="{{ request('search') }}"></div>
            <div class="cc-prod-field"><label for="productCategory">Category</label><select name="category" id="productCategory" class="form-select"><option value="">All categories</option>@foreach($categories as $cat)<option value="{{ $cat->category_id }}" @selected((string)request('category', request('category_id')) === (string)$cat->category_id)>{{ $cat->category_name }}</option>@endforeach</select></div>
            <div class="cc-prod-field"><label for="productStatus">Status</label><select name="status" id="productStatus" class="form-select"><option value="">All statuses</option><option value="Active" @selected(request('status') === 'Active')>Active</option><option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option></select></div>
            <div class="cc-prod-field"><label for="productType">Product type</label><select name="product_type" id="productType" class="form-select"><option value="">All types</option><option value="stock" @selected(request('product_type') === 'stock')>Stock</option><option value="non-stock" @selected(request('product_type') === 'non-stock')>Non-stock</option></select></div>
            <div class="cc-prod-field"><label for="productSortBy">Sort by</label><select name="sort_by" id="productSortBy" class="form-select"><option value="product_id" @selected(request('sort_by', 'product_id') === 'product_id')>Recently added</option><option value="product_name" @selected(request('sort_by') === 'product_name')>Product name</option><option value="price" @selected(request('sort_by') === 'price')>Selling price</option><option value="qty" @selected(request('sort_by') === 'qty')>Stock quantity</option></select></div>
            <div class="cc-prod-field"><label for="productSortDir">Order</label><select name="sort_dir" id="productSortDir" class="form-select"><option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('sort_dir') === 'asc')>Ascending</option></select></div>
            <div class="cc-prod-filter-actions"><button type="submit" class="cc-prod-apply"><i class="bi bi-search"></i> Apply</button><a href="{{ route('products.index') }}" class="cc-prod-reset" title="Clear all product filters" aria-label="Clear all product filters"><i class="bi bi-arrow-counterclockwise"></i></a></div>
        </form>
    </section>

    <section class="cc-prod-table-card">
        <div class="cc-prod-table-head">
            <div><h2 class="cc-prod-table-title">Product catalogue</h2><div class="cc-prod-table-meta">Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products</div></div>
            <span class="badge rounded-pill text-bg-light border"><i class="bi bi-shield-check text-success me-1"></i>Catalogue records</span>
        </div>
        <div class="cc-prod-table-wrap">
            <table class="cc-prod-table">
                <thead><tr><th style="width:65px">ID</th><th style="width:70px">Image</th><th>Product</th><th>Category</th><th>Supplier</th><th>Price</th><th>Quantity</th><th>Type</th><th>Status</th><th class="text-end" style="width:100px">Actions</th></tr></thead>
                <tbody>
                @forelse($products as $row)
                    @php($trackedStock = $row->isStockTracked())
                    <tr>
                        <td class="text-muted fw-semibold">#{{ $row->product_id }}</td>
                        <td><span class="cc-prod-thumb">@if($row->image)<img src="{{ asset('images/' . $row->image) }}" alt="{{ $row->product_name }}">@else<i class="bi bi-cup-hot"></i>@endif</span></td>
                        <td><div class="cc-prod-name">{{ $row->product_name }}</div><div class="cc-prod-meta">Product ID {{ $row->product_id }}</div></td>
                        <td>{{ $row->category?->category_name ?? 'Uncategorised' }}</td>
                        <td>{{ $row->supplier?->name ?? '—' }}</td>
                        <td class="cc-prod-price">${{ $row->price !== null ? number_format((float)$row->price, 2) : '—' }}</td>
                        <td>@if($trackedStock)<span class="cc-prod-qty"><i class="bi bi-box-seam"></i>{{ number_format((int)($row->qty ?? 0)) }}</span>@else<span class="text-muted">Not tracked</span>@endif</td>
                        <td><span class="cc-prod-type {{ $trackedStock ? 'stock' : 'nonstock' }}">{{ $trackedStock ? 'Stock' : 'Non-stock' }}</span></td>
                        <td>@if(strtolower((string)($row->status ?? '')) === 'active')<span class="cc-prod-status active"><i class="bi bi-check-circle-fill me-1"></i>Active</span>@else<span class="cc-prod-status inactive"><i class="bi bi-pause-circle-fill me-1"></i>{{ $row->status ?: 'Inactive' }}</span>@endif</td>
                        <td><div class="cc-prod-actions-cell"><a href="{{ route('products.edit', $row->product_id) }}" class="cc-prod-icon-btn" title="Edit product" aria-label="Edit {{ $row->product_name }}"><i class="bi bi-pencil-square"></i></a><form action="{{ route('products.destroy', $row->product_id) }}" method="POST" class="d-inline delete-form">@csrf @method('DELETE')<button type="button" class="cc-prod-icon-btn danger btn-delete" title="Delete product" aria-label="Delete {{ $row->product_name }}"><i class="bi bi-trash3"></i></button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="cc-prod-empty"><i class="bi bi-box-seam d-block mb-2"></i><div class="fw-bold text-dark">No products found</div><div class="small">Try changing the filters or add a product to your catalogue.</div><a href="{{ route('products.create') }}" class="cc-prod-add mt-3"><i class="bi bi-plus-lg"></i>Add product</a></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="cc-prod-pagination"><span class="small text-muted">Page {{ $products->currentPage() }} of {{ max(1, $products->lastPage()) }}</span>{{ $products->links('pagination::simple-bootstrap-5') }}</div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cc-products .btn-delete').forEach(function (button) {
        button.addEventListener('click', function () {
            const form = this.closest('.delete-form');
            if (!form) return;
            if (window.Swal) {
                Swal.fire({
                    title: 'Delete this product?',
                    text: 'Products referenced by orders, purchases or stock history cannot be deleted.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#176b4b',
                    cancelButtonColor: '#8a9690',
                    confirmButtonText: 'Yes, delete product',
                    cancelButtonText: 'Keep product'
                }).then(function (result) { if (result.isConfirmed) form.submit(); });
            } else if (window.confirm('Delete this product?')) {
                form.submit();
            }
        });
    });
});
</script>
@endsection
