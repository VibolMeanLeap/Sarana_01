@if ($order->is_paid && $order->order_status === 'completed')
    <section class="order-track-card p-3 p-md-4 mt-4" id="review">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
            <div>
                <div class="small fw-bold text-success text-uppercase mb-1" style="letter-spacing:.12em">Customer feedback</div>
                <h2 class="h5 fw-bold mb-1">How was your order?</h2>
                <p class="small text-muted mb-0">Your feedback helps us improve the store experience.</p>
            </div>
            <i class="bi bi-chat-square-heart text-success fs-2"></i>
        </div>
        @if ($order->review)
            <div class="rounded-4 p-3" style="background:#f6faf7;border:1px solid #e5eee7">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="review-stars fs-5">{{ str_repeat('★', (int) $order->review->rating) }}{{ str_repeat('☆', 5 - (int) $order->review->rating) }}</div>
                    <span class="badge rounded-pill {{ $order->review->is_approved ? 'text-bg-success' : 'text-bg-warning' }}">{{ $order->review->is_approved ? 'Published' : 'Awaiting store approval' }}</span>
                </div>
                <div class="small text-muted">Your submitted review</div>
                <p class="mb-0 mt-1">{{ $order->review->comment ?: 'You submitted a rating without a written comment.' }}</p>
            </div>
        @else
            @error('review')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
            <form method="POST" action="{{ route('web.orders.review', $order->order_id) }}">@csrf
                <label class="form-label fw-semibold">Select your rating <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-2 mb-3" role="radiogroup" aria-label="Order rating">
                    @for ($rating = 5; $rating >= 1; $rating--)
                        <label class="review-rating"><input type="radio" name="rating" value="{{ $rating }}" required><span>{{ $rating }} ★</span></label>
                    @endfor
                </div>
                @error('rating')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <label class="form-label fw-semibold" for="reviewComment">Comment <span class="small text-muted fw-normal">(optional)</span></label>
                <textarea class="form-control mb-3" id="reviewComment" name="comment" rows="3" maxlength="2000" placeholder="Tell us what you liked or what we could improve...">{{ old('comment') }}</textarea>
                @error('comment')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-success rounded-pill px-4"><i class="bi bi-send me-2"></i>Submit review</button>
                <div class="small text-muted mt-2">Reviews are checked by our team before appearing publicly.</div>
            </form>
        @endif
    </section>
@endif
