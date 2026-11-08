@extends('layout.admin')
@section('page_title', 'Review details')
@section('topbar_title', 'Customer feedback')
@section('content')
<div class="page-heading"><div><h1>Review #{{ $review->review_id }}</h1><p>Feedback linked to order #{{ $review->order_id }}.</p></div><a href="{{ route('reviews.index') }}" class="btn btn-cc-light">Back to reviews</a></div>
<div class="card"><div class="card-body p-4"><div class="mb-3"><div class="small text-muted">Customer</div><div class="fw-bold">{{ $review->customer->name ?? 'Unknown customer' }}</div></div><div class="mb-3"><div class="small text-muted">Rating</div><div class="fs-5" style="color:#d49a24">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }} <span class="small text-muted">{{ $review->rating }}/5</span></div></div><div class="mb-3"><div class="small text-muted">Publication</div><span class="badge {{ $review->is_approved ? 'text-bg-success' : 'text-bg-warning' }}">{{ $review->is_approved ? 'Published on store' : 'Pending approval' }}</span></div><div><div class="small text-muted">Comment</div><p class="mb-0">{{ $review->comment ?: 'No written comment.' }}</p></div></div></div>
@endsection
