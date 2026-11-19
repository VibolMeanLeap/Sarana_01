@extends('website.component.master')
@section('web_title', 'Reset customer password')
@section('content')
    <style>
        .recovery-title{font:800 25px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.7px;color:#203128}
        .recovery-sub{font-size:12px;color:#7d897f;line-height:1.7}
        .recovery-label{font-size:11px;font-weight:800;color:#4a594e;margin-bottom:7px}
        .recovery-input{height:49px;border:1px solid #e0e8e1;border-radius:11px;background:#fbfcfb;font-size:13px}
        .recovery-input:focus{border-color:#6eaa83;box-shadow:0 0 0 4px #23805215}
        .recovery-submit{height:49px;border:0;border-radius:11px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800}
        .recovery-note{border:1px solid #dcebe0;background:#f4faf5;border-radius:14px;padding:13px;color:#45614f;font-size:11px;line-height:1.65}
        .recovery-methods{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin:18px 0}
        .recovery-method{display:flex;gap:10px;align-items:center;border:1px solid #e1e9e2;border-radius:12px;padding:12px;background:#fff;cursor:pointer}
        .recovery-method:has(input:checked){border-color:#5c9e73;background:#f3faf5;box-shadow:0 0 0 3px #176b4b10}
        .recovery-method input{accent-color:#176b4b}
        .recovery-method i{font-size:20px;color:#176b4b}
        .recovery-method strong{display:block;font-size:11px;color:#294235}
        .recovery-method small{display:block;font-size:9px;color:#829087;margin-top:2px}
    </style>
    <div class="text-center mb-4">
        <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:54px;height:54px;background:#eaf4ed;color:#176b4b;font-size:22px"><i class="bi bi-shield-lock"></i></span>
        <h1 class="recovery-title mb-2">Forgot your password?</h1>
        <p class="recovery-sub mb-0">Choose where you want to receive your one-time password reset code.</p>
    </div>
    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
    @endif
    <form id="customer-recovery-form" method="POST" action="{{ route('web.password.send-code') }}">
        @csrf
        <div class="recovery-methods" role="radiogroup" aria-label="Password recovery method">
            <label class="recovery-method">
                <input type="radio" name="method" value="email" checked>
                <i class="bi bi-envelope-check"></i><span><strong>Email OTP</strong><small>Receive a code by email</small></span>
            </label>
            <label class="recovery-method">
                <input type="radio" name="method" value="telegram">
                <i class="bi bi-telegram"></i><span><strong>Telegram OTP</strong><small>Use your verified Telegram</small></span>
            </label>
        </div>
        <div class="mb-3" data-recovery-field="email">
            <label class="recovery-label" for="recovery-email">Registered email address</label>
            <input id="recovery-email" class="form-control recovery-input" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="email" maxlength="255">
        </div>
        <div class="mb-3" data-recovery-field="telegram" hidden>
            <label class="recovery-label" for="recovery-phone">Registered phone number</label>
            <input id="recovery-phone" class="form-control recovery-input" type="tel" name="phone" value="{{ old('phone') }}" placeholder="012 345 678" autocomplete="tel" maxlength="30" disabled>
        </div>
        <div class="recovery-note mb-4" data-recovery-note="email"><i class="bi bi-envelope-check me-1"></i>Email recovery sends a code to the address already stored on your active customer account. You can use this even if your account was originally verified through Telegram.</div>
        <div class="recovery-note mb-4" data-recovery-note="telegram" hidden><i class="bi bi-telegram me-1"></i>Telegram recovery sends a code only to the Telegram account previously verified and linked to your customer profile. We never accept a chat ID entered in this form.</div>
        <button class="btn recovery-submit w-100" type="submit">Send password reset code <i class="bi bi-arrow-right ms-2"></i></button>
    </form>
    <div class="text-center mt-4 small"><a href="{{ route('webLogin') }}" class="text-success fw-bold text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a></div>
    <script>
        (() => {
            const radios = [...document.querySelectorAll('input[name="method"]')];
            const fields = [...document.querySelectorAll('[data-recovery-field]')];
            const notes = [...document.querySelectorAll('[data-recovery-note]')];
            const update = () => {
                const method = radios.find(input => input.checked)?.value || 'email';
                fields.forEach(panel => {
                    const active = panel.dataset.recoveryField === method;
                    panel.hidden = !active;
                    const input = panel.querySelector('input');
                    input.disabled = !active;
                    input.required = active;
                });
                notes.forEach(note => note.hidden = note.dataset.recoveryNote !== method);
            };
            radios.forEach(radio => radio.addEventListener('change', update));
            update();
        })();
    </script>
@endsection
