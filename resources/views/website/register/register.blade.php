@extends('website.component.master')
@section('web_title', 'Create Customer Account')
@section('content')
    <style>
        .aura-register-shell{width:100%;max-width:560px;margin:0 auto;padding:8px 0 28px}
        .aura-register-tabs{display:flex;gap:6px;padding:4px;background:#f1f5f1;border-radius:12px;margin-bottom:18px}
        .aura-register-tabs a{flex:1;text-align:center;padding:9px 8px;border-radius:9px;text-decoration:none;font-size:12px;font-weight:800;color:#718075;transition:background .18s,color .18s}
        .aura-register-tabs a.active{background:#fff;color:#176b4b;box-shadow:0 2px 8px #1b4a2b0d}
        .aura-register-title{font:800 24px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.6px;color:#203128}
        .aura-register-sub{font-size:12px;color:#7d897f;line-height:1.6;max-width:390px;margin-inline:auto}
        .aura-register-label{display:block;font-size:11px;font-weight:800;color:#4a594e;margin-bottom:5px}
        .aura-register-input{height:44px!important;border:1px solid #e0e8e1!important;border-radius:10px!important;background:#fbfcfb!important;box-shadow:none!important;font-size:13px!important;padding-inline:12px!important}
        .aura-register-input:focus{border-color:#6eaa83!important;box-shadow:0 0 0 3px #23805215!important;background:#fff!important}
        .aura-register-field{margin-bottom:12px}
        .aura-register-field:last-of-type{margin-bottom:16px}
        .aura-register-submit{height:46px;border:0;border-radius:10px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800;transition:background .18s,transform .18s}
        .aura-register-submit:hover{background:#104d38;color:#fff;transform:translateY(-1px)}
        .aura-register-note{display:flex;gap:9px;align-items:flex-start;padding:10px 12px;border:1px solid #e2eee5;background:#f7fbf8;border-radius:11px;color:#53665a;font-size:11px;line-height:1.55;margin-bottom:16px}
        .aura-register-note i{font-size:17px;line-height:1.25;flex:0 0 auto}
        .aura-register-error{display:flex;gap:9px;align-items:flex-start;border:1px solid #f2c4c8;background:#fff5f5;color:#8f1d2c;border-radius:10px;padding:10px 12px;font-size:12px;line-height:1.5;margin-bottom:14px}
        .aura-register-error i{flex:0 0 auto;margin-top:1px}
        .aura-register-form .cc-validation-summary{margin:0 0 12px;padding:9px 11px;border-radius:10px;font-size:11px}
        .aura-register-form .invalid-feedback{font-size:11px;line-height:1.4;margin-top:4px}
        @media(max-width:575.98px){.aura-register-shell{padding-top:4px}.aura-register-title{font-size:22px}.aura-register-tabs{margin-bottom:16px}}
    </style>

    <div class="aura-register-shell">
        <div class="aura-register-tabs" role="navigation" aria-label="Customer account pages">
            <a href="{{ route('webLogin') }}">Sign in</a>
            <a href="{{ route('webRegister') }}" class="active" aria-current="page">Create account</a>
        </div>

        <div class="text-center mb-3">
            <span class="d-inline-grid place-items-center rounded-circle mb-2"
                style="width:50px;height:50px;background:#eaf4ed;color:#176b4b;font-size:21px">
                <i class="bi bi-shield-lock"></i>
            </span>
            <h1 class="aura-register-title mb-1">Create your account</h1>
            <p class="aura-register-sub mb-0">Set up your SK Coffee customer account with secure Telegram verification.</p>
        </div>

        {{-- Non-field registration errors are shown once here. Field errors are shown once beside their inputs. --}}
        @if ($errors->has('register_error'))
            <div class="aura-register-error" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div>{{ $errors->first('register_error') }}</div>
            </div>
        @endif

        <div class="aura-register-note">
            <i class="bi bi-telegram text-success"></i>
            <div><strong>Telegram verification required.</strong> Share your own phone number with the bot. Your account is created after the number matches and you verify the six-digit code.</div>
        </div>

        <form action="{{ route('register') }}" method="post" autocomplete="on" class="aura-register-form">
            @csrf
            <div class="aura-register-field">
                <label class="aura-register-label" for="register-name">Full name</label>
                <input id="register-name" type="text"
                    class="form-control aura-register-input @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" name="name" placeholder="Your full name"
                    autocomplete="name" maxlength="255" required>
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="aura-register-field">
                <label class="aura-register-label" for="register-phone">Phone number</label>
                <input id="register-phone" type="tel"
                    class="form-control aura-register-input @error('phone') is-invalid @enderror"
                    value="{{ old('phone') }}" name="phone" placeholder="012 345 678"
                    autocomplete="tel" maxlength="20" required>
                @error('phone')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="aura-register-field">
                <label class="aura-register-label" for="register-email">Email address</label>
                <input id="register-email" type="email"
                    class="form-control aura-register-input @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" name="email" placeholder="name@example.com"
                    autocomplete="email" maxlength="255" required>
                @error('email')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="aura-register-field">
                <label class="aura-register-label" for="register-password">Password</label>
                <input id="register-password" type="password"
                    class="form-control aura-register-input @error('password') is-invalid @enderror"
                    name="password" placeholder="At least 8 characters"
                    autocomplete="new-password" minlength="8" required>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="aura-register-field">
                <label class="aura-register-label" for="register-password-confirmation">Confirm password</label>
                <input id="register-password-confirmation" type="password"
                    class="form-control aura-register-input @error('password_confirmation') is-invalid @enderror"
                    name="password_confirmation" placeholder="Re-enter your password"
                    autocomplete="new-password" minlength="8" required>
                @error('password_confirmation')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn aura-register-submit w-100">
                Continue with Telegram <i class="bi bi-arrow-right ms-2"></i>
            </button>
            <p class="text-center text-muted mt-2 mb-0" style="font-size:10px">
                Your password is securely hashed and is never sent to Telegram.
            </p>
        </form>
    </div>
@endsection
