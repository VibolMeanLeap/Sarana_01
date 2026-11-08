@extends('layout.web')
@section('web_title', $product->product_name)
@section('content')
<style>
    .product-detail-shell{background:radial-gradient(circle at 88% 5%,rgba(230,190,125,.18),transparent 25%),linear-gradient(135deg,#f7faf7,#eef4ef);padding:36px 0 64px}
    .product-detail-card{border:1px solid #e7eee8;border-radius:28px;background:#fff;overflow:hidden;box-shadow:0 22px 65px rgba(24,56,38,.08)}
    .product-detail-photo{min-height:420px;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#eef4ef,#e2ebe3);position:relative}
    .product-detail-photo img{width:100%;height:100%;min-height:420px;max-height:560px;object-fit:cover}
    .product-detail-photo .fallback{font-size:7rem;color:#397a52}
    .product-detail-info{padding:clamp(25px,4vw,48px)}
    .product-detail-eyebrow{font-size:.7rem;font-weight:900;letter-spacing:.16em;text-transform:uppercase;color:#218052}
    .product-detail-title{font-size:clamp(2rem,4vw,3.1rem);font-weight:850;letter-spacing:-.055em;line-height:1.05;color:#193326}
    .product-detail-price{font-size:2rem;font-weight:900;color:#167549;letter-spacing:-.05em}
    .product-detail-old{font-size:1rem;color:#9aa69d;text-decoration:line-through;font-weight:600;margin-left:9px}
    .product-detail-qty{width:94px;border-radius:99px;text-align:center;font-weight:800}
    .product-detail-primary{border-radius:99px;padding:13px 22px;font-weight:800;background:#1d7548;border:0}
    .product-detail-primary:hover{background:#145b36}
    .product-related-card{border:1px solid #e8eee9;border-radius:18px;background:#fff;overflow:hidden;height:100%;transition:.2s}
    .product-related-card:hover{transform:translateY(-4px);box-shadow:0 12px 26px rgba(18,43,29,.08)}
    .product-related-image{height:145px;background:#eef4ef;display:grid;place-items:center;overflow:hidden}
    .product-related-image img{width:100%;height:100%;object-fit:cover}
</style>
<section class="product-detail-shell">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0"><li class="breadcrumb-item"><a href="{{ route('web.home') }}" class="text-success text-decoration-none">Home</a></li><li class="breadcrumb-item"><a href="{{ route('web.shop') }}" class="text-success text-decoration-none">Shop</a></li><li class="breadcrumb-item active" aria-current="page">{{ $product->product_name }}</li></ol>
        </nav>
        @include('admin.message.message')
        <article class="product-detail-card">
            <div class="row g-0">
                <div class="col-lg-6">
                    <div class="product-detail-photo">
                        @if($product->image && file_exists(public_path('images/'.$product->image)))
                            <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->product_name }}">
                        @else
                            <i class="bi bi-cup-hot-fill fallback" aria-hidden="true"></i>
                        @endif
                        @if($offer)<span class="position-absolute top-0 start-0 m-4 badge rounded-pill bg-danger px-3 py-2">{{ rtrim(rtrim(number_format((float)$offer->discount, 2), '0'), '.') }}% OFF</span>@endif
                    </div>
                </div>
                <div class="col-lg-6 d-flex">
                    <div class="product-detail-info w-100 d-flex flex-column">
                        <div class="product-detail-eyebrow mb-3">{{ $product->category->category_name ?? 'Coffee Corner favourite' }}</div>
                        <h1 class="product-detail-title mb-3">{{ $product->product_name }}</h1>
                        <p class="text-secondary lh-lg mb-4">{{ $product->description ?: 'Freshly prepared by the Coffee Corner team. Add this favourite to your basket and enjoy it your way.' }}</p>
                        <div class="mb-3">
                            @if($offer)
                                <div class="product-detail-price">${{ number_format((float)$offer->new_price, 2) }}<span class="product-detail-old">${{ number_format((float)$product->price, 2) }}</span></div>
                                <div class="small text-success fw-bold">You save ${{ number_format(max(0, (float)$product->price - (float)$offer->new_price), 2) }} per item</div>
                            @else
                                <div class="product-detail-price">${{ number_format((float)$product->price, 2) }}</div>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-4">
                            @if($product->isStockTracked())
                                @if((int)$product->qty > 0)<span class="badge rounded-pill text-bg-success">{{ (int)$product->qty }} in stock</span>@else<span class="badge rounded-pill text-bg-danger">Sold out</span>@endif
                            @else
                                <span class="badge rounded-pill text-bg-success">Available · made to order</span>
                            @endif
                            <span class="small text-muted"><i class="bi bi-shield-check me-1"></i>Secure checkout with ABA KHQR</span>
                        </div>
                        @if(!$product->isStockTracked() || (int)$product->qty > 0)
                            <form method="POST" action="{{ route('web.cart.add', $product->product_id) }}" class="mt-auto">
                                @csrf
                                <label for="productQuantity" class="form-label small fw-bold text-muted">Quantity</label>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <input id="productQuantity" class="form-control product-detail-qty" type="number" name="quantity" min="1" max="{{ $product->isStockTracked() ? max(1, (int)$product->qty) : 100 }}" value="1" required>
                                    <button type="submit" class="btn btn-success product-detail-primary flex-grow-1"><i class="bi bi-bag-plus me-2"></i>Add to cart</button>
                                </div>
                            </form>
                        @else
                            <button class="btn btn-secondary rounded-pill mt-auto" disabled>Currently unavailable</button>
                        @endif
                        <div class="d-flex flex-wrap gap-3 mt-4 pt-3 border-top small text-muted"><span><i class="bi bi-cup-hot me-1 text-success"></i>Made fresh</span><span><i class="bi bi-percent me-1 text-success"></i>Promotions applied automatically</span><span><i class="bi bi-bag-check me-1 text-success"></i>Track orders online</span></div>
                    </div>
                </div>
            </div>
        </article>

        @if($relatedProducts->isNotEmpty())
            <section class="mt-5">
                <div class="d-flex justify-content-between align-items-end mb-3"><div><div class="product-detail-eyebrow">You may also enjoy</div><h2 class="h3 fw-bold mb-0" style="color:#193326">More to love</h2></div><a href="{{ route('web.shop') }}" class="text-success fw-bold text-decoration-none small">View full menu <i class="bi bi-arrow-right"></i></a></div>
                <div class="row g-3">
                    @foreach($relatedProducts as $related)
                        @php($relatedOffer = $relatedOffers->get($related->product_id))
                        <div class="col-6 col-md-3">
                            <a href="{{ route('web.product.show', $related->product_id) }}" class="text-decoration-none text-reset">
                                <article class="product-related-card">
                                    <div class="product-related-image">@if($related->image && file_exists(public_path('images/'.$related->image)))<img src="{{ asset('images/'.$related->image) }}" alt="{{ $related->product_name }}" loading="lazy">@else<i class="bi bi-cup-hot text-success fs-1"></i>@endif</div>
                                    <div class="p-3"><div class="fw-bold small">{{ $related->product_name }}</div><div class="text-success fw-bold mt-1">${{ number_format((float)($relatedOffer?->new_price ?? $related->price), 2) }}</div></div>
                                </article>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</section>
@endsection
