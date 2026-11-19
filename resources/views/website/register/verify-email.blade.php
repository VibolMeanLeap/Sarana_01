@extends('website.component.master')
@section('web_title', 'Verify your email')
@section('content')
    <style>
        .email-verify-shell{max-width:470px;margin:0 auto;padding:12px 0 28px}
        .email-verify-title{font:800 25px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.7px;color:#203128}
        .email-verify-sub{font-size:12px;color:#7d897f;line-height:1.7}
        .email-verify-input{height:56px;border:1px solid #dfe9e1;border-radius:12px;background:#fbfdfb;font-size:24px;letter-spacing:10px;font-weight:800;color:#176b4b}
        .email-verify-input:focus{border-color:#6eaa83;box-shadow:0 0 0 4px #23805215}
        .email-verify-submit{height:49px;border:0;border-radius:11px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800}
        .email-verify-note{padding:13px;border:1px solid #dcebe0;background:#f4faf5;border-radius:14px;color:#45614f;font-size:11px;line-height:1.65}
    </style>
    <div class="email-verify-shell">
        <div class="text-center mb-4">
            <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:58px;height:58px;background:#eaf4ed;color:#176b4b;font-size:23px"><i class="bi bi-envelope-check"></i></span>
            <h1 class="email-verify-title mb-2">Check your inbox</h1>
            <p class="email-verify-sub mb-0">We sent a six-digit verification code to <strong class="text-dark">{{ $maskedEmail }}</strong>. Enter it below to activate your account.</p>
        </div>
        @if (session('status'))<div class="alert alert-success py-2 small" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('web.register.email.verify.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-bold small" for="email-registration-otp">Email verification code</label>
                <input id="email-registration-otp" class="form-control email-verify-input text-center" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" placeholder="000000" required autofocus>
                @error('otp')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="email-verify-note mb-4"><i class="bi bi-shield-check me-1"></i>Your code expires in five minutes. It can be used once and allows up to five attempts. Check your spam folder if you cannot find the message.</div>
            <button class="btn email-verify-submit w-100" type="submit">Verify email and create account <i class="bi bi-arrow-right ms-2"></i></button>
        </form>
        <form method="POST" action="{{ route('web.register.email.resend') }}" class="text-center mt-3">
            @csrf
            <button class="btn btn-link text-success fw-bold text-decoration-none small" type="submit">Didn't receive the code? Send a new one</button>
        </form>
        <div class="text-center mt-3 small"><a href="{{ route('webRegister') }}" class="text-secondary fw-semibold text-decoration-none">Back to registration options</a></div>
    </div>
@endsection
