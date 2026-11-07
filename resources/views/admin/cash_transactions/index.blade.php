@extends('layout.admin')

@section('content')

<div class="container">

<h2>Cash Transactions</h2>

<a href="{{ route('expense_income.create') }}" class="btn btn-primary mb-3">
Add Transaction
</a>

<table class="table table-bordered">

<tr>
    <th>ID</th>
    <th>Type</th>
    <th>Amount</th>
    <th>Description</th>
    <th>Date</th>
    <th>Action</th>
</tr>

@foreach($transactions as $transaction)

<tr>

<td>{{ $transaction->id }}</td>

<td>{{ $transaction->type }}</td>

<td>${{ $transaction->amount }}</td>

<td>{{ $transaction->description }}</td>

<td>{{ $transaction->transaction_date }}</td>

<td>


<a href="{{ route('expense_income.edit',$transaction->id) }}" class="btn btn-warning btn-sm">Edit</a>

<form action="{{ route('expense_income.destroy',$transaction->id) }}" method="POST" style="display:inline">

@csrf
@method('DELETE')

<button class="btn btn-danger btn-sm">Delete</button>

</form>

</td>

</tr>

@endforeach

</table>

</div>

@endsection