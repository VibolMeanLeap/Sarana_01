@extends('layout.admin')

@section('page_title', 'Suppliers')
@section('topbar_title', 'Supplier management')

@push('styles')
<style>
    .supplier-page { --supplier-ink:#202a25; --supplier-muted:#78857d; --supplier-line:#e8eee9; }
    .supplier-page .supplier-heading { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:22px; }
    .supplier-page .supplier-kicker { color:#176b4b; font-size:10px; font-weight:800; letter-spacing:.15em; text-transform:uppercase; margin-bottom:7px; }
    .supplier-page .supplier-title { color:var(--supplier-ink); font-family:'Manrope','DM Sans',sans-serif; font-size:27px; font-weight:800; letter-spacing:-.8px; margin:0; }
    .supplier-page .supplier-subtitle { color:var(--supplier-muted); font-size:13px; margin:7px 0 0; max-width:620px; }
    .supplier-page .supplier-actions { display:flex; align-items:center; flex-wrap:wrap; gap:9px; }
    .supplier-page .supplier-add { display:inline-flex; align-items:center; gap:8px; background:#176b4b; border:1px solid #176b4b; border-radius:11px; color:#fff; font-weight:700; padding:10px 15px; text-decoration:none; box-shadow:0 6px 16px #176b4b20; }
    .supplier-page .supplier-add:hover { background:#104d38; border-color:#104d38; color:#fff; }
    .supplier-page .supplier-stat { height:100%; border:1px solid var(--supplier-line); border-radius:15px; background:#fff; padding:17px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; box-shadow:0 3px 12px rgba(25,55,38,.025); }
    .supplier-page .supplier-stat-label { color:#87938b; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .supplier-page .supplier-stat-value { color:var(--supplier-ink); font-family:'Manrope','DM Sans',sans-serif; font-size:25px; line-height:1.2; font-weight:800; margin-top:6px; }
    .supplier-page .supplier-stat-icon { width:42px; height:42px; flex:0 0 42px; display:grid; place-items:center; border-radius:13px; background:#eaf4ed; color:#176b4b; font-size:18px; }
    .supplier-page .supplier-filter-card { border:1px solid var(--supplier-line); border-radius:15px; background:#fff; padding:16px; }
    .supplier-page .supplier-filter-label { color:#69766e; font-size:11px; font-weight:700; margin-bottom:6px; }
    .supplier-page .supplier-filter-card .form-control,
    .supplier-page .supplier-filter-card .form-select { min-height:42px; font-size:13px; }
    .supplier-page .supplier-table-card { border:1px solid var(--supplier-line); border-radius:16px; background:#fff; overflow:hidden; box-shadow:0 3px 12px rgba(25,55,38,.025); }
    .supplier-page .supplier-table-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 20px; border-bottom:1px solid var(--supplier-line); }
    .supplier-page .supplier-table-title { font-family:'Manrope','DM Sans',sans-serif; font-size:15px; font-weight:800; color:var(--supplier-ink); margin:0; }
    .supplier-page .supplier-count { color:#637067; background:#f3f6f4; border:1px solid #e7ece8; border-radius:99px; font-size:11px; font-weight:700; padding:5px 10px; white-space:nowrap; }
    .supplier-page .table { margin:0; min-width:760px; }
    .supplier-page .table thead th { padding:13px 18px; background:#fbfcfb; border-bottom:1px solid var(--supplier-line); font-size:10px; letter-spacing:.09em; color:#89958d; }
    .supplier-page .table tbody td { padding:15px 18px; border-bottom:1px solid #eff2ef; }
    .supplier-page .table tbody tr:last-child td { border-bottom:0; }
    .supplier-page .table tbody tr:hover { background:#fbfdfb; }
    .supplier-page .supplier-avatar { width:40px; height:40px; border-radius:13px; display:grid; place-items:center; flex:0 0 40px; background:#eaf4ed; color:#176b4b; font-family:'Manrope','DM Sans',sans-serif; font-size:14px; font-weight:800; }
    .supplier-page .supplier-name { color:#26332b; font-size:13px; font-weight:800; }
    .supplier-page .supplier-id { color:#8a958e; font-size:10px; font-weight:700; letter-spacing:.04em; margin-top:3px; }
    .supplier-page .supplier-contact { display:flex; flex-direction:column; gap:4px; font-size:12px; }
    .supplier-page .supplier-contact a { color:#45564b; text-decoration:none; }
    .supplier-page .supplier-contact a:hover { color:#176b4b; text-decoration:underline; }
    .supplier-page .supplier-contact .muted { color:#9aa49d; }
    .supplier-page .supplier-location { display:inline-flex; align-items:center; gap:6px; color:#56645b; font-size:12px; }
    .supplier-page .supplier-location i { color:#7f9a89; }
    .supplier-page .supplier-action { width:34px; height:34px; display:inline-grid; place-items:center; border:1px solid #e5ebe6; border-radius:10px; background:#fff; color:#637067; text-decoration:none; }
    .supplier-page .supplier-action:hover { border-color:#b8d5c3; background:#f2f8f4; color:#176b4b; }
    .supplier-page .supplier-action.danger:hover { border-color:#f0c8c5; background:#fff5f4; color:#b43b36; }
    .supplier-page .supplier-empty { padding:48px 20px; text-align:center; }
    .supplier-page .supplier-empty-icon { width:58px; height:58px; display:grid; place-items:center; margin:0 auto 14px; border-radius:18px; background:#edf5ef; color:#176b4b; font-size:24px; }
    .supplier-page .supplier-pagination { padding:14px 18px; border-top:1px solid var(--supplier-line); }
    .supplier-page .supplier-pagination .pagination { margin:0; }
    .supplier-page .supplier-pagination .page-link { color:#176b4b; border-color:#e6ece7; font-size:12px; }
    .supplier-page .supplier-pagination .page-item.active .page-link { color:#fff; background:#176b4b; border-color:#176b4b; }
    .supplier-page .supplier-pagination .pagination+p,
    .supplier-page .supplier-pagination p.text-muted { display:none!important; }
    @media(max-width:767.98px) {
        .supplier-page .supplier-heading { align-items:flex-start; flex-direction:column; }
        .supplier-page .supplier-title { font-size:23px; }
        .supplier-page .supplier-actions { width:100%; }
        .supplier-page .supplier-actions > * { flex:1 1 auto; }
        .supplier-page .supplier-table-head { align-items:flex-start; flex-direction:column; }
    }
</style>
@endpush

@section('content')
<div class="supplier-page">
    <div class="supplier-heading">
        <div>
            <div class="supplier-kicker"><i class="bi bi-diagram-3 me-1"></i> Procurement & supply network</div>
            <h1 class="supplier-title">Suppliers</h1>
            <p class="supplier-subtitle">Manage vendor contacts and keep purchasing relationships organized in one place.</p>
        </div>
        <div class="supplier-actions">
            @include('admin.partials.export-actions', ['exportType' => 'suppliers'])
            <a href="{{ route('supplier.create') }}" class="supplier-add"><i class="bi bi-plus-lg"></i> Add supplier</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="supplier-stat">
                <div><div class="supplier-stat-label">Total suppliers</div><div class="supplier-stat-value">{{ number_format($totalSupplier) }}</div></div>
                <span class="supplier-stat-icon"><i class="bi bi-buildings"></i></span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="supplier-stat">
                <div><div class="supplier-stat-label">Email contacts</div><div class="supplier-stat-value">{{ number_format($suppliersWithEmail) }}</div></div>
                <span class="supplier-stat-icon"><i class="bi bi-envelope"></i></span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="supplier-stat">
                <div><div class="supplier-stat-label">Phone contacts</div><div class="supplier-stat-value">{{ number_format($suppliersWithPhone) }}</div></div>
                <span class="supplier-stat-icon"><i class="bi bi-telephone"></i></span>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="supplier-stat">
                <div><div class="supplier-stat-label">Locations covered</div><div class="supplier-stat-value">{{ number_format($supplierLocations) }}</div></div>
                <span class="supplier-stat-icon"><i class="bi bi-geo-alt"></i></span>
            </div>
        </div>
    </div>

    @include('admin.message.message')

    @if(session('error'))
        <div class="alert alert-warning border-0 rounded-3 d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="supplier-filter-card mb-3">
        <form method="GET" action="{{ route('supplier.list') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="supplier-filter-label" for="supplierSearch">Search suppliers</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-color:#dfe6e1;border-radius:9px 0 0 9px"><i class="bi bi-search text-muted"></i></span>
                    <input id="supplierSearch" class="form-control border-start-0" style="border-radius:0 9px 9px 0" type="search" name="search" value="{{ request('search') }}" placeholder="Name, email, phone or location...">
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <label class="supplier-filter-label" for="supplierSort">Sort by</label>
                <select id="supplierSort" name="sort_by" class="form-select">
                    <option value="supplier_id" @selected(request('sort_by', 'supplier_id') === 'supplier_id')>Recently added</option>
                    <option value="name" @selected(request('sort_by') === 'name')>Supplier name</option>
                    <option value="address" @selected(request('sort_by') === 'address')>Location</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="supplier-filter-label" for="supplierDirection">Order</label>
                <select id="supplierDirection" name="sort_dir" class="form-select">
                    <option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Newest first</option>
                    <option value="asc" @selected(request('sort_dir') === 'asc')>Oldest first</option>
                </select>
            </div>
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-cc flex-grow-1"><i class="bi bi-funnel me-1"></i> Apply filters</button>
                <a href="{{ route('supplier.list') }}" class="btn btn-cc-light" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <section class="supplier-table-card">
        <div class="supplier-table-head">
            <div>
                <h2 class="supplier-table-title">Supplier directory</h2>
                <div class="small text-muted mt-1">Contact details for your product and purchase partners.</div>
            </div>
            <span class="supplier-count">{{ number_format($supp_key->total()) }} {{ $supp_key->total() === 1 ? 'record' : 'records' }}</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">SUPPLIER</th>
                        <th>CONTACT INFORMATION</th>
                        <th>LOCATION</th>
                        <th class="text-end pe-4">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supp_key as $supp)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="supplier-avatar">{{ strtoupper(substr((string) ($supp->name ?: 'S'), 0, 1)) }}</span>
                                    <div>
                                        <div class="supplier-name">{{ $supp->name ?: 'Unnamed supplier' }}</div>
                                        <div class="supplier-id">SUP-{{ str_pad((string) $supp->supplier_id, 4, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="supplier-contact">
                                    @if($supp->phone)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $supp->phone) }}"><i class="bi bi-telephone me-2 text-muted"></i>{{ $supp->phone }}</a>
                                    @else
                                        <span class="muted"><i class="bi bi-telephone me-2"></i>No phone number</span>
                                    @endif
                                    @if($supp->email)
                                        <a href="mailto:{{ $supp->email }}"><i class="bi bi-envelope me-2 text-muted"></i>{{ $supp->email }}</a>
                                    @else
                                        <span class="muted"><i class="bi bi-envelope me-2"></i>No email address</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="supplier-location"><i class="bi bi-geo-alt-fill"></i>{{ $supp->address ?: 'Location not provided' }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <a href="{{ route('supplier.edit', $supp->supplier_id) }}" class="supplier-action" title="Edit supplier" aria-label="Edit {{ $supp->name }}"><i class="bi bi-pencil-square"></i></a>
                                    <form action="{{ route('supplier.delete', $supp->supplier_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier? This is only possible when the supplier is not linked to products or purchase history.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="supplier-action danger" title="Delete supplier" aria-label="Delete {{ $supp->name }}"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="supplier-empty">
                                    <span class="supplier-empty-icon"><i class="bi bi-buildings"></i></span>
                                    @if(request()->filled('search'))
                                        <h3 class="h6 fw-bold mb-2">No suppliers match your search</h3>
                                        <p class="text-muted small mb-3">Try another name, email, phone number or location.</p>
                                        <a href="{{ route('supplier.list') }}" class="btn btn-cc-light btn-sm">Clear search</a>
                                    @else
                                        <h3 class="h6 fw-bold mb-2">No suppliers added yet</h3>
                                        <p class="text-muted small mb-3">Add your first supplier to organize purchasing contacts.</p>
                                        <a href="{{ route('supplier.create') }}" class="btn btn-cc btn-sm"><i class="bi bi-plus-lg me-1"></i>Add first supplier</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($supp_key->hasPages())
            <div class="supplier-pagination d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="small text-muted">Showing {{ $supp_key->firstItem() ?? 0 }}–{{ $supp_key->lastItem() ?? 0 }} of {{ number_format($supp_key->total()) }} suppliers</div>
                {{ $supp_key->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
