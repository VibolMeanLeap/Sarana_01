@extends('website.component.master')
@section('web_title', 'Create Customer Account')
@section('content')
    <style>
        .aura-register-shell {
            max-width: 560px;
            margin: 0 auto;
            padding: 12px 0 34px
        }

        .aura-register-tabs {
            display: flex;
            gap: 8px;
            padding: 5px;
            background: #f1f5f1;
            border-radius: 13px;
            margin-bottom: 25px
        }

        .aura-register-tabs a {
            flex: 1;
            text-align: center;
            padding: 11px 9px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            color: #718075
        }

        .aura-register-tabs a.active {
            background: #fff;
            color: #176b4b;
            box-shadow: 0 3px 10px #1b4a2b0d
        }

        .aura-register-title {
            font: 800 26px 'Manrope', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -.7px;
            color: #203128
        }

        .aura-register-sub {
            font-size: 12px;
            color: #7d897f;
            line-height: 1.7
        }

        .aura-register-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #4a594e;
            margin-bottom: 7px
        }

        .aura-register-input {
            height: 48px !important;
            border: 1px solid #e0e8e1 !important;
            border-radius: 11px !important;
            background: #fbfcfb !important;
            box-shadow: none !important;
            font-size: 13px !important
        }

        .aura-register-input:focus {
            border-color: #6eaa83 !important;
            box-shadow: 0 0 0 4px #23805215 !important;
            background: #fff !important
        }

        .aura-register-submit {
            height: 49px;
            border: 0;
            border-radius: 11px;
            background: linear-gradient(110deg, #176b4b, #218557);
            color: #fff;
            font-size: 12px;
            font-weight: 800
        }

        .aura-register-submit:hover {
            background: #104d38;
            color: #fff
        }

        .aura-register-note {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 13px;
            border: 1px solid #e2eee5;
            background: #f7fbf8;
            border-radius: 12px;
            color: #53665a;
            font-size: 11px;
            line-height: 1.65
        }
    </style>
    <div class="aura-register-shell">
        <div class="aura-register-tabs">
            <a href="{{ route('webLogin') }}">Sign in</a>
            <a href="{{ route('webRegister') }}" class="active">Create account</a>
        </div>

        <div class="text-center mb-4">
            <span class="d-inline-grid place-items-center rounded-circle mb-3"
                style="width:56px;height:56px;background:#eaf4ed;color:#176b4b;font-size:23px">
                <i class="bi bi-shield-lock"></i>
            </span>
            <h1 class="aura-register-title mb-2">Create your account</h1>
            <p class="aura-register-sub mb-0">Join SK Coffee and verify your account securely through Telegram before your
                first sign-in.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="aura-register-note mb-4">
            <i class="bi bi-telegram fs-5 text-success"></i>
            <div><strong>Telegram verification required.</strong> After submitting this form, you will connect the Telegram
                bot and enter a six-digit code. Your customer account is created only after the code is verified.</div>
        </div>

        <form action="{{ route('register') }}" method="post" autocomplete="on">
            @csrf
            <div class="mb-3">
                <label class="aura-register-label" for="register-name">Full name</label>
                <input id="register-name" type="text"
                    class="form-control aura-register-input @error('name') is-invalid @enderror" value="{{ old('name') }}"
                    name="name" placeholder="Your full name" autocomplete="name" maxlength="255" required>
                @error('name')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="aura-register-label" for="register-phone">Phone number</label>
                <input id="register-phone" type="tel"
                    class="form-control aura-register-input @error('phone') is-invalid @enderror"
                    value="{{ old('phone') }}" name="phone" placeholder="012 345 678" autocomplete="tel" maxlength="20"
                    required>
                @error('phone')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="aura-register-label" for="register-email">Email address</label>
                <input id="register-email" type="email"
                    class="form-control aura-register-input @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" name="email" placeholder="name@example.com" autocomplete="email"
                    maxlength="255" required>
                @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="aura-register-label" for="register-password">Password</label>
                <input id="register-password" type="password"
                    class="form-control aura-register-input @error('password') is-invalid @enderror" name="password"
                    placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <label class="aura-register-label" for="register-password-confirmation">Confirm password</label>
                <input id="register-password-confirmation" type="password" class="form-control aura-register-input"
                    name="password_confirmation" placeholder="Re-enter your password" autocomplete="new-password"
                    minlength="8" required>
            </div>
            <button type="submit" class="btn aura-register-submit w-100">Continue with Telegram <i
                    class="bi bi-arrow-right ms-2"></i></button>
        </form>
    </div>
@endsection
