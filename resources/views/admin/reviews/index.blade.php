@extends('layout.admin')

@section('content')

<div class="container">

<h2>Reviews</h2>

<a href="{{ route('reviews.create') }}" class="btn btn-primary mb-3">
Add Review
</a>

<table class="table table-bordered">

<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Product</th>
    <th>Rating</th>
    <th>Comment</th>
    <th>Action</th>
</tr>

@foreach($reviews as $review)

<tr>

<td>{{ $review->id }}</td>

<td>{{ $review->customer_id }}</td>

<td>{{ $review->product_id }}</td>

<td>{{ $review->rating }}</td>

<td>{{ $review->comment }}</td>

<td>



<a href="{{ route('reviews.edit',$review->id) }}" class="btn btn-warning btn-sm">
Edit
</a>

<form action="{{ route('reviews.destroy',$review->id) }}" method="POST" style="display:inline">

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