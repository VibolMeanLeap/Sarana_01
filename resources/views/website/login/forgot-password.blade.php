@extends('website.component.master')
@section('web_title', 'Forgot password')
@section('content')
    <style>
        .recovery-title{font:800 25px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.7px;color:#203128}
        .recovery-sub{font-size:12px;color:#7d897f;line-height:1.7}
        .recovery-label{font-size:11px;font-weight:800;color:#4a594e;margin-bottom:7px}
        .recovery-input{height:49px;border:1px solid #e0e8e1;border-radius:11px;background:#fbfcfb;font-size:13px}
        .recovery-input:focus{border-color:#6eaa83;box-shadow:0 0 0 4px #23805215}
        .recovery-submit{height:49px;border:0;border-radius:11px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800}
        .recovery-note{border:1px solid #dcebe0;background:#f4faf5;border-radius:14px;padding:13px;color:#45614f;font-size:11px;line-height:1.65}
    </style>
    <div class="text-center mb-4">
        <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:54px;height:54px;background:#eaf4ed;color:#176b4b;font-size:22px"><i class="bi bi-shield-lock"></i></span>
        <h1 class="recovery-title mb-2">Forgot your password?</h1>
        <p class="recovery-sub mb-0">Enter the phone number registered with SK Coffee. We'll send a one-time code to the Telegram account already linked to your customer account.</p>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('web.password.send-code') }}">
        @csrf
        <div class="mb-3">
            <label class="recovery-label" for="recovery-phone">Registered phone number</label>
            <input id="recovery-phone" class="form-control recovery-input" type="tel" name="phone" value="{{ old('phone') }}" placeholder="012 345 678" autocomplete="tel" required maxlength="30">
        </div>
        <div class="recovery-note mb-4"><i class="bi bi-telegram me-1"></i>For your protection, SK Coffee sends recovery codes only to the Telegram account verified when you registered. We never ask you to enter a different Telegram username or chat ID.</div>
        <button class="btn recovery-submit w-100" type="submit">Send recovery code <i class="bi bi-arrow-right ms-2"></i></button>
    </form>
    <div class="text-center mt-4 small"><a href="{{ route('webLogin') }}" class="text-success fw-bold text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></div>
@endsection
