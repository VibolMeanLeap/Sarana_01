@extends('layout.admin')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Inventory / Stock Logs</h5>
        <a href="{{ route('inventory.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> Manual Adjustment
        </a>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->product_id }}" @selected(request('product_id') == $p->product_id)>{{ $p->product_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    <option value="in" @selected(request('type') == 'in')>Stock In</option>
                    <option value="out" @selected(request('type') == 'out')>Stock Out</option>
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
                    <th>Product</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Reason</th>
                    <th>Note</th>
                    <th>By</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->log_id }}</td>
                        <td>{{ $log->product->product_name ?? '-' }}</td>
                        <td>
                            @if($log->type === 'in')
                                <span class="badge bg-success">In</span>
                            @else
                                <span class="badge bg-danger">Out</span>
                            @endif
                        </td>
                        <td>{{ $log->quantity }}</td>
                        <td>{{ ucfirst($log->reason) }}</td>
                        <td>{{ $log->note }}</td>
                        <td>{{ $log->user->name ?? '-' }}</td>
                        <td>{{ optional($log->log_date ?? $log->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('inventory.edit', $log->log_id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('inventory.destroy', $log->log_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this log? Stock will be reverted.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No stock movements yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $logs->links() }}
    </div>
</div>
@endsection