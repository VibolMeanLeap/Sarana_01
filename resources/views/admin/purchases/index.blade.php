@extends('layout.admin')

@section('content')

<div class="container">

<h2>Purchases</h2>

<a href="{{ route('purchase.create') }}" class="btn btn-primary mb-3">
Add Purchase
</a>

<table class="table table-bordered">

<tr>

<th>ID</th>

<th>Supplier</th>

<th>User</th>

<th>Product</th>

<th>Qty</th>

<th>Cost</th>

<th>Action</th>

</tr>

@foreach($purchases as $purchase)

<tr>

<td>{{$purchase->id}}</td>

<td>{{$purchase->supplier_id}}</td>

<td>{{$purchase->user_id}}</td>

<td>{{$purchase->product_id}}</td>

<td>{{$purchase->quantity}}</td>

<td>{{$purchase->cost_price}}</td>

<td>


</a>

<a href="{{route('purchase.edit',$purchase->id)}}" class="btn btn-warning btn-sm">

Edit

</a>

<form action="{{route('purchase.destroy',$purchase->id)}}" method="POST">

@csrf

@method('DELETE')

<button class="btn btn-danger btn-sm">

Delete

</button>

</form>

</td>

</tr>

@endforeach

</table>

</div>

@endsection