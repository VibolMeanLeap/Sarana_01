@extends('layout.admin')
@section('page_title', 'Product sales')
@section('topbar_title', 'Product sales')

@section('content')
<style>
    .ps-hero{background:linear-gradient(120deg,#123f2e 0%,#176b4b 62%,#2d8b62 100%);color:#fff;border-radius:20px;padding:23px 25px;position:relative;overflow:hidden;box-shadow:0 12px 28px rgba(23,107,75,.14)}
    .ps-hero:after{content:"";position:absolute;width:230px;height:230px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-62px;top:-112px;box-shadow:0 0 0 28px rgba(255,255,255,.035),0 0 0 58px rgba(255,255,255,.025)}
    .ps-hero-content{position:relative;z-index:1}
    .ps-kpi{height:100%;border:1px solid #e8eee9;border-radius:16px;background:#fff;box-shadow:0 5px 18px rgba(24,65,42,.035)}
    .ps-kpi-icon{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;font-size:17px;background:#edf6ef;color:#176b4b}
    .ps-section-card{height:100%;border:1px solid #e8eee9;border-radius:17px;background:#fff;box-shadow:0 5px 18px rgba(24,65,42,.035)}
    .ps-section-head{padding:17px 19px;border-bottom:1px solid #edf1ee}
    .ps-section-body{padding:17px 19px}
    .ps-rank{width:29px;height:29px;border-radius:9px;display:grid;place-items:center;background:#eef5f0;color:#176b4b;font-size:12px;font-weight:850;flex:0 0 auto}
    .ps-product-thumb{width:43px;height:43px;border-radius:11px;object-fit:cover;background:#f1f5f2;border:1px solid #edf1ee}
    .ps-table thead th{font-size:10px;letter-spacing:.07em;text-transform:uppercase;color:#849088;font-weight:850;border-bottom:1px solid #e9efeb;white-space:nowrap}
    .ps-table tbody td{padding-top:12px;padding-bottom:12px;border-color:#f0f3f1;vertical-align:middle}
    .ps-table tfoot th{padding:14px 8px;background:#f4f8f5;border-top:2px solid #dfe9e2;color:#244333;font-size:12px;white-space:nowrap}
    .ps-filter-shell{padding:14px;border:1px solid #e5ede7;border-radius:18px;background:#fff;box-shadow:0 5px 18px rgba(24,65,42,.035)}
    .ps-filter-shell-title{display:flex;align-items:center;gap:9px;font-weight:850;color:#263b30;margin:0 0 12px;font-size:13px}
    .ps-filter-label{font-size:10px;font-weight:850;color:#77827b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px}
    .ps-filter-select,.ps-filter-input{min-height:39px;border-color:#e1e9e3;border-radius:10px;font-size:12px}
    .ps-muted{color:#7b8980}
    @media(max-width:767px){.ps-hero{padding:19px}.ps-section-head,.ps-section-body{padding:14px}}
</style>

<div class="page-heading mb-3">
    <div>
        <div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Sales intelligence</div>
        <h1>Product sales</h1>
        <p>See what customers buy most, how many units sell, and which products contribute the most value.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.product-sales.csv', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
        <a href="{{ route('admin.product-sales.pdf', request()->query()) }}" class="btn btn-outline-danger" target="_blank" rel="noopener">
            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </a>
    </div>
</div>

<form method="GET" action="{{ route('admin.product-sales.index') }}" class="mb-4">
    <div class="ps-filter-shell">
        <div class="ps-filter-shell-title"><i class="bi bi-funnel-fill text-success"></i>Filter product sales</div>
        @include('admin.partials.date-filter-panel', [
            'dateFilter' => $filters['date_filter'],
            'dateFromField' => 'start_date',
            'dateToField' => 'end_date',
            'dateFilterTitle' => 'Sales date range',
            'dateFilterHint' => 'Quickly compare today, this week, this month, or all paid product sales. Dates use the order date.',
        ])

        <section class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="fw-bold"><i class="bi bi-funnel me-2 text-success"></i>Product filters</div>
                <div class="small text-muted">Combine filters to narrow the sales results.</div>
            </div>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="ps-filter-label" for="psSource">Sales source</label>
                    <select id="psSource" name="source" class="form-select ps-filter-select">
                        <option value="all" @selected($filters['source'] === 'all')>All sources</option>
                        <option value="pos" @selected($filters['source'] === 'pos')>POS</option>
                        <option value="website" @selected($filters['source'] === 'website')>Website</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label class="ps-filter-label" for="psCategory">Category</label>
                    <select id="psCategory" name="category_id" class="form-select ps-filter-select">
                        <option value="">All categories</option>
                        @foreach($categoryOptions as $category)
                            <option value="{{ $category->category_id }}" @selected((string)$filters['category_id'] === (string)$category->category_id)>{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label class="ps-filter-label" for="psStaff">Created by staff</label>
                    <select id="psStaff" name="staff_id" class="form-select ps-filter-select">
                        <option value="">All staff & website</option>
                        <option value="website" @selected($filters['staff_id'] === 'website')>Website / no staff</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->user_id }}" @selected((string)$filters['staff_id'] === (string)$member->user_id)>
                                {{ $member->name }} · {{ ucfirst($member->role_name) }}{{ strtolower((string)$member->status) !== 'active' ? ' (inactive)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="ps-filter-label" for="psSearch">Product name</label>
                    <input id="psSearch" name="search" type="search" value="{{ $filters['search'] }}" class="form-control ps-filter-input" placeholder="Search product..." maxlength="100">
                </div>
                <div class="col-12 col-xl-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1"><i class="bi bi-filter me-1"></i>Apply</button>
                    <a href="{{ route('admin.product-sales.index') }}" class="btn btn-outline-secondary" title="Reset all filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </div>
            <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>Sales analytics include paid, non-cancelled orders only. Unpaid orders and cancelled orders are excluded.</div>
        </div>
        </section>
    </div>
</form>

<div class="ps-hero mb-4">
    <div class="ps-hero-content d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.12em;color:#c9e6d4">Best-selling product</div>
            @if($bestSeller)
                <h2 class="h3 fw-bold mb-1">{{ $bestSeller->product_name }}</h2>
                <div class="small" style="color:#d7eadf">{{ $bestSeller->category_name }} · Product #{{ $bestSeller->product_id }}</div>
            @else
                <h2 class="h3 fw-bold mb-1">No product sales yet</h2>
                <div class="small" style="color:#d7eadf">Try another date range or remove some filters.</div>
            @endif
        </div>
        @if($bestSeller)
            <div class="text-md-end">
                <div class="display-6 fw-bold lh-1">{{ number_format((int)$bestSeller->units_sold) }}</div>
                <div class="small mt-2" style="color:#d7eadf">units sold in this selection</div>
            </div>
        @else
            <i class="bi bi-trophy" style="font-size:54px;color:rgba(255,255,255,.45)"></i>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3"><div class="ps-kpi p-3"><div class="d-flex justify-content-between align-items-center"><span class="small ps-muted fw-semibold">Total units sold</span><span class="ps-kpi-icon"><i class="bi bi-box-seam"></i></span></div><div class="fs-2 fw-bold mt-2">{{ number_format((int)$summary->units_sold) }}</div><div class="small ps-muted">Across the selected products</div></div></div>
    <div class="col-12 col-sm-6 col-xl-3"><div class="ps-kpi p-3"><div class="d-flex justify-content-between align-items-center"><span class="small ps-muted fw-semibold">Products sold</span><span class="ps-kpi-icon"><i class="bi bi-tags"></i></span></div><div class="fs-2 fw-bold mt-2">{{ number_format((int)$summary->products_sold) }}</div><div class="small ps-muted">Unique product types</div></div></div>
    <div class="col-12 col-sm-6 col-xl-3"><div class="ps-kpi p-3"><div class="d-flex justify-content-between align-items-center"><span class="small ps-muted fw-semibold">Orders with products</span><span class="ps-kpi-icon"><i class="bi bi-receipt"></i></span></div><div class="fs-2 fw-bold mt-2">{{ number_format((int)$summary->orders_count) }}</div><div class="small ps-muted">Paid orders in this selection</div></div></div>
    <div class="col-12 col-sm-6 col-xl-3"><div class="ps-kpi p-3"><div class="d-flex justify-content-between align-items-center"><span class="small ps-muted fw-semibold">Item sales subtotal</span><span class="ps-kpi-icon"><i class="bi bi-currency-dollar"></i></span></div><div class="fs-2 fw-bold mt-2">${{ number_format((float)$summary->item_sales_total, 2) }}</div><div class="small ps-muted">Before item-level discounts</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6"><div class="ps-kpi p-3"><div class="small ps-muted fw-semibold">Estimated cost of goods sold</div><div class="h3 fw-bold mt-2 mb-1">${{ number_format((float)$summary->cogs_total, 2) }}</div><div class="small ps-muted">Based on saved item unit cost at sale time.</div></div></div>
    <div class="col-12 col-md-6"><div class="ps-kpi p-3"><div class="small ps-muted fw-semibold">Estimated item gross margin</div><div class="h3 fw-bold mt-2 mb-1 {{ $summary->gross_margin >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format((float)$summary->gross_margin, 2) }}</div><div class="small ps-muted">Item sales subtotal minus product discounts and estimated item cost.</div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-7">
        <section class="ps-section-card">
            <div class="ps-section-head d-flex flex-wrap align-items-center justify-content-between gap-2"><div><h2 class="h5 fw-bold mb-1">Top-selling products</h2><div class="small ps-muted">Ranked by units sold</div></div><span class="badge rounded-pill text-bg-light">Top 5</span></div>
            <div class="ps-section-body">
                @forelse($topProducts as $index => $product)
                    <div class="d-flex align-items-center gap-3 {{ !$loop->last ? 'mb-3' : '' }}">
                        <span class="ps-rank">{{ $index + 1 }}</span>
                        @if($product->image && file_exists(public_path('images/'.$product->image)))
                            <img class="ps-product-thumb" src="{{ asset('images/'.$product->image) }}" alt="{{ $product->product_name }}">
                        @else
                            <span class="ps-product-thumb d-grid place-items-center"><i class="bi bi-cup-hot text-success"></i></span>
                        @endif
                        <div class="flex-grow-1 min-w-0"><div class="fw-bold text-truncate">{{ $product->product_name }}</div><div class="small ps-muted">{{ $product->category_name }}</div></div>
                        <div class="text-end"><div class="fw-bold">{{ number_format((int)$product->units_sold) }} units</div><div class="small ps-muted">${{ number_format((float)$product->item_sales_total,2) }}</div></div>
                    </div>
                @empty
                    <div class="text-center py-5 ps-muted"><i class="bi bi-bar-chart-line fs-2 d-block mb-2"></i>No product sales match these filters.</div>
                @endforelse
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-5">
        <section class="ps-section-card">
            <div class="ps-section-head"><h2 class="h5 fw-bold mb-1">Sales by category</h2><div class="small ps-muted">Share of units sold in the selected period</div></div>
            <div class="ps-section-body">
                @forelse($categories as $category)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2"><span class="fw-semibold text-truncate">{{ $category->category_name }}</span><span class="small fw-bold text-nowrap">{{ number_format((int)$category->units_sold) }} units</span></div>
                        <div class="rounded-pill overflow-hidden" style="height:9px;background:#edf2ee"><div style="width:{{ $category->bar_width }}%;height:9px;background:linear-gradient(90deg,#176b4b,#70b58b);border-radius:999px"></div></div>
                        <div class="small ps-muted mt-1">${{ number_format((float)$category->item_sales_total,2) }} item subtotal</div>
                    </div>
                @empty
                    <div class="text-center py-5 ps-muted">No category sales found.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>

<section class="ps-section-card mb-4">
    <div class="ps-section-head d-flex flex-wrap align-items-center justify-content-between gap-2"><div><h2 class="h5 fw-bold mb-1">Daily units sold</h2><div class="small ps-muted">Most recent 14 selling days in the selected period</div></div><span class="small ps-muted">Units</span></div>
    <div class="ps-section-body">
        @forelse($dailySales as $day)
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="small ps-muted text-nowrap" style="width:88px">{{ \Illuminate\Support\Carbon::parse($day->sale_day)->format('d M Y') }}</div>
                <div class="flex-grow-1"><div class="rounded-pill overflow-hidden" style="height:11px;background:#edf2ee"><div style="width:{{ $day->bar_width }}%;height:11px;background:linear-gradient(90deg,#176b4b,#70b58b);border-radius:999px"></div></div></div>
                <div class="small fw-bold text-end text-nowrap" style="width:75px">{{ number_format((int)$day->units_sold) }}</div>
            </div>
        @empty
            <div class="text-center text-muted py-4">No daily sales data for this period.</div>
        @endforelse
    </div>
</section>

<section class="ps-section-card">
    <div class="ps-section-head d-flex flex-wrap align-items-center justify-content-between gap-2"><div><h2 class="h5 fw-bold mb-1">Product sales breakdown</h2><div class="small ps-muted">Every matching product, ranked by quantity sold</div></div><span class="badge rounded-pill text-bg-light">{{ number_format($products->total()) }} products</span></div>
    <div class="table-responsive">
        <table class="table ps-table mb-0">
            <thead><tr><th class="ps-3">Product</th><th>Category</th><th class="text-end">Units sold</th><th class="text-end" title="Distinct orders containing this product">Orders</th><th class="text-end" title="Item sales subtotal divided by units sold; a quantity-weighted average of recorded unit prices before item discounts">Avg. selling price</th><th class="text-end">Item sales</th><th class="text-end" title="Total item-level discount saved on this product across the selected period">Discount</th><th class="text-end">Est. margin</th></tr></thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td class="ps-3"><div class="d-flex align-items-center gap-2">
                            @if($product->image && file_exists(public_path('images/'.$product->image)))<img class="ps-product-thumb" src="{{ asset('images/'.$product->image) }}" alt="">
                            @else<span class="ps-product-thumb d-grid place-items-center"><i class="bi bi-cup-hot text-success"></i></span>@endif
                            <div><div class="fw-bold">{{ $product->product_name }}</div><div class="small ps-muted">#{{ $product->product_id }}</div></div>
                        </div></td>
                        <td><span class="small">{{ $product->category_name }}</span></td>
                        <td class="text-end fw-bold">{{ number_format((int)$product->units_sold) }}</td>
                        <td class="text-end">{{ number_format((int)$product->orders_count) }}</td>
                        <td class="text-end">${{ number_format((float)$product->average_selling_price, 2, '.', ',') }}</td>
                        <td class="text-end">${{ number_format((float)$product->item_sales_total,2) }}</td>
                        <td class="text-end">${{ number_format((float)$product->item_discount_total,2) }}</td>
                        <td class="text-end {{ $product->gross_margin >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format((float)$product->gross_margin,2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No product sales match the selected filters.</td></tr>
                @endforelse
            </tbody>
            @if($products->total() > 0)
                <tfoot>
                    <tr>
                        <th class="ps-3" colspan="2">TOTAL</th>
                        <th class="text-end">{{ number_format((int)$summary->units_sold) }}</th>
                        <th class="text-end" title="Distinct orders across the full filtered report">{{ number_format((int)$summary->orders_count) }}</th>
                        <th class="text-end">${{ number_format((float)$summary->average_selling_price, 2, '.', ',') }}</th>
                        <th class="text-end">${{ number_format((float)$summary->item_sales_total, 2) }}</th>
                        <th class="text-end">${{ number_format((float)$summary->item_discount_total, 2) }}</th>
                        <th class="text-end {{ $summary->gross_margin >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format((float)$summary->gross_margin, 2) }}</th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if($products->hasPages())<div class="p-3 border-top">{{ $products->links() }}</div>@endif
</section>

<div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>Average selling price is the quantity-weighted average of recorded unit prices before item discounts; multiply it by units sold to approximate item sales (small rounding differences may occur). Item sales is the exact sum of quantity × recorded item price. Discount is the total saved item-level discount for each product. Estimated margin subtracts item discounts and estimated item cost. Footer totals cover every product matching the filters, including products on other pages; Orders is a distinct count, not a sum of product order counts. Historic order-wide discounts that were not assigned to individual products are not allocated to product rows.</div>
@endsection
