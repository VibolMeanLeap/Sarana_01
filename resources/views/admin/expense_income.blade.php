@extends('layout.admin')

@section('content')

<!-- 💡 Cards របាយការណ៍សង្ខេបហិរញ្ញវត្ថុ POS (Financial Summary Dashboard) -->
<div class="row mb-3 g-2">
    <!-- 1. Total Sales (POS) -->
    <div class="col-md-2">
        <div class="card text-bg-primary text-white shadow-sm">
            <div class="card-body p-2 text-center">
                <div class="small fw-semibold">1. POS Sales</div>
                <h5 class="mb-0 fw-bold">${{ number_format($totalSales, 2) }}</h5>
            </div>
        </div>
    </div>

    <!-- 2. Cost of Goods Sold (COGS) -->
    <div class="col-md-2">
        <div class="card text-bg-warning text-dark shadow-sm">
            <div class="card-body p-2 text-center">
                <div class="small fw-semibold">2. Cost (ដើមទុន)</div>
                <h5 class="mb-0 fw-bold">${{ number_format($totalCogs, 2) }}</h5>
            </div>
        </div>
    </div>

    <!-- 3. Gross Profit -->
    <div class="col-md-2">
        <div class="card text-bg-info text-white shadow-sm">
            <div class="card-body p-2 text-center">
                <div class="small fw-semibold">3. Gross Profit (ចំណេញដុល)</div>
                <h5 class="mb-0 fw-bold">${{ number_format($grossProfit, 2) }}</h5>
            </div>
        </div>
    </div>

    <!-- 4. Other Income -->
    <div class="col-md-2">
        <div class="card text-bg-secondary text-white shadow-sm">
            <div class="card-body p-2 text-center">
                <div class="small fw-semibold">4. Other Income</div>
                <h5 class="mb-0 fw-bold">${{ number_format($otherIncome, 2) }}</h5>
            </div>
        </div>
    </div>

    <!-- 5. Operating Expense -->
    <div class="col-md-2">
        <div class="card text-bg-danger text-white shadow-sm">
            <div class="card-body p-2 text-center">
                <div class="small fw-semibold">5. Operating Expense (ចំណាយប្រតិបត្តិការ)</div>
                <h5 class="mb-0 fw-bold">${{ number_format($totalExpense, 2) }}</h5>
            </div>
        </div>
    </div>

    <!-- 6. Net Profit / Loss -->
    <div class="col-md-2">
        <div class="card {{ $netProfit >= 0 ? 'text-bg-success' : 'bg-dark' }} text-white shadow-sm">
            <div class="card-body p-2 text-center">
                <div class="small fw-semibold">6. Net Profit (ចំណេញសុទ្ធ)</div>
                <h5 class="mb-0 fw-bold">${{ number_format($netProfit, 2) }}</h5>
            </div>
        </div>
    </div>
</div>

@if($inventoryPurchases > 0)
    <div class="alert alert-info border-0 small mb-3">
        <i class="fa fa-boxes-stacked me-1"></i>
        Inventory purchases recorded in this period: <strong>${{ number_format($inventoryPurchases, 2) }}</strong>.
        They are shown in the transaction history but excluded from operating expenses because inventory cost is recognized through COGS when items are sold.
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0 text-primary"><i class="fa fa-list"></i> Operating Expenses & Other Income</h5>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">Manage Categories</button>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fa fa-plus"></i> New Transaction
            </button>
        </div>
    </div>

    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="GET" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>
                            {{ $c->name }} ({{ $c->type->name ?? ($c->id_type == 1 ? 'Income' : 'Expense') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary btn-sm">Filter</button>
            </div>
        </form>

        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Note</th>
                    <th>By</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr>
                        <td>{{ $t->id }}</td>
                        <td>{{ $t->category->name ?? '-' }}</td>
                        <td>
                            @if(($t->category->id_type ?? null) == 1)
                                <span class="badge bg-success">Income</span>
                            @else
                                <span class="badge bg-danger">Expense</span>
                            @endif
                        </td>
                        <td>${{ number_format($t->amount, 2) }}</td>
                        <td>{{ $t->note }}</td>
                        <td>{{ $t->user->name ?? '-' }}</td>
                        <td>{{ $t->transaction_date ? \Carbon\Carbon::parse($t->transaction_date)->format('Y-m-d H:i') : '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('expense_income.edit', $t->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('expense_income.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this transaction?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No expenses/other income recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $transactions->links() }}
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('expense_income.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">New Expense / Other Income</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Select category --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->type->name ?? ($c->id_type == 1 ? 'Income' : 'Expense') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="datetime-local" name="transaction_date" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note (optional)</label>
                    <textarea name="note" class="form-control" rows="2" placeholder="e.g. Rent, Ice, Electric bill..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Categories Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Accounting Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group mb-3" style="max-height: 240px; overflow-y: auto;">
                    @foreach($categories as $c)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <span>{{ $c->name }}</span>
                                <span class="badge bg-secondary ms-1">{{ $c->type->name ?? ($c->id_type == 1 ? 'Income' : 'Expense') }}</span>
                            </div>
                            <form action="{{ route('accounting-category.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Delete category?')" class="m-0">
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">Delete</button>
                            </form>
                        </li>
                    @endforeach
                </ul>

                <form action="{{ route('accounting-category.store') }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-6">
                        <input type="text" name="name" class="form-control" placeholder="Category name" required>
                    </div>
                    <div class="col-4">
                        <select name="id_type" class="form-select" required>
                            <option value="1">Income</option>
                            <option value="2">Expense</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection