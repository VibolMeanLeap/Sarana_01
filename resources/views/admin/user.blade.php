@extends('layout.admin')

@section('page_title', 'Customer accounts')
@section('topbar_title', 'Customer accounts')

@section('content')
<div class="page-heading">
    <div>
        <div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">People & relationships</div>
        <h1>Customer accounts</h1>
        <p>Manage website customer sign-ins and control whether a customer can access their online account. Walk-in customer records remain available to existing orders and reports.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="small text-muted fw-semibold text-uppercase">Registered accounts</div><div class="display-6 fw-bold mt-1">{{ number_format($totalCustomerAccounts) }}</div></div><span class="rounded-4 p-3" style="background:#eaf4ed;color:#176b4b"><i class="bi bi-people-fill fs-4"></i></span></div></div></div>
    <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="small text-muted fw-semibold text-uppercase">Active accounts</div><div class="display-6 fw-bold mt-1">{{ number_format($activeCustomerAccounts) }}</div></div><span class="rounded-4 p-3" style="background:#eaf4ed;color:#176b4b"><i class="bi bi-person-check-fill fs-4"></i></span></div></div></div>
    <div class="col-12 col-md-4"><div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="small text-muted fw-semibold text-uppercase">Inactive accounts</div><div class="display-6 fw-bold mt-1">{{ number_format($inactiveCustomerAccounts) }}</div></div><span class="rounded-4 p-3" style="background:#fff3e3;color:#98641b"><i class="bi bi-person-x-fill fs-4"></i></span></div></div></div>
</div>

<div class="d-flex justify-content-end mb-3">
    @include('admin.partials.export-actions', ['exportType' => 'customer-accounts'])
</div>
<div class="card overflow-hidden">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div><h2 class="h5 fw-bold mb-1">Website customer accounts</h2><p class="small text-muted mb-0">Only users assigned the Customer role appear here. Administrator and Cashier accounts are managed in Settings.</p></div>
        <span class="badge rounded-pill text-bg-light">{{ $customerAccounts->total() }} accounts</span>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" action="{{ route('auth.list') }}" class="row g-2 align-items-end">
            <div class="col-12 col-lg-4"><label for="customerSearch" class="form-label small fw-semibold">Search customers</label><input id="customerSearch" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, phone, email or username"></div>
            <div class="col-6 col-lg-2"><label for="customerStatus" class="form-label small fw-semibold">Status</label><select id="customerStatus" name="status" class="form-select"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">Registered from</label><input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control"></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">Registered to</label><input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control"></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">Sort</label><input type="hidden" name="sort_by" value="created_at"><select name="sort_dir" class="form-select"><option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Newest first</option><option value="asc" @selected(request('sort_dir') === 'asc')>Oldest first</option></select></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-cc" type="submit"><i class="bi bi-search me-1"></i>Filter</button><a href="{{ route('auth.list') }}" class="btn btn-cc-light" title="Clear filters">Reset</a></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th class="ps-4">CUSTOMER</th><th>CONTACT</th><th>ORDERS</th><th>REGISTERED</th><th>STATUS</th><th class="text-end pe-4">ACTIONS</th></tr></thead>
            <tbody>
            @forelse($customerAccounts as $account)
                <tr>
                    <td class="ps-4"><div class="d-flex align-items-center gap-2"><span class="d-inline-grid rounded-circle align-items-center justify-content-center fw-bold" style="width:40px;height:40px;background:#eaf4ed;color:#176b4b">{{ strtoupper(substr($account->name ?: 'C', 0, 1)) }}</span><div><div class="fw-bold">{{ $account->name }}</div><div class="small text-muted">Customer #{{ $account->customer?->customer_id ?? $account->user_id }}</div></div></div></td>
                    <td><div>{{ $account->phone ?: '—' }}</div><div class="small text-muted">{{ $account->email ?: 'No email' }}</div></td>
                    <td><span class="badge rounded-pill text-bg-light">{{ $account->customer?->orders?->count() ?? 0 }}</span></td>
                    <td class="small text-muted">{{ $account->created_at?->format('d M Y') ?? '—' }}</td>
                    <td><span class="badge rounded-pill {{ strtolower((string)$account->status) === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($account->status ?: 'inactive') }}</span></td>
                    <td class="text-end pe-4">
                        <div class="d-inline-flex flex-wrap justify-content-end align-items-center gap-2">
                            <a href="{{ route('customer.accounts.edit', $account->user_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                            <form method="POST" action="{{ route('customer.accounts.status', $account->user_id) }}" class="d-inline-flex align-items-center gap-2">
                                @csrf @method('PATCH')
                                <select name="status" class="form-select form-select-sm" aria-label="Account status for {{ $account->name }}" required style="min-width:115px">
                                    <option value="active" @selected($account->status === 'active')>Active</option>
                                    <option value="inactive" @selected($account->status !== 'active')>Inactive</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Save account status"><i class="bi bi-check2"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-5"><i class="bi bi-person-lines-fill fs-1 text-muted"></i><h3 class="h6 fw-bold mt-3">No customer accounts found</h3><p class="text-muted small mb-0">Customers who register on the website will appear here.</p></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2"><span class="small text-muted">Showing {{ $customerAccounts->firstItem() ?? 0 }}–{{ $customerAccounts->lastItem() ?? 0 }} of {{ $customerAccounts->total() }}</span>{{ $customerAccounts->links() }}</div>
</div>
@endsection
