@extends('layout.admin')
@section('page_title', 'Edit review')
@section('topbar_title', 'Customer feedback')
@section('content')
<div class="page-heading"><div><h1>Edit review #{{ $review->review_id }}</h1><p>Update the saved customer feedback.</p></div><a href="{{ route('reviews.index') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-1"></i>Back to reviews</a></div>
<div class="card"><div class="card-body p-4">
    <form action="{{ route('reviews.update', $review->review_id) }}" method="POST">@csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold">Customer</label><select name="customer_id" class="form-select" required>@foreach($customers as $customer)<option value="{{ $customer->customer_id }}" @selected(old('customer_id', $review->customer_id) == $customer->customer_id)>{{ $customer->name }}</option>@endforeach</select>@error('customer_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label fw-semibold">Rating</label><select name="rating" class="form-select" required>@for($rating=5;$rating>=1;$rating--)<option value="{{ $rating }}" @selected(old('rating', $review->rating) == $rating)>{{ $rating }} / 5 stars</option>@endfor</select>@error('rating')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label fw-semibold">Comment</label><textarea name="comment" class="form-control" rows="4" data-optional>{{ old('comment', $review->comment) }}</textarea>@error('comment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
        </div>
        <div class="mt-4 d-flex gap-2"><button class="btn btn-cc" type="submit">Save changes</button><a href="{{ route('reviews.index') }}" class="btn btn-cc-light">Cancel</a></div>
    </form>
</div></div>
@endsection
