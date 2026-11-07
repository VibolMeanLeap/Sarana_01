@extends('layout.admin')

@section('content')

<style>
    .inv-page-bg {
        background: #f3f4f6;
        min-height: 100vh;
        padding: 24px;
    }

    .inv-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .inv-page-title {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-add-inv {
        background: #2563eb;
        border: none;
        color: #fff;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: background .15s ease;
    }
    .btn-add-inv:hover { background: #1d4ed8; color: #fff; }

    .inv-card {
        background: #fff;
        border-radius: 14px;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 24px;
    }

    .inv-alert-success {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 10px 16px;
        margin-bottom: 16px;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .inv-search-form {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }
    .inv-search-form .form-select {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        font-size: 14px;
        height: 42px;
        width: 100%;
    }
    .inv-search-form .btn-search {
        border-radius: 8px;
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #fff;
        font-size: 14px;
        padding: 0 20px;
        height: 42px;
        white-space: nowrap;
    }
    .inv-search-form .btn-search:hover { background: #1d4ed8; }
    .inv-search-form .btn-clear {
        border-radius: 8px;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-size: 14px;
        padding: 0 16px;
        height: 42px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
    }
    .inv-search-form .btn-clear:hover { background: #e5e7eb; color: #111827; }

    /* Keep filters on one row on desktop, like Order List */
    @media (min-width: 768px) {
        .inv-search-form {
            flex-wrap: nowrap;
        }
        .inv-search-form > * {
            flex: 1 1 0;
            min-width: 0;
        }
        .inv-search-form .btn-search,
        .inv-search-form .btn-clear {
            flex: 0 0 auto;
        }
    }

    .inv-table {
        width: 100%;
        border-collapse: collapse;
    }
    .inv-table thead th {
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .03em;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }
    .inv-table tbody td {
        padding: 12px;
        font-size: 14px;
        color: #111827;
        border-bottom: 1px solid #f1f2f4;
        vertical-align: middle;
    }
    .inv-table tbody tr:hover { background: #f9fafb; }

    .inv-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
    }
    .inv-badge.in  { background: #dcfce7; color: #15803d; }
    .inv-badge.out { background: #fee2e2; color: #b91c1c; }

    .inv-badge-reason {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
    }

    .inv-qty.in  { color: #15803d; font-weight: 700; }
    .inv-qty.out { color: #b91c1c; font-weight: 700; }

    .inv-pagination { display: flex; justify-content: flex-end; margin-top: 18px; }
    .inv-pagination .pagination { gap: 4px; }
    .inv-pagination .page-link {
        border-radius: 8px !important;
        border: 1px solid #e5e7eb;
        color: #374151;
        font-size: 13px;
        min-width: 32px;
        text-align: center;
    }
    .inv-pagination .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    /* Modal restyle */
    #adjustStockModal .modal-content { border-radius: 14px; border: none; }
    #adjustStockModal .modal-header {
        border-bottom: 1px solid #f1f2f4;
        padding: 18px 24px;
    }
    #adjustStockModal .modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    #adjustStockModal .modal-body { padding: 20px 24px; }
    #adjustStockModal .modal-footer {
        border-top: 1px solid #f1f2f4;
        padding: 16px 24px;
    }
    #adjustStockModal .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }
    #adjustStockModal .form-select,
    #adjustStockModal .form-control {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        font-size: 14px;
    }
    #adjustStockModal .btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        border-radius: 8px;
    }
    #adjustStockModal .btn-primary:hover { background: #1d4ed8; }
    #adjustStockModal .btn-secondary {
        background: #f3f4f6;
        border-color: #e5e7eb;
        color: #374151;
        border-radius: 8px;
    }
    #adjustStockModal .btn-secondary:hover { background: #e5e7eb; color: #111827; }
</style>

<div class="inv-page-bg">

    <div class="inv-topbar">
        <h4 class="inv-page-title"><i class="fa fa-boxes"></i> Inventory / Stock Logs</h4>
        <button type="button" class="btn-add-inv" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
            <i class="fa fa-plus"></i> Manual Adjustment
        </button>
    </div>

    <div class="inv-card">

        @if(session('success'))
            <div class="inv-alert-success" role="alert">
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Filter Form -->
        <form method="GET" action="{{ route('inventory.index') }}" class="inv-search-form" id="invFilterForm">
            <select name="product_id" id="filterProduct" class="form-select">
                <option value="">All products</option>
                @foreach($products as $p)
                    <option value="{{ $p->product_id }}" @selected(request('product_id') == $p->product_id)>
                        {{ $p->product_name }}
                    </option>
                @endforeach
            </select>

            <select name="type" id="filterType" class="form-select">
                <option value="">All types</option>
                <option value="in" @selected(request('type') == 'in')>Stock In</option>
                <option value="out" @selected(request('type') == 'out')>Stock Out</option>
            </select>

            <button type="submit" class="btn-search">Filter</button>

            @if(request('product_id') || request('type'))
                <a href="{{ route('inventory.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="inv-table">
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
                    </tr>
                </thead>

                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>#{{ $log->log_id }}</td>

                            <td><strong>{{ $log->product->product_name ?? '-' }}</strong></td>

                            <td>
                                @if(strtolower($log->type ?? '') === 'in')
                                    <span class="inv-badge in"><i class="fa fa-arrow-down"></i> In</span>
                                @else
                                    <span class="inv-badge out"><i class="fa fa-arrow-up"></i> Out</span>
                                @endif
                            </td>

                            <td class="inv-qty {{ strtolower($log->type ?? '') === 'in' ? 'in' : 'out' }}">
                                {{ strtolower($log->type ?? '') === 'in' ? '+' : '-' }}{{ $log->quantity }}
                            </td>

                            <td><span class="inv-badge-reason">{{ ucfirst($log->reason ?? 'Adjustment') }}</span></td>

                            <td>{{ $log->note ?? '-' }}</td>

                            <td>{{ $log->user->name ?? 'System' }}</td>

                            {{-- Safe Date Formatting --}}
                            <td>
                                @if($log->log_date)
                                    {{ \Carbon\Carbon::parse($log->log_date)->format('d/m/Y H:i') }}
                                @elseif($log->created_at)
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No stock movements yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="inv-pagination">
            {{ $logs->links() }}
        </div>
    </div>
</div>


<!-- Manual Adjustment Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">

        <form action="{{ route('inventory.store') }}" method="POST" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-sliders-h"></i> Manual Stock Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">-- Select product --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->product_id }}">
                                {{ $p->product_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="in">Stock In (+)</option>
                            <option value="out">Stock Out (-)</option>
                        </select>
                    </div>

                    <div class="col mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" min="1" class="form-control" required placeholder="e.g. 5">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <input type="text" name="reason" class="form-control" placeholder="e.g. damaged, correction, stock take" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Note (optional)</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="Additional details..."></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-primary"><i class="fa fa-save"></i> Save Adjustment</button>
            </div>

        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('invFilterForm');
        document.getElementById('filterProduct').addEventListener('change', function () {
            form.submit();
        });
        document.getElementById('filterType').addEventListener('change', function () {
            form.submit();
        });
    });
</script>

@endsection
