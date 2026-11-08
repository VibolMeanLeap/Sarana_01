@extends('layout.admin')
@section('page_title', 'Deliveries')
@section('topbar_title', 'Delivery operations')
@section('content')
<div class="page-heading">
    <div><div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Order fulfillment</div><h1>Deliveries</h1><p>Assign delivery orders, monitor dispatch progress and preserve fulfillment history.</p></div>
    <a href="{{ route('delivery.create') }}" class="btn btn-cc"><i class="bi bi-plus-lg me-1"></i> Create delivery</a>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl"><div class="card h-100"><div class="card-body"><div class="small text-muted">Filtered deliveries</div><div class="fs-3 fw-bold">{{ number_format($summary['count']) }}</div><div class="small text-muted">All matching records</div></div></div></div>
    <div class="col-6 col-xl"><div class="card h-100"><div class="card-body"><div class="small text-muted">Pending</div><div class="fs-3 fw-bold text-warning">{{ number_format($summary['pending']) }}</div><div class="small text-muted">Awaiting dispatch</div></div></div></div>
    <div class="col-6 col-xl"><div class="card h-100"><div class="card-body"><div class="small text-muted">In progress</div><div class="fs-3 fw-bold text-info">{{ number_format($summary['preparing']) }}</div><div class="small text-muted">Preparing / ready</div></div></div></div>
    <div class="col-6 col-xl"><div class="card h-100"><div class="card-body"><div class="small text-muted">Delivered</div><div class="fs-3 fw-bold text-success">{{ number_format($summary['delivered']) }}</div><div class="small text-muted">Completed handoffs</div></div></div></div>
    <div class="col-12 col-xl"><div class="card h-100"><div class="card-body"><div class="small text-muted">Delivery fees recorded</div><div class="fs-3 fw-bold">${{ number_format($summary['fees'],2) }}</div><div class="small text-muted">Filtered period</div></div></div></div>
</div>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h2 class="h5 fw-bold mb-1">Delivery register</h2><div class="small text-muted">Only paid orders with Delivery order type can be assigned.</div></div>@include('admin.partials.export-actions',['exportType'=>'deliveries'])</div>
    <div class="card-body">
        <form method="GET" action="{{ route('delivery.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-12 col-lg-3"><label class="form-label small fw-semibold">Search</label><input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Delivery, order, customer or address"></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">Fulfillment status</label><select name="status" class="form-select form-select-sm"><option value="">All statuses</option>@foreach(['pending'=>'Pending','confirmed'=>'Confirmed','preparing'=>'Preparing','ready'=>'Ready','out_for_delivery'=>'Out for delivery','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">From</label><input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm"></div>
            <div class="col-6 col-lg-2"><label class="form-label small fw-semibold">To</label><input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm"></div>
            <div class="col-6 col-lg-1"><label class="form-label small fw-semibold">Sort</label><select name="sort_dir" class="form-select form-select-sm"><option value="desc" @selected(request('sort_dir','desc')==='desc')>Newest</option><option value="asc" @selected(request('sort_dir')==='asc')>Oldest</option></select></div>
            <div class="col-6 col-lg-1"><label class="form-label small fw-semibold">Rows</label><select name="per_page" class="form-select form-select-sm">@foreach([10,15,25,50,100] as $size)<option value="{{ $size }}" @selected((string)request('per_page',15)===(string)$size)>{{ $size }}</option>@endforeach</select></div>
            <div class="col-12 col-lg-1 d-flex gap-1"><button class="btn btn-cc btn-sm flex-grow-1">Apply</button><a href="{{ route('delivery.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a></div>
        </form>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Delivery / Order</th><th>Customer</th><th>Destination</th><th>Order date</th><th class="text-end">Fee</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse($deliveries as $delivery)
                @php($state = strtolower((string)$delivery->status))
                @php($badge = ['delivered'=>'success','out_for_delivery'=>'primary','ready'=>'info','preparing'=>'info','confirmed'=>'primary','cancelled'=>'danger','pending'=>'warning'][$state] ?? 'secondary')
                <tr>
                    <td><div class="fw-bold">Delivery #{{ $delivery->delivery_id }}</div><a class="small text-success text-decoration-none" href="{{ route('admin.orders.index',['search'=>$delivery->order_id]) }}">Order #{{ $delivery->order_id }}</a></td>
                    <td><div class="fw-semibold">{{ $delivery->order?->customer?->name ?? 'Walk-in customer' }}</div><div class="small text-muted">{{ $delivery->order?->customer?->phone ?? '—' }}</div></td>
                    <td style="min-width:220px;max-width:320px"><span class="d-inline-block text-truncate" style="max-width:300px" title="{{ $delivery->address }}">{{ $delivery->address }}</span></td>
                    <td class="text-nowrap">{{ $delivery->order?->order_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-end fw-semibold">${{ number_format($delivery->cost,2) }}</td>
                    <td><span class="badge rounded-pill text-bg-{{ $badge }}">{{ str($state)->replace('_',' ')->title() }}</span></td>
                    <td class="text-end text-nowrap"><a href="{{ route('delivery.edit',$delivery->delivery_id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Update</a>
                        @if(!in_array($state,['delivered','cancelled'],true))<form action="{{ route('delivery.destroy',$delivery->delivery_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel delivery #{{ $delivery->delivery_id }}? The record will be preserved.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Cancel delivery"><i class="bi bi-x-circle"></i></button></form>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-5"><i class="bi bi-truck fs-1 d-block text-muted mb-2"></i><div class="fw-semibold">No deliveries found</div><div class="small text-muted">Choose a paid delivery order to create the first record.</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><div class="small text-muted">Showing {{ $deliveries->firstItem() ?? 0 }}–{{ $deliveries->lastItem() ?? 0 }} of {{ $deliveries->total() }} deliveries</div>{{ $deliveries->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
