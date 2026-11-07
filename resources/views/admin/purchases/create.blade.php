@extends('layout.admin')

@section('content')

<h2>Create Purchase</h2>

<form action="{{route('purchase.store')}}" method="POST">

@csrf

<input class="form-control mb-2" name="supplier_id" placeholder="Supplier">

<input class="form-control mb-2" name="user_id" placeholder="User">

<input class="form-control mb-2" name="product_id" placeholder="Product">

<input class="form-control mb-2" name="quantity" placeholder="Quantity">

<input class="form-control mb-2" name="cost_price" placeholder="Cost Price">

<button class="btn btn-success">

Save

</button>

</form>

@endsection