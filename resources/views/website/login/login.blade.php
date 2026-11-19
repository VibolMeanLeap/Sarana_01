@extends('website.component.master')
@section('web_title', 'Sign in')
@section('content')
<style>
    .sk-auth-shell{width:100%;max-width:470px;margin:0 auto;padding:8px 0 30px}
    .sk-auth-tabs{display:flex;gap:6px;padding:4px;background:#f1f5f1;border-radius:13px;margin-bottom:22px}
    .sk-auth-tabs a{flex:1;text-align:center;padding:10px;border-radius:10px;text-decoration:none;font-size:12px;font-weight:800;color:#718075}
    .sk-auth-tabs a.active{background:#fff;color:#176b4b;box-shadow:0 3px 10px #1b4a2b0d}
    .sk-auth-title{font:800 27px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.8px;color:#203128}
    .sk-auth-sub{font-size:12px;color:#7d897f;line-height:1.7}
    .sk-auth-google{width:100%;min-height:52px;display:flex;align-items:center;justify-content:center;gap:12px;padding:12px 16px;border:1px solid #dce4de;border-radius:13px;background:#fff;color:#29352d;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 5px 16px rgba(24,63,40,.04);transition:.18s}
    .sk-auth-google:hover{background:#f8fcf9;border-color:#8fb59b;color:#176b4b;transform:translateY(-1px);box-shadow:0 8px 20px rgba(24,63,40,.09)}
    .sk-auth-google svg{width:20px;height:20px;flex:0 0 20px}
    .sk-auth-divider{display:flex;align-items:center;gap:12px;color:#9aa49c;font-size:10px;font-weight:800;letter-spacing:.12em;margin:22px 0 16px}
    .sk-auth-divider:before,.sk-auth-divider:after{content:"";height:1px;background:#e8ede8;flex:1}
    .sk-auth-label{display:block;font-size:11px;font-weight:800;color:#4a594e;margin-bottom:7px}
    .sk-auth-input{height:49px!important;border:1px solid #e0e8e1!important;border-radius:11px!important;background:#fbfcfb!important;padding:10px 14px!important;box-shadow:none!important;font-size:13px!important}
    .sk-auth-input:focus{border-color:#6eaa83!important;box-shadow:0 0 0 4px #23805215!important;background:#fff!important}
    .sk-auth-submit{height:49px;border:0;border-radius:11px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800}
    .sk-auth-submit:hover{background:#104d38;color:#fff}
    .sk-auth-foot{font-size:11px;color:#89958c}
    .sk-auth-alert{font-size:12px;border-radius:11px}
</style>
<div class="sk-auth-shell">
    <nav class="sk-auth-tabs" aria-label="Customer account pages">
        <a href="{{ route('webLogin') }}" class="active" aria-current="page">Sign in</a>
        <a href="{{ route('webRegister') }}">Create account</a>
    </nav>

    <div class="text-center mb-4">
        <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:58px;height:58px;background:#eaf4ed;color:#176b4b;font-size:23px"><i class="bi bi-cup-hot"></i></span>
        <h1 class="sk-auth-title mb-2">Welcome to SK Coffee</h1>
        <p class="sk-auth-sub mb-0">Sign in to follow your orders, save your details and enjoy a smoother checkout.</p>
    </div>

    @if (session('status'))<div class="alert alert-success sk-auth-alert" role="status">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger sk-auth-alert" role="alert">{{ session('error') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger sk-auth-alert" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if (config('services.google.client_id') && config('services.google.client_secret'))
        <a href="{{ route('google.redirect', ['audience' => 'customer']) }}" class="sk-auth-google">
            <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.88c-.58 2.96-2.25 5.48-4.72 7.18l7.62 5.91c4.45-4.11 7.2-10.17 7.2-17.56z"/><path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.89.93 7.58 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.9-5.89l-7.62-5.91c-2.12 1.42-4.84 2.26-8.28 2.26-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            <span>Continue with Google</span>
        </a>
        <p class="text-center sk-auth-foot mt-2 mb-0">New to SK Coffee? Google can create your customer account.</p>
    @endif

    <div class="sk-auth-divider">OR USE EMAIL / PHONE</div>
    <form action="{{ route('login') }}" method="post" data-login-lockout data-login-portal="customer"
        data-login-lock-seconds="{{ (int) session('login_lock_seconds', 0) }}"
        data-login-wait-label="{{ app()->getLocale() === 'km' ? 'សូមរង់ចាំ' : 'Please wait' }}">
        @csrf
        @include('partials.login-lockout-script')
        <div class="mb-3">
            <label class="sk-auth-label" for="customer-identity">Email address or phone number</label>
            <input id="customer-identity" type="text" class="form-control sk-auth-input @error('phone') is-invalid @enderror"
                value="{{ old('phone') }}" name="phone" placeholder="name@example.com or 012 345 678" autocomplete="username" required>
            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="sk-auth-label mb-0" for="customer-password">Password</label>
                <a href="{{ route('web.password.request') }}" class="small fw-bold text-success text-decoration-none">Forgot password?</a>
            </div>
            <input id="customer-password" type="password" class="form-control sk-auth-input" name="password" placeholder="Enter your password" autocomplete="current-password" required>
        </div>
        <div class="form-check mb-4"><input class="form-check-input" type="checkbox" value="1" id="customer-remember" name="remember"><label class="form-check-label sk-auth-foot" for="customer-remember">Keep me signed in</label></div>
        <button type="submit" class="btn sk-auth-submit w-100">Sign in with password <i class="bi bi-arrow-right ms-2"></i></button>
    </form>
    <div class="text-center sk-auth-foot mt-4"><i class="bi bi-shield-check me-1 text-success"></i>Your SK Coffee account and order details stay protected.</div>
</div>
@endsection
