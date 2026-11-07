@extends('layout.web')
@section('web_title', 'Shop')

@section('content')
<style>
    .shop-hero{background:radial-gradient(circle at 82% 12%,rgba(226,184,112,.18),transparent 26%),linear-gradient(120deg,#10251d 0%,#1e4935 58%,#274f3b 100%);border-radius:30px;color:#fff;overflow:hidden;position:relative;isolation:isolate}
    .shop-hero:before{content:"";position:absolute;width:390px;height:390px;border:1px solid rgba(240,208,153,.16);border-radius:50%;right:-120px;top:-190px;box-shadow:0 0 0 35px rgba(240,208,153,.035),0 0 0 75px rgba(240,208,153,.025);z-index:-1}
    .shop-hero-kicker{color:#dfbd80;letter-spacing:.18em;font-size:.72rem;font-weight:800;text-transform:uppercase}
    .shop-hero h1{font-weight:800;letter-spacing:-.045em;line-height:1.06}
    .shop-hero-visual{width:100%;height:190px;border-radius:22px;overflow:hidden;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08)}
    .shop-hero-visual img{width:100%;height:100%;object-fit:cover}
    .offer-zone{padding-top:34px}
    .offer-banner{min-height:260px;position:relative;overflow:hidden;border-radius:25px;color:#fff;background:radial-gradient(circle at 83% 18%,rgba(233,192,117,.22),transparent 30%),linear-gradient(115deg,#173527 0%,#1c5039 55%,#87643a 100%);box-shadow:0 18px 45px rgba(24,56,40,.13);isolation:isolate}
    .offer-banner:after{content:"";position:absolute;right:-85px;bottom:-175px;width:360px;height:360px;border:1px solid rgba(255,255,255,.22);border-radius:50%;box-shadow:0 0 0 25px rgba(255,255,255,.035),0 0 0 55px rgba(255,255,255,.025);z-index:-1}
    .offer-banner-copy{position:relative;z-index:3;padding:30px 32px;max-width:62%}
    .offer-banner-kicker{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:30px;background:#f1cd8e;color:#3b2a17;font-size:.68rem;font-weight:900;letter-spacing:.12em}
    .offer-banner-title{font-size:clamp(1.5rem,3vw,2.2rem);font-weight:850;letter-spacing:-.04em;line-height:1.08;margin:15px 0 8px}
    .offer-banner-product{color:rgba(255,255,255,.75);font-size:.95rem}
    .offer-banner-price{display:flex;align-items:baseline;gap:10px;margin:16px 0 18px}
    .offer-banner-price strong{font-size:2.1rem;letter-spacing:-.05em;color:#f6d69e}
    .offer-banner-price del{color:rgba(255,255,255,.58);font-size:1rem}
    .offer-banner-image{position:absolute;right:0;top:0;bottom:0;width:43%;z-index:1;opacity:.95;mask-image:linear-gradient(90deg,transparent 0%,#000 24%);-webkit-mask-image:linear-gradient(90deg,transparent 0%,#000 24%)}
    .offer-banner-image img{width:100%;height:100%;object-fit:cover;mix-blend-mode:screen}
    .offer-banner-stamp{position:absolute;z-index:4;right:20px;top:18px;width:76px;height:76px;border-radius:50%;background:#f4cf91;color:#382614;display:flex;flex-direction:column;align-items:center;justify-content:center;transform:rotate(10deg);font-weight:900;box-shadow:0 8px 25px rgba(0,0,0,.18)}
    .offer-banner-stamp strong{font-size:1.18rem;line-height:1}
    .offer-banner-stamp small{font-size:.55rem;letter-spacing:.1em}
    .offer-banner-btn{display:inline-flex;gap:9px;align-items:center;background:#f5d59e;color:#332515;border:0;border-radius:99px;padding:11px 18px;font-size:.84rem;font-weight:800;text-decoration:none;transition:transform .2s,background .2s}
    .offer-banner-btn:hover{background:#fff0cf;color:#332515;transform:translateY(-2px)}
    .offer-banner-date{font-size:.72rem;color:rgba(255,255,255,.65);margin-left:10px}
    .shop-section-eyebrow{font-size:.7rem;font-weight:800;letter-spacing:.15em;text-transform:uppercase;color:#14804b}
    .shop-section-title{font-weight:850;letter-spacing:-.04em;color:#15281e}
    .shop-product-card{border:1px solid #e8eee9;border-radius:22px;overflow:hidden;box-shadow:0 8px 24px rgba(18,43,29,.045);transition:transform .22s,box-shadow .22s;background:#fff;height:100%;position:relative}
    .shop-product-card:hover{transform:translateY(-5px);box-shadow:0 18px 38px rgba(18,43,29,.11)}
    .shop-product-image{height:190px;background:linear-gradient(135deg,#f0f5f0,#e6eee6);display:flex;align-items:center;justify-content:center;overflow:hidden}
    .shop-product-image img{width:100%;height:100%;object-fit:cover;transition:transform .35s}
    .shop-product-card:hover .shop-product-image img{transform:scale(1.04)}
    .shop-sale-chip{position:absolute;left:14px;top:14px;z-index:2;background:#b83e35;color:#fff;font-weight:900;font-size:.7rem;border-radius:99px;padding:7px 11px;box-shadow:0 4px 12px rgba(90,24,20,.15)}
    .shop-product-name{font-weight:800;color:#1c3025;letter-spacing:-.02em}
    .shop-price{color:#147a45;font-weight:850;font-size:1.12rem}
    .shop-old-price{color:#98a39b;font-size:.83rem;text-decoration:line-through;font-weight:500;margin-left:5px}
    .shop-save-note{font-size:.7rem;color:#b33e35;font-weight:700}
    .shop-add-btn{background:#1d7548;color:#fff;border-radius:99px;font-weight:750;border:0;padding:10px 14px}
    .shop-add-btn:hover{background:#145b36;color:#fff}
    @media(max-width:767.98px){.shop-hero{border-radius:22px}.shop-hero-visual{height:155px}.offer-banner{min-height:300px}.offer-banner-copy{max-width:100%;padding:26px 22px 125px}.offer-banner-image{top:auto;bottom:0;left:35%;width:65%;height:155px;mask-image:linear-gradient(180deg,transparent 0%,#000 24%);-webkit-mask-image:linear-gradient(180deg,transparent 0%,#000 24%)}.offer-banner-stamp{width:66px;height:66px;right:14px;top:14px}.offer-banner-title{max-width:80%}.offer-banner-price{margin:12px 0}.offer-banner-date{display:block;margin:10px 0 0}.shop-product-image{height:155px}}
    @media(prefers-reduced-motion:reduce){.shop-product-card,.shop-product-image img,.offer-banner-btn{transition:none}}
</style>

<section class="py-4 py-lg-5">
    <div class="container">
        <div class="shop-hero p-4 p-lg-5">
            <div class="row align-items-center g-4 position-relative">
                <div class="col-lg-7">
                    <div class="shop-hero-kicker mb-3"><i class="bi bi-cup-hot-fill me-2"></i>Freshly made · Lovingly served</div>
                    <h1 class="display-4 mb-3">Find your next<br><span style="color:#e9c58c">little happy place.</span></h1>
                    <p class="text-white-50 mb-4" style="max-width:520px">From smooth espresso to refreshing favourites, discover something delicious for every kind of day.</p>
                    <a href="#shop-menu" class="btn btn-light rounded-pill fw-bold px-4 py-3 text-success"><i class="bi bi-arrow-down-circle me-2"></i>Explore the menu</a>
                    @if($offers->isNotEmpty())<span class="ms-2 small text-white-50"><i class="bi bi-stars me-1" style="color:#e9c58c"></i>{{ $offers->count() }} special {{ \Illuminate\Support\Str::plural('offer', $offers->count()) }} live now</span>@endif
                </div>
                <div class="col-lg-5">
                    <div class="shop-hero-visual shadow-lg">
                        <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=85" alt="Fresh coffee being prepared" loading="lazy">
                    </div>
                    <div class="d-flex justify-content-end mt-2 small text-white-50"><i class="bi bi-heart-fill me-2" style="color:#e9c58c"></i>Made for your everyday moments</div>
                </div>
            </div>
        </div>

        @include('admin.message.message')

        @if($offers->isNotEmpty())
            <section class="offer-zone" aria-labelledby="special-offers-heading">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
                    <div><div class="shop-section-eyebrow mb-1"><i class="bi bi-stars me-1"></i>Good things, for less</div><h2 id="special-offers-heading" class="h3 shop-section-title mb-0">Today’s special treats</h2></div>
                    <span class="small text-muted">Limited-time prices · While stocks last</span>
                </div>
                <div class="row g-3">
                    @foreach($offers as $offer)
                        @php $offerProduct = $offer->product; @endphp
                        @if($offerProduct)
                            <div class="col-12 col-xl-6">
                                <article class="offer-banner h-100">
                                    <div class="offer-banner-copy">
                                        <span class="offer-banner-kicker"><i class="bi bi-stars"></i> LIMITED-TIME OFFER</span>
                                        <div class="offer-banner-title">A little treat<br>for your day.</div>
                                        <div class="offer-banner-product">{{ $offerProduct->product_name }}</div>
                                        <div class="offer-banner-price"><strong>${{ number_format((float)$offer->new_price, 2) }}</strong><del>${{ number_format((float)$offerProduct->price, 2) }}</del></div>
                                        <form method="POST" action="{{ route('web.cart.add', $offerProduct->product_id) }}" class="d-inline-block">
                                            @csrf
                                            <button class="offer-banner-btn" type="submit"><i class="bi bi-bag-plus me-1"></i>Grab this deal <i class="bi bi-arrow-up-right"></i></button>
                                        </form>
                                        <span class="offer-banner-date"><i class="bi bi-calendar-event me-1"></i>Until {{ \Carbon\Carbon::parse($offer->end_date)->format('M d, Y') }}</span>
                                    </div>
                                    <div class="offer-banner-image" aria-hidden="true">
                                        @if($offerProduct->image && file_exists(public_path('images/'.$offerProduct->image)))
                                            <img src="{{ asset('images/'.$offerProduct->image) }}" alt="" loading="lazy">
                                        @else
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center"><i class="bi bi-cup-hot-fill" style="font-size:8rem;color:rgba(245,213,158,.6)"></i></div>
                                        @endif
                                    </div>
                                    <div class="offer-banner-stamp"><strong>{{ rtrim(rtrim(number_format((float)$offer->discount, 2), '0'), '.') }}%</strong><small>OFF</small></div>
                                </article>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        <section id="shop-menu" class="pt-5 mt-2">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div><div class="shop-section-eyebrow mb-1">Made fresh for you</div><h2 class="h2 shop-section-title mb-1">Explore our menu</h2><p class="text-muted mb-0">Choose your favourites and add them to your cart.</p></div>
                <a href="{{ route('web.cart') }}" class="btn btn-outline-success rounded-pill px-4"><i class="bi bi-bag me-2"></i>View cart</a>
            </div>

            <div class="row g-3 g-lg-4">
                @forelse($products as $product)
                    @php $activeOffer = $offersByProduct->get($product->product_id); @endphp
                    <div class="col-6 col-md-4 col-xl-3" id="product-{{ $product->product_id }}">
                        <article class="shop-product-card d-flex flex-column">
                            @if($activeOffer)<span class="shop-sale-chip"><i class="bi bi-lightning-fill me-1"></i>{{ rtrim(rtrim(number_format((float)$activeOffer->discount, 2), '0'), '.') }}% OFF</span>@endif
                            <div class="shop-product-image">
                                @if($product->image && file_exists(public_path('images/'.$product->image)))
                                    <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->product_name }}" loading="lazy">
                                @else
                                    <i class="bi bi-cup-hot text-success" style="font-size:3.3rem"></i>
                                @endif
                            </div>
                            <div class="p-3 p-lg-3 d-flex flex-column flex-grow-1">
                                <div class="small text-success fw-semibold mb-1">{{ $product->category->category_name ?? 'Coffee Corner' }}</div>
                                <h3 class="h6 shop-product-name mb-2">{{ $product->product_name }}</h3>
                                <p class="small text-muted flex-grow-1 mb-3">{{ \Illuminate\Support\Str::limit($product->description, 76) }}</p>
                                <div class="mb-3">
                                    @if($activeOffer)
                                        <div class="shop-price">${{ number_format((float)$activeOffer->new_price, 2) }} <span class="shop-old-price">${{ number_format((float)$product->price, 2) }}</span></div>
                                        <div class="shop-save-note">You save ${{ number_format(max(0, (float)$product->price - (float)$activeOffer->new_price), 2) }}</div>
                                    @else
                                        <div class="shop-price">${{ number_format((float)$product->price, 2) }}</div>
                                    @endif
                                    <div class="small text-muted mt-1">{{ (int)$product->qty }} available</div>
                                </div>
                                <form method="POST" action="{{ route('web.cart.add', $product->product_id) }}" class="mt-auto">@csrf<button class="shop-add-btn w-100" type="submit"><i class="bi bi-bag-plus me-1"></i>Add to cart</button></form>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12"><div class="bg-white border rounded-4 text-center py-5"><i class="bi bi-cup-hot text-success fs-1"></i><h3 class="h5 fw-bold mt-3">The menu is getting ready</h3><p class="text-muted mb-0">No active products are available right now.</p></div></div>
                @endforelse
            </div>
            <div class="mt-4 d-flex justify-content-center">{{ $products->links() }}</div>
        </section>
    </div>
</section>
@endsection
