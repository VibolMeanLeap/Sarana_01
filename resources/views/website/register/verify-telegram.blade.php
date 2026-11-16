@extends('website.component.master')
@section('web_title', 'Verify with Telegram')
@section('content')
<style>
    .aura-verify-shell{max-width:540px;margin:0 auto;padding:12px 0 38px}
    .aura-verify-card{border:1px solid #e8eee9;border-radius:20px;background:#fff;padding:clamp(20px,4vw,32px);box-shadow:0 14px 45px rgba(28,61,39,.07)}
    .aura-verify-title{font:800 25px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.7px;color:#203128}
    .aura-verify-sub{font-size:12px;color:#7d897f;line-height:1.7}
    .aura-verify-step{display:flex;gap:12px;align-items:flex-start;padding:13px 0;border-bottom:1px solid #edf1ed}
    .aura-verify-step:last-child{border-bottom:0}
    .aura-verify-number{width:28px;height:28px;flex:0 0 28px;border-radius:50%;display:grid;place-items:center;background:#eaf4ed;color:#176b4b;font-size:12px;font-weight:800}
    .aura-verify-step strong{display:block;color:#35483b;font-size:12px;margin-bottom:3px}
    .aura-verify-step span{display:block;color:#829087;font-size:11px;line-height:1.55}
    .aura-telegram-button{height:48px;display:flex;align-items:center;justify-content:center;gap:9px;border-radius:11px;background:#229ed9;color:#fff;text-decoration:none;font-size:12px;font-weight:800}
    .aura-telegram-button:hover{background:#1689c1;color:#fff}
    .aura-otp-input{height:54px!important;text-align:center;letter-spacing:12px;padding-left:18px!important;font-size:21px!important;font-weight:800;border:1px solid #dce7df!important;border-radius:12px!important;box-shadow:none!important}
    .aura-otp-input:focus{border-color:#67a77e!important;box-shadow:0 0 0 4px #23805215!important}
    .aura-verify-submit{height:48px;border:0;border-radius:11px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800}
    .aura-verify-submit:hover{background:#104d38;color:#fff}
</style>
<div class="aura-verify-shell">
    <div class="aura-verify-card">
        <div class="text-center mb-4">
            <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:58px;height:58px;background:#eaf4ed;color:#176b4b;font-size:25px"><i class="bi bi-telegram"></i></span>
            <h1 class="aura-verify-title mb-2">Verify your Telegram</h1>
            <p class="aura-verify-sub mb-0">One final step before your SK Coffee customer account is activated.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 small">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="mb-4">
            <div class="aura-verify-step">
                <span class="aura-verify-number">1</span>
                <div><strong>Connect your Telegram account</strong><span>Open the secure, one-use link below. It expires after 15 minutes.</span></div>
            </div>
            <div class="aura-verify-step">
                <span class="aura-verify-number">2</span>
                <div><strong>Share your own phone number</strong><span>In the private bot chat, tap “Share my phone number”. We compare it with the number you entered on this website.</span></div>
            </div>
            <div class="aura-verify-step">
                <span class="aura-verify-number">3</span>
                <div><strong>Receive your OTP after a match</strong><span>The bot sends a six-digit code only when the Telegram phone number matches your registration.</span></div>
            </div>
            <div class="aura-verify-step">
                <span class="aura-verify-number">4</span>
                <div><strong>Enter your code here</strong><span>The code expires in five minutes and allows up to five attempts.</span></div>
            </div>
        </div>

        @php
            $telegramPhoneVerified = (bool) $registration->telegram_phone_verified_at;
            $otpReady = $telegramPhoneVerified && $registration->otp_hash && $registration->otp_expires_at?->isFuture();
        @endphp

        @if ($otpReady)
            <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill me-1"></i> Phone number confirmed. Check your Telegram messages for the six-digit code.</div>
        @elseif ($registration->telegram_chat_id && ! $telegramPhoneVerified)
            <div class="alert alert-info py-2 small"><i class="bi bi-phone me-1"></i> Telegram is connected. Now tap <strong>Share my phone number</strong> in the bot chat. No OTP is sent until the shared number matches <strong>{{ $registration->phone }}</strong>.</div>
        @elseif ($registration->telegram_chat_id)
            <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-circle-fill me-1"></i> Your code expired or reached its attempt limit. Generate a new Telegram link below to restart verification.</div>
        @else
            <a class="aura-telegram-button mb-3" href="{{ $telegramLink }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-telegram fs-5"></i> Open SK Coffee Telegram Bot</a>
        @endif

        @if ($otpReady)
            <form action="{{ route('web.register.telegram.verify.submit') }}" method="post">
                @csrf
                <label for="telegram-otp" class="form-label small fw-bold text-dark">Six-digit verification code</label>
                <input id="telegram-otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6" autocomplete="one-time-code" class="form-control aura-otp-input mb-3 @error('otp') is-invalid @enderror" placeholder="000000" required>
                @error('otp')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                <button type="submit" class="btn aura-verify-submit w-100">Verify and activate account <i class="bi bi-arrow-right ms-2"></i></button>
            </form>
        @else
            <div class="small text-muted text-center py-2">The OTP entry will appear here after your phone number is confirmed by Telegram.</div>
        @endif

        <div class="text-center mt-3">
            <form action="{{ route('web.register.telegram.refresh') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link text-success text-decoration-none small fw-semibold">The link or code expired? Generate a new Telegram link</button>
            </form>
        </div>
        <p class="text-center text-muted mt-3 mb-0" style="font-size:10px">This registration session expires after 30 minutes. Expired temporary details are removed by the Laravel scheduler.</p>
        @if (! $otpReady && ! $telegramPhoneVerified)
            <p class="text-center text-muted mt-2 mb-0" style="font-size:10px">This page checks Telegram verification automatically. Keep it open; the OTP field appears after your phone number matches.</p>
            <script>
                window.setTimeout(function () {
                    window.location.reload();
                }, 4000);
            </script>
        @endif
    </div>
</div>
@endsection
