@extends('layout.admin')

@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary"><i class="fa fa-history"></i> Stock Logs Report (កំណត់ត្រាចលនាស្តុក)</h5>
        <a href="{{ route('inventory.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus"></i> Adjust Stock
        </a>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 mb-3">
            <div class="col-md-4">
                {{-- 💡 កែមកជា product_id និង product_name --}}
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">-- All Products --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->product_id }}" @selected(request('product_id') == $p->product_id)>
                            {{ $p->product_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">-- All Types --</option>
                    <option value="in" @selected(request('type') == 'in')>Stock In (ចូល)</option>
                    <option value="out" @selected(request('type') == 'out')>Stock Out (ចេញ)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa fa-filter"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
            </div>
        </form>

        <!-- Table Display Logs -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th># Log ID</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Reason</th>
                        <th>Note</th>
                        <th>By User</th>
                        <th>Date & Time</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            {{-- 💡 កែមកជា log_id --}}
                            <td>#{{ $log->log_id }}</td>
                            
                            {{-- 💡 កែមកជា product_name --}}
                            <td><strong>{{ $log->product->product_name ?? 'N/A' }}</strong></td>
                            
                            <td>
                                @if($log->type == 'in')
                                    <span class="badge bg-success"><i class="fa fa-arrow-down"></i> IN</span>
                                @else
                                    <span class="badge bg-danger"><i class="fa fa-arrow-up"></i> OUT</span>
                                @endif
                            </td>
                            
                            <td class="fw-bold {{ $log->type == 'in' ? 'text-success' : 'text-danger' }}">
                                {{ $log->type == 'out' ? '-' : '+' }}{{ $log->quantity }}
                            </td>
                            
                            {{-- 💡 បង្ហាញ Column reason ជំនួស unit_cost / reference --}}
                            <td><span class="badge bg-light text-dark border">{{ $log->reason }}</span></td>
                            
                            <td>{{ $log->note ?? '-' }}</td>
                            <td>{{ $log->user->name ?? 'System' }}</td>
                            <td>{{ $log->created_at ? $log->created_at->format('Y-m-d H:i') : '-' }}</td>
                            
                            <!-- Action Buttons for Edit/Delete -->
                            <td class="text-center">
                                <a href="{{ route('inventory.edit', $log->log_id) }}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                
                                <form action="{{ route('inventory.destroy', $log->log_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to revert this stock log?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No stock logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-end">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection