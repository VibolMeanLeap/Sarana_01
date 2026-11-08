@extends('layout.admin')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="small text-muted text-uppercase fw-semibold mb-1">Finance / Edit entry</div>
        <h1 class="h3 fw-bold mb-1">Update expense #{{ $transaction->id }}</h1>
        <p class="text-muted mb-0">Review the details and save your changes.</p>
    </div>
    <a href="{{ route('expense_income.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to finance</a>
</div>
<div class="card border-0 shadow-sm" style="max-width:760px">
    <div class="card-body p-4">
        <form action="{{ route('expense_income.update', $transaction->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold">Expense category</label>
                <select name="category_id" class="form-select" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $transaction->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Amount (USD)</label>
                    <input type="number" name="amount" min="0.01" step="0.01" class="form-control" value="{{ old('amount', $transaction->amount) }}" required>
                    @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Transaction date</label>
                    <input type="datetime-local" name="transaction_date" class="form-control" value="{{ old('transaction_date', optional($transaction->transaction_date)->format('Y-m-d\\TH:i')) }}" required>
                    @error('transaction_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-3 mb-4">
                <label class="form-label fw-semibold">Note <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="note" rows="3" maxlength="2000" class="form-control" data-optional>{{ old('note', $transaction->note) }}</textarea>
                @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('expense_income.index') }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
