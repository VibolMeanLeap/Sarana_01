@extends('layout.admin')

@section('content')
<div class="card">
    <div class="card-header"><h5 class="mb-0">Edit Transaction #{{ $transaction->id }}</h5></div>
    <div class="card-body">
        <form action="{{ route('expense_income.update', $transaction->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" required>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected($transaction->category_id == $c->id)>{{ $c->name }} ({{ $c->type->name ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ $transaction->amount }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="datetime-local" name="transaction_date" class="form-control" value="{{ $transaction->transaction_date->format('Y-m-d\TH:i') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="note" class="form-control" rows="2" data-optional>{{ $transaction->note }}</textarea>
            </div>
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('expense_income.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection