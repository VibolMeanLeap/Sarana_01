@extends('layout.admin')
@section('page_title', 'Add review')
@section('topbar_title', 'Customer feedback')
@section('content')
<div class="page-heading"><div><h1>Add a review</h1><p>Record feedback for a completed order.</p></div><a href="{{ route('reviews.index') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-1"></i>Back to reviews</a></div>
<div class="card"><div class="card-body p-4">
    <form action="{{ route('reviews.store') }}" method="POST">@csrf
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold">Completed order</label><select name="order_id" class="form-select" required><option value="">Select an order</option>@foreach($orders as $order)<option value="{{ $order->order_id }}" @selected(old('order_id') == $order->order_id)>#{{ $order->order_id }} · {{ $order->customer->name ?? 'Walk-in' }}</option>@endforeach</select>@error('order_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label fw-semibold">Customer</label><select name="customer_id" class="form-select" required><option value="">Select a customer</option>@foreach($customers as $customer)<option value="{{ $customer->customer_id }}" @selected(old('customer_id') == $customer->customer_id)>{{ $customer->name }}</option>@endforeach</select>@error('customer_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label fw-semibold">Rating</label><select name="rating" class="form-select" required><option value="">Choose rating</option>@for($rating=5;$rating>=1;$rating--)<option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} / 5 stars</option>@endfor</select>@error('rating')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label fw-semibold">Comment</label><textarea name="comment" class="form-control" rows="4" placeholder="Customer feedback…">{{ old('comment') }}</textarea>@error('comment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
        </div>
        <div class="mt-4 d-flex gap-2"><button class="btn btn-cc" type="submit">Save review</button><a href="{{ route('reviews.index') }}" class="btn btn-cc-light">Cancel</a></div>
    </form>
</div></div>
@endsection
