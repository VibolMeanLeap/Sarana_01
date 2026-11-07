@extends('login_resig.main')
@section('page_title', 'Staff sign in')
@section('content')
@php
    $shopName = $shopSettings?->shop_name ?: 'Coffee Corner';
    $logoPath = $shopSettings?->logo ? public_path('uploads/'.$shopSettings->logo) : null;
@endphp
<style>
    html,body{min-height:100%;margin:0}body{font-family:'DM Sans',sans-serif!important;background:#0d2119!important}.staff-auth{min-height:100vh;display:grid;grid-template-columns:minmax(0,1.1fr) minmax(420px,.9fr);background:#f5f7f3}.auth-showcase{position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;padding:clamp(34px,5vw,76px);color:#fff;background:radial-gradient(circle at 14% 14%,rgba(103,184,130,.25),transparent 30%),radial-gradient(circle at 90% 85%,rgba(188,153,93,.15),transparent 32%),linear-gradient(145deg,#0b3023,#14573d 58%,#1c6848)}.auth-showcase:before,.auth-showcase:after{content:"";position:absolute;border:1px solid rgba(255,255,255,.12);border-radius:50%;pointer-events:none}.auth-showcase:before{width:560px;height:560px;right:-230px;top:-190px}.auth-showcase:after{width:390px;height:390px;right:-110px;top:-105px}.auth-showcase>*{position:relative;z-index:1}.auth-brand{display:flex;align-items:center;gap:13px;color:#fff;text-decoration:none}.auth-brand-mark{width:48px;height:48px;border-radius:15px;display:grid;place-items:center;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.19);overflow:hidden;font-size:22px}.auth-brand-mark img{width:100%;height:100%;object-fit:contain;background:#fff}.auth-brand-name{font:800 17px 'Manrope',sans-serif;letter-spacing:-.3px}.auth-brand-sub{color:rgba(255,255,255,.64);font-size:10px;letter-spacing:1.15px;text-transform:uppercase;margin-top:2px}.showcase-copy{max-width:620px;padding:50px 0}.showcase-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(255,255,255,.2);border-radius:30px;background:rgba(255,255,255,.08);color:#d6efdf;font-size:10px;font-weight:800;letter-spacing:1px;text-transform:uppercase}.showcase-copy h1{font:800 clamp(40px,5.3vw,68px)/1.02 'Manrope',sans-serif;letter-spacing:-2.5px;margin:23px 0 17px}.showcase-copy h1 span{color:#b6dfc2}.showcase-copy p{max-width:470px;color:rgba(255,255,255,.72);font-size:15px;line-height:1.8}.showcase-features{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;max-width:650px}.showcase-feature{padding:15px 13px;border-radius:15px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.07);backdrop-filter:blur(8px)}.showcase-feature i{font-size:18px;color:#c3e8ce}.showcase-feature strong{display:block;font-size:11px;margin-top:10px}.showcase-feature span{display:block;color:rgba(255,255,255,.57);font-size:10px;margin-top:3px}.showcase-foot{font-size:11px;color:rgba(255,255,255,.5)}.auth-panel{display:flex;align-items:center;justify-content:center;padding:clamp(24px,4vw,64px);background:radial-gradient(circle at 100% 0%,#e7efe7,transparent 34%),#f8faf7}.auth-card{width:min(100%,440px);padding:clamp(27px,3vw,42px);background:rgba(255,255,255,.94);border:1px solid #e5ebe5;border-radius:25px;box-shadow:0 24px 70px rgba(24,55,34,.10)}.auth-eyebrow{font-size:10px;letter-spacing:1.25px;text-transform:uppercase;font-weight:800;color:#28794f}.auth-card h2{font:800 30px 'Manrope',sans-serif;letter-spacing:-1.1px;color:#1c2d22;margin:9px 0 7px}.auth-card .lead{font-size:12px;line-height:1.7;color:#7b887e;margin-bottom:25px}.auth-label{display:block;font-size:11px;font-weight:800;color:#445248;margin-bottom:8px}.auth-field{position:relative}.auth-field .field-icon{position:absolute;left:15px;top:50%;transform:translateY(-50%);font-size:16px;color:#92a096;z-index:2}.auth-input{height:51px!important;border:1px solid #e1e9e2!important;border-radius:12px!important;padding:10px 45px 10px 43px!important;background:#fbfcfa!important;font-size:13px!important;box-shadow:none!important}.auth-input:focus{border-color:#67a981!important;background:#fff!important;box-shadow:0 0 0 4px rgba(39,128,82,.10)!important}.auth-input::placeholder{color:#a7b0a9}.auth-eye{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;width:36px;height:36px;border-radius:9px;color:#7d8a80}.auth-eye:hover{background:#eef5ef;color:#176b4b}.auth-submit{height:52px;border:0;border-radius:12px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-weight:800;font-size:13px;box-shadow:0 10px 20px rgba(23,107,75,.18);transition:.2s}.auth-submit:hover{transform:translateY(-1px);box-shadow:0 13px 24px rgba(23,107,75,.25);background:linear-gradient(110deg,#125a3e,#176b4b)}.auth-divider{display:flex;align-items:center;gap:12px;color:#a0aaa2;font-size:10px;margin:21px 0}.auth-divider:before,.auth-divider:after{content:"";height:1px;background:#edf0ed;flex:1}.auth-public-link{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;border-radius:11px;border:1px solid #e7ece7;color:#496052;text-decoration:none;font-size:11px;font-weight:700}.auth-public-link:hover{background:#f2f7f2;color:#176b4b}.auth-error{border-radius:11px;font-size:11px}.auth-remember{font-size:11px;color:#7e8a81}.auth-remember .form-check-input:checked{background-color:#176b4b;border-color:#176b4b}
    @media(max-width:991.98px){.staff-auth{grid-template-columns:1fr}.auth-showcase{display:none}.auth-panel{min-height:100vh;padding:22px}.auth-card{width:min(100%,460px)}}
    @media(max-width:575.98px){.auth-panel{padding:14px}.auth-card{padding:26px 21px;border-radius:20px}.auth-card h2{font-size:26px}}
</style>
<div class="staff-auth">
    <section class="auth-showcase">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <a class="auth-brand" href="{{ route('showlogin') }}">
                <span class="auth-brand-mark">@if($logoPath && file_exists($logoPath))<img src="{{ asset('uploads/'.$shopSettings->logo) }}" alt="{{ $shopName }}">@else<i class="bi bi-cup-hot-fill"></i>@endif</span>
                <span><span class="auth-brand-name d-block">{{ $shopName }}</span><span class="auth-brand-sub d-block">Store management suite</span></span>
            </a>
            <span class="small text-white-50 d-none d-xl-inline">STAFF ACCESS</span>
        </div>
        <div class="showcase-copy">
            <span class="showcase-pill"><i class="bi bi-stars"></i> Welcome to your workspace</span>
            <h1>Great service starts <span>behind the counter.</span></h1>
            <p>One calm place to manage orders, inventory, payments and the people who make every customer feel welcome.</p>
            <div class="showcase-features">
                <div class="showcase-feature"><i class="bi bi-speedometer2"></i><strong>Live overview</strong><span>Know your store at a glance</span></div>
                <div class="showcase-feature"><i class="bi bi-cup-hot"></i><strong>Smarter service</strong><span>Keep every order moving</span></div>
                <div class="showcase-feature"><i class="bi bi-shield-check"></i><strong>Role protected</strong><span>The right tools for each role</span></div>
            </div>
        </div>
        <div class="showcase-foot"><i class="bi bi-shield-lock me-1"></i> Secure staff portal <span class="mx-2">·</span> {{ $shopSettings?->address ?: 'Coffee shop operations' }}</div>
    </section>
    <section class="auth-panel">
        <div class="auth-card">
            <div class="auth-eyebrow"><i class="bi bi-lock-fill me-1"></i> Administrator & cashier portal</div>
            <h2>Welcome back.</h2>
            <p class="lead">Sign in with your staff account to continue to your workspace.</p>

            @if(session('error'))<div class="alert alert-danger auth-error py-2"><i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}</div>@endif
            @if(session('success'))<div class="alert alert-success auth-error py-2">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger auth-error py-2">{{ $errors->first() }}</div>@endif

            <form action="{{ route('process_login') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="login-username" class="auth-label">Staff username</label>
                    <div class="auth-field"><i class="bi bi-person field-icon"></i><input id="login-username" value="{{ old('name') }}" type="text" name="name" class="form-control auth-input" placeholder="Enter your username" autocomplete="username" required autofocus></div>
                </div>
                <div class="mb-3">
                    <label for="password-input" class="auth-label">Password</label>
                    <div class="auth-field"><i class="bi bi-key field-icon"></i><input id="password-input" type="password" name="password" class="form-control auth-input" placeholder="Enter your password" autocomplete="current-password" required><button class="auth-eye" type="button" id="toggle-password" aria-label="Show password"><i class="bi bi-eye" id="toggle-icon"></i></button></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4"><div class="form-check auth-remember"><input class="form-check-input" type="checkbox" value="1" id="remember" name="remember"><label class="form-check-label" for="remember">Remember this device</label></div><span class="small text-muted"><i class="bi bi-person-badge me-1"></i>Staff only</span></div>
                <button type="submit" class="btn auth-submit w-100">Sign in to workspace <i class="bi bi-arrow-right ms-2"></i></button>
            </form>
            <div class="auth-divider">OR EXPLORE THE SHOP</div>
            <a href="{{ route('web.home') }}" class="auth-public-link"><i class="bi bi-globe2"></i> Visit {{ $shopName }} customer website <i class="bi bi-arrow-up-right ms-auto"></i></a>
            <div class="text-center small text-muted mt-4">Protected access for authorized {{ $shopName }} staff.</div>
        </div>
    </section>
</div>
<script>
(() => {
    const input = document.getElementById('password-input');
    const button = document.getElementById('toggle-password');
    const icon = document.getElementById('toggle-icon');
    button?.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();
</script>
@endsection
