@extends('layout.web')
@section('web_title', 'Contact us · '.($shopSettings?->shop_name ?: 'Coffee Corner'))
@section('content')
<style>
    .store-contact-page{background:#f6f8f6;padding:40px 0 76px;min-height:65vh}
    .store-contact-hero{border-radius:27px;padding:clamp(27px,4vw,46px);background:radial-gradient(circle at 85% 10%,rgba(237,198,132,.2),transparent 25%),linear-gradient(120deg,#10291e,#1b4d35 68%,#315c43);color:#fff;position:relative;overflow:hidden}
    .store-contact-hero h1{font-size:clamp(2rem,4vw,3rem);letter-spacing:-.05em;font-weight:900;line-height:1.06}
    .store-contact-hero p{max-width:650px;color:rgba(255,255,255,.76);line-height:1.75;margin:0}
    .store-contact-kicker{color:#e8c992;letter-spacing:.16em;text-transform:uppercase;font-size:.72rem;font-weight:850}
    .store-contact-card{background:#fff;border:1px solid #e7eee8;border-radius:23px;box-shadow:0 12px 34px rgba(24,56,38,.045);padding:clamp(20px,3vw,32px);height:100%}
    .store-contact-title{font-weight:850;letter-spacing:-.035em;color:#173426}
    .store-contact-label{font-size:.78rem;font-weight:800;color:#53645a;margin-bottom:7px}
    .store-contact-form .form-control,.store-contact-form .form-select{border:1px solid #dfe8e1;border-radius:12px;padding:12px 14px;min-height:48px;box-shadow:none}
    .store-contact-form textarea.form-control{min-height:145px;resize:vertical}
    .store-contact-form .form-control:focus,.store-contact-form .form-select:focus{border-color:#4a9b70;box-shadow:0 0 0 3px rgba(38,132,79,.10)}
    .store-contact-method{display:flex;gap:13px;padding:16px 0;border-bottom:1px solid #edf1ed}
    .store-contact-method:last-child{border-bottom:0}
    .store-contact-method-icon{width:43px;height:43px;flex:0 0 43px;border-radius:14px;background:#edf6ef;color:#187448;display:grid;place-items:center;font-size:1.15rem}
    .store-contact-method strong{display:block;color:#243a2d;font-size:.88rem}
    .store-contact-method span,.store-contact-method a{font-size:.84rem;color:#77837b;text-decoration:none;line-height:1.55;overflow-wrap:anywhere}
    .store-contact-method a:hover{color:#187448}
    .store-contact-submit{background:#176b43;border:0;border-radius:13px;color:white;font-weight:850;padding:14px 20px}
    .store-contact-submit:hover{background:#105535;color:#fff}
    .store-honeypot{position:absolute!important;left:-10000px!important;width:1px!important;height:1px!important;overflow:hidden!important}
</style>
<section class="store-contact-page">
    <div class="container" style="max-width:1120px">
        <header class="store-contact-hero mb-4 mb-lg-5">
            <div class="store-contact-kicker mb-3"><i class="bi bi-chat-heart me-2"></i>Customer care</div>
            <h1 class="mb-3">We're here to help.</h1>
            <p>Questions about a product, payment, delivery or an existing order? Send us a message and our team will review your enquiry.</p>
        </header>

        @if(session('contact_success'))
            <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4" role="status"><i class="bi bi-check-circle-fill me-2"></i>{{ session('contact_success') }}
                @if(session('contact_message_id'))<a class="d-inline-block fw-bold ms-2" href="{{ route('web.inbox.show', session('contact_message_id')) }}">Open your conversation <i class="bi bi-arrow-right"></i></a>@endif
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger rounded-4 mb-4"><strong>Please check the form.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="store-contact-card">
                    <div class="store-contact-kicker text-success mb-2">Send a message</div>
                    <h2 class="h3 store-contact-title mb-2">How can we help?</h2>
                    <p class="small text-muted mb-4">Fields marked with * are required. Please do not include payment passwords, PINs or other sensitive credentials.</p>
                    @if(auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer')
                        <div class="alert alert-success border-0 rounded-3 small"><i class="bi bi-inbox me-2"></i>Because you are signed in, the shop's acknowledgement and replies will appear in <a href="{{ route('web.inbox') }}" class="fw-bold">your account inbox</a>.</div>
                    @else
                        <div class="alert alert-light border rounded-3 small"><i class="bi bi-info-circle me-2 text-success"></i>To read replies in your Store Account inbox, please <a href="{{ route('webLogin') }}" class="fw-bold">sign in</a> before sending your enquiry. Guest enquiries are still delivered to the shop, but cannot be linked to an online inbox.</div>
                    @endif
                    <form class="store-contact-form" method="POST" action="{{ route('web.contact.submit') }}" novalidate>
                        @csrf
                        <div class="store-honeypot" aria-hidden="true"><label for="website">Leave this field empty</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="store-contact-label" for="contact-name">Your name *</label><input class="form-control @error('name') is-invalid @enderror" id="contact-name" name="name" value="{{ old('name', auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer' ? auth()->user()->name : '') }}" maxlength="100" autocomplete="name" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="store-contact-label" for="contact-email">Email address *</label><input type="email" class="form-control @error('email') is-invalid @enderror" id="contact-email" name="email" value="{{ old('email', auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer' ? auth()->user()->email : '') }}" maxlength="190" autocomplete="email" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="store-contact-label" for="contact-phone">Phone number (optional)</label><input type="tel" class="form-control @error('phone') is-invalid @enderror" id="contact-phone" name="phone" data-optional value="{{ old('phone', auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer' ? auth()->user()->phone : '') }}" maxlength="40" autocomplete="tel">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-md-6"><label class="store-contact-label" for="contact-order">Order number (optional)</label><input class="form-control @error('order_reference') is-invalid @enderror" id="contact-order" name="order_reference" data-optional value="{{ old('order_reference') }}" maxlength="40" placeholder="e.g. 1042">@error('order_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label class="store-contact-label" for="contact-topic">What is this about? *</label><select class="form-select @error('topic') is-invalid @enderror" id="contact-topic" name="topic" required><option value="">Choose a topic</option>@foreach(['order'=>'Order enquiry','delivery'=>'Delivery & collection','payment'=>'Payment & refund','product'=>'Product information','feedback'=>'Feedback','other'=>'Something else'] as $value=>$label)<option value="{{ $value }}" @selected(old('topic')===$value)>{{ $label }}</option>@endforeach</select>@error('topic')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label class="store-contact-label" for="contact-message">Your message *</label><textarea class="form-control @error('message') is-invalid @enderror" id="contact-message" name="message" minlength="15" maxlength="3000" placeholder="Tell us how we can help..." required>{{ old('message') }}</textarea><div class="d-flex justify-content-between mt-2"><small class="text-muted">Please include useful details so we can help faster.</small><small class="text-muted"><span id="contact-count">{{ mb_strlen(old('message', '')) }}</span>/3000</small></div>@error('message')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><button type="submit" class="store-contact-submit w-100"><i class="bi bi-send-fill me-2"></i>Send enquiry</button><p class="small text-muted text-center mt-3 mb-0">Your message is stored securely for the shop team. Signed-in customers can continue the conversation in My Account → Inbox. Do not share bank PINs or passwords.</p></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <aside class="store-contact-card">
                    <div class="store-contact-kicker text-success mb-2">Get in touch</div>
                    <h2 class="h4 store-contact-title mb-2">{{ $shopSettings?->shop_name ?: 'Our shop' }}</h2>
                    <p class="small text-muted mb-3">You can also reach the shop using the contact details in your Store Profile.</p>
                    @if($shopSettings?->tel)
                        <div class="store-contact-method"><span class="store-contact-method-icon"><i class="bi bi-telephone"></i></span><div><strong>Call the shop</strong><a href="tel:{{ preg_replace('/[^0-9+]/', '', $shopSettings->tel) }}">{{ $shopSettings->tel }}</a></div></div>
                    @endif
                    @if($shopSettings?->address)
                        <div class="store-contact-method"><span class="store-contact-method-icon"><i class="bi bi-geo-alt"></i></span><div><strong>Store address</strong><span>{{ $shopSettings->address }}</span></div></div>
                    @endif
                    <div class="store-contact-method"><span class="store-contact-method-icon"><i class="bi bi-clock-history"></i></span><div><strong>Order updates</strong><span>Signed-in customers can follow order and delivery progress from My Orders.</span></div></div>
                    <div class="rounded-4 p-3 mt-4" style="background:#f1f7f2"><div class="fw-bold text-success small mb-1"><i class="bi bi-lightbulb me-1"></i>Helpful tip</div><p class="small text-muted mb-0">For an existing order, include its order number in your message so our team can find it quickly.</p></div>
                    <a href="{{ auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer' ? route('web.inbox') : route('webLogin') }}" class="btn btn-outline-success rounded-pill w-100 py-2 fw-bold mt-3"><i class="bi bi-inbox me-2"></i>{{ auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer' ? 'Open Customer Inbox' : 'Sign in to your account' }}</a>
                </aside>
            </div>
        </div>
    </div>
</section>
<script>
(() => {
    const field = document.getElementById('contact-message');
    const counter = document.getElementById('contact-count');
    if (!field || !counter) return;
    field.addEventListener('input', () => { counter.textContent = String(field.value.length); });
})();
</script>
@endsection
