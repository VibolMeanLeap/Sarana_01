@extends('layout.admin')
@section('page_title','Moderate review')
@section('topbar_title','Customer feedback')
@section('content')
<div class="page-heading"><div><div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Customer experience</div><h1>Moderate review #{{ $review->review_id }}</h1><p>Update the rating or correct the review comment. Order ownership stays fixed.</p></div><a href="{{ route('reviews.index') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-1"></i>Back to reviews</a></div>
<div class="card"><div class="card-body p-4"><div class="row g-3 mb-4"><div class="col-md-6"><div class="small text-muted">Customer</div><div class="fw-bold">{{ $review->customer?->name ?? 'Unknown customer' }}</div></div><div class="col-md-6"><div class="small text-muted">Order</div><div class="fw-bold">#{{ $review->order_id }}</div></div></div>
    <form action="{{ route('reviews.update',$review->review_id) }}" method="POST" novalidate>@csrf @method('PUT')
        <div class="mb-3"><label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label><select name="rating" class="form-select @error('rating') is-invalid @enderror" required>@for($rating=5;$rating>=1;$rating--)<option value="{{ $rating }}" @selected(old('rating',$review->rating)==$rating)>{{ $rating }} / 5 stars</option>@endfor</select>@error('rating')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label class="form-label fw-semibold">Comment</label><textarea name="comment" class="form-control @error('comment') is-invalid @enderror" rows="5" maxlength="2000" data-optional>{{ old('comment',$review->comment) }}</textarea>@error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('reviews.index') }}" class="btn btn-light border">Cancel</a><button class="btn btn-cc">Save changes</button></div>
    </form>
</div></div>
@endsection
