@extends('layout.web')
@section('web_title', $pageTitle.' · '.($shopSettings?->shop_name ?: 'Coffee Corner'))
@section('content')
<style>
    .store-info-page{background:#f6f8f6;padding:42px 0 76px;min-height:65vh}
    .store-info-wrap{max-width:1080px}
    .store-info-hero{position:relative;overflow:hidden;border-radius:28px;padding:clamp(28px,5vw,54px);color:#fff;background:radial-gradient(circle at 88% 18%,rgba(229,190,125,.20),transparent 26%),linear-gradient(122deg,#10291e,#1c5038 62%,#2e6248);box-shadow:0 22px 55px rgba(17,48,31,.13)}
    .store-info-hero:after{content:"";position:absolute;width:330px;height:330px;right:-105px;bottom:-220px;border:1px solid rgba(255,255,255,.18);border-radius:50%;box-shadow:0 0 0 32px rgba(255,255,255,.035),0 0 0 68px rgba(255,255,255,.025)}
    .store-info-kicker{color:#e7c990;font-size:.72rem;letter-spacing:.17em;text-transform:uppercase;font-weight:850}
    .store-info-title{font-size:clamp(2rem,5vw,3.35rem);font-weight:900;line-height:1.04;letter-spacing:-.055em;max-width:760px}
    .store-info-description{max-width:690px;color:rgba(255,255,255,.76);font-size:1.02rem;line-height:1.8}
    .store-info-icon{width:64px;height:64px;border-radius:20px;display:grid;place-items:center;background:rgba(246,217,165,.14);border:1px solid rgba(246,217,165,.25);color:#f4d59e;font-size:1.75rem;margin-bottom:24px}
    .store-info-notice{margin-top:24px;padding:17px 19px;border:1px solid rgba(245,215,165,.26);border-radius:17px;background:rgba(255,255,255,.09);color:#fff;max-width:780px;line-height:1.65}
    .store-info-card{height:100%;padding:25px;border:1px solid #e6ede7;border-radius:22px;background:#fff;box-shadow:0 10px 28px rgba(26,55,37,.045)}
    .store-info-card-icon{width:46px;height:46px;border-radius:15px;background:#edf6ef;color:#187448;display:grid;place-items:center;font-size:1.25rem;margin-bottom:17px}
    .store-info-card h2{font-size:1.03rem;font-weight:850;color:#183426;letter-spacing:-.02em}
    .store-info-card p{font-size:.9rem;line-height:1.75;color:#718078;margin:0}
    .store-info-bottom{border:1px solid #e7ede8;border-radius:22px;background:linear-gradient(120deg,#fff,#f8fbf8);padding:24px 27px}
    .store-info-bottom h2{font-weight:850;color:#193629;letter-spacing:-.03em}
    @media(max-width:575.98px){.store-info-page{padding-top:22px}.store-info-hero{border-radius:22px}.store-info-card{padding:21px}}
</style>
<section class="store-info-page">
    <div class="container store-info-wrap">
        <header class="store-info-hero mb-4 mb-lg-5">
            <div class="store-info-icon"><i class="bi {{ $heroIcon }}"></i></div>
            <div class="store-info-kicker mb-3">{{ $eyebrow }}</div>
            <h1 class="store-info-title mb-3">{{ $pageTitle }}</h1>
            <p class="store-info-description mb-0">{{ $pageDescription }}</p>
            @if(!empty($notice))
                <div class="store-info-notice"><i class="bi bi-info-circle me-2" style="color:#f0d29c"></i>{{ $notice }}</div>
            @endif
        </header>

        <div class="row g-3 g-lg-4">
            @foreach($cards as $card)
                <div class="col-md-6 col-lg-4">
                    <article class="store-info-card">
                        <div class="store-info-card-icon"><i class="bi {{ $card['icon'] }}"></i></div>
                        <h2 class="mb-2">{{ $card['title'] }}</h2>
                        <p>{{ $card['body'] }}</p>
                    </article>
                </div>
            @endforeach
        </div>

        @if(isset($bottomTitle))
            <div class="store-info-bottom mt-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div><h2 class="h5 mb-1">{{ $bottomTitle }}</h2><p class="small text-muted mb-0">{{ $bottomText }}</p></div>
                <a href="{{ route('web.contact') }}" class="btn btn-success rounded-pill px-4 py-3 fw-bold flex-shrink-0"><i class="bi bi-chat-dots me-2"></i>Contact the shop</a>
            </div>
        @else
            <div class="text-center mt-5"><a href="{{ route('web.shop') }}" class="btn btn-success rounded-pill px-4 py-3 fw-bold"><i class="bi bi-bag me-2"></i>Explore the menu</a></div>
        @endif
    </div>
</section>
@endsection
