@extends('layout.admin')
@section('page_title', 'Record customer feedback')
@section('topbar_title', 'Customer feedback')
@section('content')
<div class="page-heading"><div><div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Customer experience</div><h1>Record feedback</h1><p>Add a review for a paid order that has not received feedback yet.</p></div><a href="{{ route('reviews.index') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-1"></i>Back to reviews</a></div>
<div class="row g-4"><div class="col-12 col-xl-8"><div class="card"><div class="card-body p-4">
    @if($errors->any())<div class="alert alert-danger"><strong>Please check the form.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form action="{{ route('reviews.store') }}" method="POST" novalidate>@csrf
        <div class="mb-3"><label class="form-label fw-semibold">Paid customer order <span class="text-danger">*</span></label><select name="order_id" class="form-select @error('order_id') is-invalid @enderror" required><option value="">Choose an order</option>@foreach($orders as $order)<option value="{{ $order->order_id }}" @selected(old('order_id')==$order->order_id)>#{{ $order->order_id }} · {{ $order->customer?->name }} · ${{ number_format($order->final_price,2) }} · {{ $order->order_date?->format('d M Y') }}</option>@endforeach</select>@error('order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
@if($orders->isEmpty())<div class="form-text text-warning">No eligible paid customer orders are available.</div>@endif</div>
        <div class="mb-3"><label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label><select name="rating" class="form-select @error('rating') is-invalid @enderror" required><option value="">Choose a rating</option>@for($rating=5;$rating>=1;$rating--)<option value="{{ $rating }}" @selected(old('rating')==$rating)>{{ $rating }} / 5 stars</option>@endfor</select>@error('rating')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label class="form-label fw-semibold">Comment</label><textarea name="comment" class="form-control @error('comment') is-invalid @enderror" rows="4" maxlength="2000" placeholder="Write the customer's feedback…" data-optional>{{ old('comment') }}</textarea>@error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('reviews.index') }}" class="btn btn-light border">Cancel</a><button class="btn btn-cc" @disabled($orders->isEmpty())><i class="bi bi-check2-circle me-1"></i>Save review</button></div>
    </form>
</div></div></div><div class="col-12 col-xl-4"><div class="card"><div class="card-body p-4"><i class="bi bi-shield-check fs-3 text-success"></i><h2 class="h6 fw-bold mt-3">Review ownership</h2><p class="small text-muted mb-0">The customer is automatically taken from the selected order. This prevents a review being attached to a different customer's purchase.</p></div></div></div></div>
@endsection
