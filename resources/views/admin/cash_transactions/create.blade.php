@extends('layout.admin')

@section('content')

<h2>Create Cash Transaction</h2>

<form action="{{ route('expense_income.store') }}" method="POST">

@csrf

<select name="type" class="form-control mb-2">
    <option value="Income">Income</option>
    <option value="Expense">Expense</option>
</select>

<input type="number" step="0.01" name="amount" class="form-control mb-2" placeholder="Amount">

<input type="text" name="description" class="form-control mb-2" placeholder="Description">

<input type="date" name="transaction_date" class="form-control mb-2">

<button class="btn btn-success">Save</button>

</form>

@endsection