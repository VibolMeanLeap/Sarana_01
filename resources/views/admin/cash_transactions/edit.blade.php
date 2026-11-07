@extends('layout.admin')

@section('content')

<h2>Edit Cash Transaction</h2>

<form action="{{ route('expense_income.update',$cash_transaction->id) }}" method="POST">

@csrf
@method('PUT')

<select name="type" class="form-control mb-2">
    <option value="Income" {{ $cash_transaction->type=='Income'?'selected':'' }}>Income</option>
    <option value="Expense" {{ $cash_transaction->type=='Expense'?'selected':'' }}>Expense</option>
</select>

<input type="number" step="0.01" name="amount" class="form-control mb-2" value="{{ $cash_transaction->amount }}">

<input type="text" name="description" class="form-control mb-2" value="{{ $cash_transaction->description }}">

<input type="date" name="transaction_date" class="form-control mb-2" value="{{ $cash_transaction->transaction_date }}">

<button class="btn btn-primary">Update</button>

</form>

@endsection