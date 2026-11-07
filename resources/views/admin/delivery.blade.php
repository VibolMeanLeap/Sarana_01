@extends('layout.admin')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Deliveries</h5>
        <a href="{{ route('delivery.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> New Delivery
        </a>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-warning">{{ session('error') }}</div>
        @endif

        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All statuses</option>
                    <option value="pending" @selected(request('status') == 'pending')>Pending</option>
                    <option value="out_for_delivery" @selected(request('status') == 'out_for_delivery')>Out for delivery</option>
                    <option value="delivered" @selected(request('status') == 'delivered')>Delivered</option>
                    <option value="cancelled" @selected(request('status') == 'cancelled')>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary btn-sm">Filter</button>
            </div>
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