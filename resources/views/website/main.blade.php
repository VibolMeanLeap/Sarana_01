@extends('layout.web')

@section('content')
<!-- 2. Hero Section -->
<section class="hero-tech">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <h1 class="mb-3">Top Artisanal Coffee & Brews</h1>
                <p class="mb-4">Explore our curated roast selections, cold brews, and premium coffee gear.</p>
                <a href="#featured" class="btn btn-green btn-lg">Shop Now</a>
            </div>
            <div class="col-lg-6 text-center">
                <img src="https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=800&q=80"
                    alt="Coffee Set" class="img-fluid rounded-3 shadow-lg"
                    style="max-height: 380px; width: 100%; object-fit: cover;">
            </div>
        </div>
    </div>
</section>

<!-- 3. Value Proposition Bar -->
<div class="value-bar text-center">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-truck text-success fs-5"></i>
                <span>Pickup or home delivery</span>
            </div>
            <div class="col-md-4 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-shield-check text-success fs-5"></i>
                <span>Freshly prepared to order</span>
            </div>
            <div class="col-md-4 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-headset text-success fs-5"></i>
                <span>Secure ABA KHQR checkout</span>
            </div>
        </div>
    </div>
</div>

@if($shopSettings?->address || $shopSettings?->tel)
<section class="py-3 bg-white border-bottom">
    <div class="container d-flex flex-wrap justify-content-center gap-4 small text-muted">
        @if($shopSettings?->address)<span><i class="bi bi-geo-alt-fill text-success me-2"></i>{{ $shopSettings->address }}</span>@endif
        @if($shopSettings?->tel)<a class="text-muted text-decoration-none" href="tel:{{ preg_replace('/[^0-9+]/', '', $shopSettings->tel) }}"><i class="bi bi-telephone-fill text-success me-2"></i>{{ $shopSettings->tel }}</a>@endif
    </div>
</section>
@endif

<!-- Real catalogue products and Laravel-backed Add to Cart forms -->
<section id="featured" class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <span class="small text-success fw-bold text-uppercase" style="letter-spacing:.14em">A few customer favourites</span>
                <h2 class="section-title mb-1">Fresh from our menu</h2>
                <p class="text-muted mb-0">Choose a favourite and add it to your real shopping cart.</p>
            </div>
            <a href="{{ route('web.shop') }}" class="btn btn-outline-success rounded-pill px-4">Explore full menu <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        @include('admin.message.message')

        <div class="row g-3 g-lg-4">
            @forelse($products as $product)
                @php($activeOffer = $offersByProduct->get($product->product_id))
                <div class="col-6 col-md-4 col-xl-3" id="product-{{ $product->product_id }}">
                    <article class="product-card h-100 d-flex flex-column position-relative">
                        @if($activeOffer)
                            <span class="product-badge badge-sale">{{ rtrim(rtrim(number_format((float) $activeOffer->discount, 2), '0'), '.') }}% OFF</span>
                        @endif
                        <div class="product-img-box">
                            @if($product->image && file_exists(public_path('images/'.$product->image)))
                                <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->product_name }}" class="rounded" loading="lazy">
                            @else
                                <i class="bi bi-cup-hot text-success" style="font-size:3.5rem"></i>
                            @endif
                        </div>
                        <div class="product-title"><a class="text-decoration-none text-reset" href="{{ route('web.product.show', $product->product_id) }}">{{ $product->product_name }}</a></div>
                        <p class="small text-muted mb-3">{{ \Illuminate\Support\Str::limit($product->description, 68) }}</p>
                        <div class="product-price mt-auto">
                            @if($activeOffer)
                                ${{ number_format((float) $activeOffer->new_price, 2) }}
                                <span class="old-price">${{ number_format((float) $product->price, 2) }}</span>
                            @else
                                ${{ number_format((float) $product->price, 2) }}
                            @endif
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('web.product.show', $product->product_id) }}" class="btn btn-outline-success rounded-pill" aria-label="View {{ $product->product_name }}"><i class="bi bi-eye"></i></a>
                            <form method="POST" action="{{ route('web.cart.add', $product->product_id) }}" class="flex-grow-1">
                                @csrf
                                <button class="btn btn-green w-100" type="submit"><i class="bi bi-bag-plus me-2"></i>Add to cart</button>
                            </form>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12"><div class="bg-white border rounded-4 text-center p-5"><i class="bi bi-cup-hot display-5 text-success"></i><h3 class="h5 fw-bold mt-3">Our menu is being prepared</h3><p class="text-muted mb-0">Please visit again soon.</p></div></div>
            @endforelse
        </div>
    </div>
</section>


<section class="py-5" style="background:#f6faf7">
    <div class="container">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div><div class="small text-success fw-bold text-uppercase mb-2" style="letter-spacing:.14em">Guest stories</div><h2 class="section-title mb-1">What customers say</h2><p class="text-muted mb-0">Verified feedback from completed and paid orders.</p></div>
            @if(($reviewSummary?->total ?? 0) > 0)
                <div class="text-end"><div class="d-flex align-items-center justify-content-end gap-2"><span class="display-6 fw-bold text-dark">{{ number_format((float)$reviewSummary->average, 1) }}</span><span class="text-warning fs-5">★★★★★</span></div><div class="small text-muted">{{ number_format((int)$reviewSummary->total) }} published reviews</div></div>
            @endif
        </div>
        @if($approvedReviews->isNotEmpty())
            <div class="row g-3">
                @foreach($approvedReviews as $customerReview)
                    @php($reviewerFirstName = \Illuminate\Support\Str::before(trim((string)($customerReview->customer?->name ?? 'Customer')), ' '))
                    <div class="col-12 col-md-6 col-xl-4"><article class="h-100 bg-white border rounded-4 p-4 shadow-sm"><div class="text-warning mb-3" style="letter-spacing:2px">{{ str_repeat('★',(int)$customerReview->rating) }}{{ str_repeat('☆',5-(int)$customerReview->rating) }}</div><p class="mb-4" style="color:#46574c">{{ $customerReview->comment ?: 'Thank you for your rating.' }}</p><div class="d-flex align-items-center gap-2 mt-auto"><span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width:38px;height:38px;background:#eaf5ed;color:#176b4b">{{ strtoupper(substr($reviewerFirstName,0,1)) }}</span><div><div class="fw-bold">{{ $reviewerFirstName }}</div><div class="small text-muted">Verified customer</div></div></div></article></div>
                @endforeach
            </div>
        @else
            <div class="bg-white border rounded-4 p-4 text-center"><i class="bi bi-chat-square-heart text-success fs-2"></i><h3 class="h5 fw-bold mt-2">Your feedback matters</h3><p class="text-muted mb-0">After your order is completed, you can leave a rating and share your experience.</p></div>
        @endif
    </div>
</section>

@endsection
