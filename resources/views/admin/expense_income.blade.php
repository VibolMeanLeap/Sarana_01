@extends('layout.admin')

@section('page_title', 'Sales & Expenses')
@section('topbar_title', 'Financial overview')

@push('styles')
<style>
    .finance-page {
        display: flex;
        flex-direction: column;
        gap: 18px
    }

    .finance-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap
    }

    .finance-kicker {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #809087
    }

    .finance-title {
        font: 800 25px 'Manrope', sans-serif;
        color: #24352b;
        margin: 3px 0 4px
    }

    .finance-subtitle {
        color: #7a8980;
        font-size: 12px;
        margin: 0
    }

    .finance-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px
    }

    .finance-card {
        background: #fff;
        border: 1px solid #e6eee8;
        border-radius: 15px;
        padding: 16px 17px;
        box-shadow: 0 5px 17px rgba(23, 65, 42, .035);
        min-width: 0
    }

    .finance-card .label {
        font-size: 10px;
        color: #819087;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .55px
    }

    .finance-card .value {
        font: 800 22px 'Manrope', sans-serif;
        color: #26382e;
        margin-top: 8px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis
    }

    .finance-card .hint {
        font-size: 10px;
        color: #94a097;
        margin-top: 5px
    }

    .finance-card.income .value {
        color: #168052
    }

    .finance-card.expense .value {
        color: #bb5a4e
    }

    .finance-card.profit .value {
        color: #176b4b
    }

    .finance-card.neutral .value {
        color: #596b9b
    }

    .finance-panel {
        background: #fff;
        border: 1px solid #e6eee8;
        border-radius: 16px;
        box-shadow: 0 5px 17px rgba(23, 65, 42, .035);
        overflow: hidden
    }

    .finance-panel-head {
        padding: 17px 19px;
        border-bottom: 1px solid #edf2ee;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap
    }

    .finance-panel-head h2 {
        font: 800 14px 'Manrope', sans-serif;
        color: #293b30;
        margin: 0
    }

    .finance-body {
        padding: 17px 19px
    }

    .finance-compare {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px
    }

    .finance-compare-block {
        min-width: 0
    }

    .finance-compare-label {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 8px
    }

    .finance-track {
        height: 12px;
        border-radius: 20px;
        background: #f0f4f1;
        overflow: hidden
    }

    .finance-fill {
        height: 100%;
        border-radius: 20px;
        min-width: 0
    }

    .finance-fill.in {
        background: linear-gradient(90deg, #168052, #5db88a)
    }

    .finance-fill.out {
        background: linear-gradient(90deg, #d88d48, #c95b50)
    }

    .finance-net {
        background: #f7faf8;
        border: 1px solid #e5eee8;
        border-radius: 12px;
        padding: 13px 15px;
        margin-top: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap
    }

    .finance-filter {
        display: grid;
        grid-template-columns: minmax(180px, 1.5fr) repeat(5, minmax(105px, 1fr)) auto auto;
        gap: 9px;
        padding: 14px 18px;
        border-bottom: 1px solid #edf2ee
    }

    .finance-filter .form-control,
    .finance-filter .form-select {
        height: 39px;
        font-size: 12px;
        min-width: 0
    }

    .finance-filter .btn {
        height: 39px;
        font-size: 12px;
        font-weight: 700
    }

    .finance-table-wrap {
        overflow-x: auto
    }

    .finance-table {
        width: 100%;
        min-width: 850px;
        border-collapse: collapse
    }

    .finance-table th {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #85928a;
        padding: 11px 13px;
        border-bottom: 1px solid #edf2ee;
        white-space: nowrap
    }

    .finance-table td {
        font-size: 12px;
        color: #46574c;
        padding: 12px 13px;
        border-bottom: 1px solid #f0f4f1;
        vertical-align: middle
    }

    .finance-table tbody tr:hover {
        background: #fbfcfb
    }

    .finance-pagination {
        padding: 13px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px
    }

    @media(max-width:1100px) {
        .finance-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }

        .finance-filter {
            grid-template-columns: repeat(3, minmax(0, 1fr))
        }
    }

    @media(max-width:650px) {
        .finance-grid {
            grid-template-columns: 1fr 1fr
        }

        .finance-filter {
            grid-template-columns: 1fr 1fr
        }

        .finance-compare {
            grid-template-columns: 1fr
        }

        .finance-title {
            font-size: 21px
        }
    }
</style>
@endpush

@section('content')
<div class="finance-page">
    <div class="finance-head">
        <div>
            <div class="finance-kicker"><i class="bi bi-graph-up-arrow me-1"></i>Store performance</div>
            <h1 class="finance-title">Sales & Expenses</h1>
            <p class="finance-subtitle">Track verified sales revenue, supplier purchases and day-to-day operating expenses in one place.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('admin.partials.export-actions', ['exportType' => 'transactions'])
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-tags me-1"></i>Categories</button>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal"><i class="bi bi-plus-lg me-1"></i>New expense</button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success py-2 mb-0">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger py-2 mb-0">{{ session('error') }}</div>@endif

    <div class="finance-grid">
        <div class="finance-card income">
            <div class="label"><i class="bi bi-cup-hot me-1"></i>Sales revenue</div>
            <div class="value">${{ number_format($summary['sales'], 2) }}</div>
            <div class="hint">Verified paid POS and website orders</div>
        </div>
        <div class="finance-card expense">
            <div class="label"><i class="bi bi-box-seam me-1"></i>Inventory purchases</div>
            <div class="value">${{ number_format($summary['inventory_purchases'], 2) }}</div>
            <div class="hint">Supplier stock purchases · cash outflow</div>
        </div>
        <div class="finance-card expense">
            <div class="label"><i class="bi bi-receipt me-1"></i>Operating expenses</div>
            <div class="value">${{ number_format($summary['operating_expenses'], 2) }}</div>
            <div class="hint">Rent, utilities, supplies and maintenance</div>
        </div>
        <div class="finance-card {{ $summary['net_profit'] >= 0 ? 'profit' : 'expense' }}">
            <div class="label"><i class="bi bi-wallet2 me-1"></i>Net profit / loss</div>
            <div class="value">${{ number_format($summary['net_profit'], 2) }}</div>
            <div class="hint">Gross profit minus operating expenses</div>
        </div>
    </div>

    <section class="finance-panel">
        <div class="finance-panel-head">
            <h2><i class="bi bi-arrow-left-right me-2 text-success"></i>Cash inflow vs cash outflow</h2><span class="small text-muted">Selected date range</span>
        </div>
        <div class="finance-body">
            @php($maxCash = max((float) $summary['cash_inflow'], (float) $summary['cash_outflow'], 0.01))
            <div class="finance-compare">
                <div class="finance-compare-block">
                    <div class="finance-compare-label"><span class="text-success">Money received</span><strong>${{ number_format($summary['cash_inflow'], 2) }}</strong></div>
                    <div class="finance-track">
                        <div class="finance-fill in" style="width:{{ min(100, ($summary['cash_inflow'] / $maxCash) * 100) }}%"></div>
                    </div>
                    <div class="small text-muted mt-2">Paid sales revenue only</div>
                </div>
                <div class="finance-compare-block">
                    <div class="finance-compare-label"><span class="text-danger">Money spent</span><strong>${{ number_format($summary['cash_outflow'], 2) }}</strong></div>
                    <div class="finance-track">
                        <div class="finance-fill out" style="width:{{ min(100, ($summary['cash_outflow'] / $maxCash) * 100) }}%"></div>
                    </div>
                    <div class="small text-muted mt-2">Purchases ${{ number_format($summary['inventory_purchases'], 2) }} + operating ${{ number_format($summary['operating_expenses'], 2) }}</div>
                </div>
            </div>
            <div class="finance-net">
                <div>
                    <div class="small text-muted fw-semibold">Net cash movement</div>
                    <div class="small text-muted">Cash received minus cash spent</div>
                </div>
                <div class="fs-4 fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $summary['net_cash_flow'] < 0 ? '-' : '' }}${{ number_format(abs($summary['net_cash_flow']), 2) }}</div>
            </div>
        </div>
    </section>

    <div class="finance-grid">
        <div class="finance-card neutral">
            <div class="label">Cost of goods sold</div>
            <div class="value">${{ number_format($summary['cogs'], 2) }}</div>
            <div class="hint">Cost attached to paid sales</div>
        </div>
        <div class="finance-card profit">
            <div class="label">Gross profit</div>
            <div class="value">${{ number_format($summary['gross_profit'], 2) }}</div>
            <div class="hint">Sales income minus sold-item cost</div>
        </div>
        <div class="finance-card neutral">
            <div class="label">Cash outflow</div>
            <div class="value">${{ number_format($summary['cash_outflow'], 2) }}</div>
            <div class="hint">Inventory purchases + operating expenses</div>
        </div>
        <div class="finance-card neutral">
            <div class="label">Profit margin</div>
            <div class="value">{{ $summary['sales'] > 0 ? number_format(($summary['net_profit'] / $summary['sales']) * 100, 1).'%' : '—' }}</div>
            <div class="hint">Net profit as a share of sales revenue</div>
        </div>
    </div>
    <div class="alert alert-info border-0 small mb-0"><i class="bi bi-info-circle me-1"></i><strong>Accounting note:</strong> Inventory purchases are shown as cash outflow, but are not subtracted again from net profit. Their cost is recognized as Cost of Goods Sold when the stock is sold, preventing double-counting.</div>

    <section class="finance-panel">
        <div class="finance-panel-head">
            <div>
                <h2><i class="bi bi-journal-text me-2 text-success"></i>Expense ledger</h2>
                <div class="small text-muted mt-1">Manual operating expenses and purchase-linked expenses. Paid sales revenue is summarized above.</div>
            </div>
            <span class="badge text-bg-light">{{ number_format($transactions->total()) }} records</span>
        </div>
        <form method="GET" action="{{ route('expense_income.index') }}" class="finance-filter">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search note or ID" aria-label="Search transactions">
            <select name="category_id" class="form-select">
                <option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id')==$category->id)>{{ $category->name }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From date">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To date">
            <select name="sort_dir" class="form-select">
                <option value="desc" @selected(request('sort_dir', 'desc' )==='desc' )>Newest first</option>
                <option value="asc" @selected(request('sort_dir')==='asc' )>Oldest first</option>
            </select>
            <select name="per_page" class="form-select">
                <option value="15" @selected(($perPage ?? 15)===15)>15 / page</option>
                <option value="25" @selected(($perPage ?? 15)===25)>25 / page</option>
                <option value="50" @selected(($perPage ?? 15)===50)>50 / page</option>
                <option value="100" @selected(($perPage ?? 15)===100)>100 / page</option>
            </select>
            <button type="submit" class="btn btn-success">Apply filters</button><a href="{{ route('expense_income.index') }}" class="btn btn-outline-secondary">Reset</a>
        </form>
        <div class="finance-table-wrap">
            <table class="finance-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Reference</th>
                        <th>Note</th>
                        <th>Recorded by</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                                        <tr>
                        <td>#{{ $transaction->id }}</td>
                        <td>{{ $transaction->transaction_date ? \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y') : '—' }}</td>
                        <td><span class="badge text-bg-danger">Expense</span></td>
                        <td>{{ $transaction->category?->name ?? 'Uncategorised' }}</td>
                        <td>{{ $transaction->purchase_id ? 'Purchase #'.$transaction->purchase_id : ($transaction->order_id ? 'Order #'.$transaction->order_id : 'Manual') }}</td>
                        <td>{{ $transaction->note ?: '—' }}</td>
                        <td>{{ $transaction->user?->name ?: '—' }}</td>
                        <td class="text-end fw-bold text-danger">−${{ number_format((float) $transaction->amount, 2) }}</td>
                        <td class="text-end text-nowrap">
                            @if($transaction->purchase_id)
                            <span class="small text-muted">Managed in Purchases</span>
                            @else
                            <a href="{{ route('expense_income.edit', $transaction->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('expense_income.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense record?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Delete</button></form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">No expenses match these filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="finance-pagination">
            <div class="small text-muted">Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ number_format($transactions->total()) }}</div>{{ $transactions->links('pagination::bootstrap-5') }}
        </div>
    </section>
</div>

<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('expense_income.store') }}" method="POST" class="modal-content">@csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addTransactionTitle">Record operating expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Expense category <span class="text-danger">*</span></label><select name="category_id" class="form-select" required>
                        <option value="">Choose a category</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>@error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Amount (USD) <span class="text-danger">*</span></label><input type="number" name="amount" min="0.01" step="0.01" class="form-control" required>@error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label">Date <span class="text-danger">*</span></label><input type="datetime-local" name="transaction_date" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required>@error('transaction_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <div class="mb-0"><label class="form-label">Note</label><textarea name="note" rows="2" class="form-control" placeholder="e.g. Electricity, rent, supplies or maintenance"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Save expense</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryTitle">Accounting categories</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group mb-3">@forelse($categories as $category)<li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $category->name }} <span class="badge text-bg-danger">Expense</span></span>
                        <form action="{{ route('accounting-category.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Delete this category?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                    </li>@empty<li class="list-group-item text-muted">No categories yet.</li>@endforelse</ul>
                <form action="{{ route('accounting-category.store') }}" method="POST" class="row g-2">@csrf
                    <div class="col-9"><input type="text" name="name" class="form-control" placeholder="New expense category" maxlength="255" required></div>
                    <div class="col-3"><button type="submit" class="btn btn-success w-100">Add</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection