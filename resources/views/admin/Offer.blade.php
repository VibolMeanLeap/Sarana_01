@extends('layout.admin')

@section('content')
<div class="container-fluid py-4 offer-admin-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="small fw-bold text-uppercase text-success mb-1" style="letter-spacing:.12em">Marketing studio</div>
            <h1 class="h3 fw-bold mb-1">Offers & promotions</h1>
            <p class="text-muted mb-0">Create a discount once. Your live promotion appears automatically in the website shop.</p>
        </div>
        <a href="{{ route('offer.create') }}" class="btn btn-success rounded-pill px-4 py-2 shadow-sm">
            <i class="bi bi-stars me-2"></i>Create new offer
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between"><span class="small text-muted fw-semibold">Total offers</span><span class="rounded-circle bg-light text-success d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px"><i class="bi bi-tags fs-5"></i></span></div>
                <div class="fs-2 fw-bold mt-2">{{ $offers->total() }}</div>
                <div class="small text-muted">All scheduled promotions</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between"><span class="small text-muted fw-semibold">On this page · Live</span><span class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px"><i class="bi bi-lightning-charge-fill fs-5"></i></span></div>
                <div class="fs-2 fw-bold mt-2">{{ $offers->getCollection()->where('display_status', 'Active')->count() }}</div>
                <div class="small text-muted">Visible on the public shop now</div>
            </div></div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between"><span class="small text-muted fw-semibold">On this page · Upcoming</span><span class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px"><i class="bi bi-calendar-event fs-5"></i></span></div>
                <div class="fs-2 fw-bold mt-2">{{ $offers->getCollection()->where('display_status', 'Upcoming')->count() }}</div>
                <div class="small text-muted">Scheduled to appear later</div>
            </div></div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="GET" action="{{ route('offer.list') }}" class="row g-2 align-items-center flex-grow-1">
            <div class="col-12 col-md-3"><input class="form-control form-control-sm" name="search" value="{{ request('search') }}" placeholder="Search product name..."></div>
            <div class="col-6 col-md-2"><select name="category_id" class="form-select form-select-sm"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" @selected(request('category_id') == $category->category_id)>{{ $category->category_name }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><select name="product_id" class="form-select form-select-sm"><option value="">All products</option>@foreach($products as $product)<option value="{{ $product->product_id }}" @selected(request('product_id') == $product->product_id)>{{ $product->product_name }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><select name="status" class="form-select form-select-sm"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="upcoming" @selected(request('status') === 'upcoming')>Upcoming</option><option value="expired" @selected(request('status') === 'expired')>Expired</option></select></div>
            <div class="col-6 col-md-3 d-flex gap-2"><input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" aria-label="Offer start from"><input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" aria-label="Offer end to"></div>
            <div class="col-6 col-md-2"><select name="sort_by" class="form-select form-select-sm"><option value="start_date" @selected(request('sort_by', 'start_date') === 'start_date')>Start date</option><option value="end_date" @selected(request('sort_by') === 'end_date')>End date</option><option value="discount" @selected(request('sort_by') === 'discount')>Discount</option></select></div>
            <div class="col-6 col-md-2"><select name="sort_dir" class="form-select form-select-sm"><option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Descending</option><option value="asc" @selected(request('sort_dir') === 'asc')>Ascending</option></select></div>
            <div class="col-auto"><button type="submit" class="btn btn-success btn-sm">Filter</button><a href="{{ route('offer.list') }}" class="btn btn-outline-secondary btn-sm">Reset</a></div>
        </form>
        @include('admin.partials.export-actions', ['exportType' => 'offers'])
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 px-3 px-md-4 pt-4 pb-2">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><h2 class="h5 fw-bold mb-1">Promotion calendar</h2><p class="small text-muted mb-0">The banner is shown only between its start and end dates.</p></div>
                <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $offers->total() }} records</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr><th class="ps-4">Offer</th><th>Discount</th><th>New price</th><th>Schedule</th><th>Status</th><th class="text-end pe-4">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($offers as $offer)
                    @php
                        $statusClass = $offer->display_status === 'Active' ? 'success' : ($offer->display_status === 'Upcoming' ? 'warning' : 'secondary');
                        $product = $offer->product;
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-light d-flex align-items-center justify-content-center overflow-hidden" style="width:54px;height:54px;flex:0 0 54px">
                                    @if($product?->image && file_exists(public_path('images/'.$product->image)))
                                        <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->product_name }}" style="width:100%;height:100%;object-fit:cover">
                                    @else
                                        <i class="bi bi-cup-hot text-success fs-4"></i>
                                    @endif
                                </div>
                                <div><div class="fw-bold">{{ $product->product_name ?? 'Product removed' }}</div><div class="small text-muted">Offer #{{ $offer->offer_id }}</div></div>
                            </div>
                        </td>
                        <td><span class="badge rounded-pill text-bg-danger px-3 py-2">{{ rtrim(rtrim(number_format((float)$offer->discount, 2), '0'), '.') }}% OFF</span></td>
                        <td><div class="fw-bold text-success">${{ number_format((float)$offer->new_price, 2) }}</div><div class="small text-muted">Regular: ${{ number_format((float)($product->price ?? 0), 2) }}</div></td>
                        <td><div class="small fw-semibold">{{ \Carbon\Carbon::parse($offer->start_date)->format('M d, Y') }}</div><div class="small text-muted">to {{ \Carbon\Carbon::parse($offer->end_date)->format('M d, Y') }}</div></td>
                        <td><span class="badge text-bg-{{ $statusClass }} rounded-pill px-3 py-2">{{ $offer->display_status }}</span></td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-2">
                                <a href="{{ route('offer.edit', $offer->offer_id) }}" class="btn btn-sm btn-outline-success rounded-pill px-3"><i class="bi bi-pencil me-1"></i>Edit</a>
                                <form action="{{ route('offer.destroy', $offer->offer_id) }}" method="POST" onsubmit="return confirm('Delete this offer? Its banner will disappear from the shop.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5"><div class="text-success mb-2"><i class="bi bi-megaphone fs-1"></i></div><h3 class="h6 fw-bold">No offers yet</h3><p class="small text-muted mb-3">Create your first promotion and it will appear in the website shop during its scheduled dates.</p><a href="{{ route('offer.create') }}" class="btn btn-success rounded-pill px-4">Create first offer</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($offers->hasPages())<div class="card-footer bg-white border-0 py-3">{{ $offers->links() }}</div>@endif
    </div>
</div>
@endsection
