@extends('layout.admin')

@section('content')

<h2>Create Stock Log</h2>

<form action="{{ route('inventory.store') }}" method="POST">

@csrf

<input type="number" name="product_id" class="form-control mb-2" placeholder="Product ID">

<select name="type" class="form-control mb-2">
    <option value="IN">IN</option>
    <option value="OUT">OUT</option>
</select>

<input type="number" name="quantity" class="form-control mb-2" placeholder="Quantity">

<input type="text" name="note" class="form-control mb-2" placeholder="Note">

<button class="btn btn-success">Save</button>

</form>

@endsection