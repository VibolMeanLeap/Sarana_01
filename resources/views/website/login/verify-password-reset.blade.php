@extends('website.component.master')
@section('web_title', 'Verify password reset')
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
    @php($isEmail = ($deliveryMethod ?? 'telegram') === 'email')
    <div class="text-center mb-4">
        <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:54px;height:54px;background:#eaf4ed;color:#176b4b;font-size:22px"><i class="bi {{ $isEmail ? 'bi-envelope-check' : 'bi-telegram' }}"></i></span>
        <h1 class="recovery-title mb-2">Verify and reset password</h1>
        <p class="recovery-sub mb-0">{{ $isEmail ? 'Enter the six-digit code sent to your registered email address.' : 'Enter the six-digit code sent to your verified Telegram chat.' }}</p>
    </div>
    @if (session('recovery_notice'))
        <div class="alert alert-info py-2 small" role="status">{{ session('recovery_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('web.password.update') }}">
        @csrf
        <div class="mb-3">
            <label class="recovery-label" for="recovery-otp">Six-digit {{ $isEmail ? 'email' : 'Telegram' }} code</label>
            <input id="recovery-otp" class="form-control recovery-input text-center fw-bold" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" placeholder="000000" required autofocus>
        </div>
        <div class="mb-3">
            <label class="recovery-label" for="recovery-password">New password</label>
            <input id="recovery-password" class="form-control recovery-input" type="password" name="password" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="mb-4">
            <label class="recovery-label" for="recovery-password-confirmation">Confirm new password</label>
            <input id="recovery-password-confirmation" class="form-control recovery-input" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="recovery-note mb-4"><i class="bi bi-shield-check me-1"></i>The code expires after five minutes, can be used once, and allows up to five attempts. Never share it with anyone.</div>
        <button class="btn recovery-submit w-100" type="submit">Save new password <i class="bi bi-check2 ms-2"></i></button>
    </form>
    <div class="d-flex justify-content-between gap-3 mt-4 small">
        <a href="{{ route('web.password.request') }}" class="text-success fw-bold text-decoration-none">Choose another recovery method</a>
        <a href="{{ route('webLogin') }}" class="text-secondary fw-semibold text-decoration-none">Back to sign in</a>
    </div>
@endsection
