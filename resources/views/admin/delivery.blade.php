@extends('layout.admin')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><h5 class="mb-0">Deliveries</h5><div class="small text-muted">Search and export the current delivery records.</div></div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @include('admin.partials.export-actions', ['exportType' => 'deliveries'])
            <a href="{{ route('delivery.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> New Delivery
            </a>
        </div>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-warning">{{ session('error') }}</div>
        @endif

        <form method="GET" action="{{ route('delivery.index') }}" class="row g-2 mb-3 align-items-end">
            <div class="col-12 col-md-3"><label class="form-label small fw-semibold">Search</label><input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Order, customer or address"></div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    <option value="pending" @selected(request('status') == 'pending')>Pending</option>
                    <option value="out_for_delivery" @selected(request('status') == 'out_for_delivery')>Out for delivery</option>
                    <option value="delivered" @selected(request('status') == 'delivered')>Delivered</option>
                    <option value="cancelled" @selected(request('status') == 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div class="col-auto"><label class="form-label small fw-semibold">From</label><input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm"></div>
            <div class="col-auto"><label class="form-label small fw-semibold">To</label><input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm"></div>
            <div class="col-auto"><label class="form-label small fw-semibold">Sort</label><select name="sort_dir" class="form-select form-select-sm"><option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Newest</option><option value="asc" @selected(request('sort_dir') === 'asc')>Oldest</option></select></div>
            <input type="hidden" name="sort_by" value="order_date">
            <div class="col-auto"><button class="btn btn-outline-secondary btn-sm">Filter</button><a href="{{ route('delivery.index') }}" class="btn btn-outline-dark btn-sm">Reset</a></div>
        </form>

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Address</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $d)
                    <tr>
                        <td>{{ $d->delivery_id }}</td>
                        <td>#{{ $d->order_id }}</td>
                        <td>{{ $d->order->customer->name ?? '-' }}</td>
                        <td>{{ $d->address }}</td>
                        <td>{{ number_format($d->cost, 2) }}</td>
                        <td>
                            @php
                                $badge = match($d->status) {
                                    'delivered' => 'success',
                                    'out_for_delivery' => 'info',
                                    'cancelled' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $badge }}">{{ ucfirst(str_replace('_', ' ', $d->status)) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('delivery.edit', $d->delivery_id) }}" class="btn btn-sm btn-outline-primary">Update</a>
                            <form action="{{ route('delivery.destroy', $d->delivery_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this delivery?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No deliveries yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $deliveries->links() }}
    </div>
</div>
@endsection