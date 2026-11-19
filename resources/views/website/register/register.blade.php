@extends('website.component.master')
@section('web_title', 'Create an account')
@section('content')
<style>
    .sk-register-shell{width:100%;max-width:560px;margin:0 auto;padding:8px 0 30px}
    .sk-register-tabs{display:flex;gap:6px;padding:4px;background:#f1f5f1;border-radius:13px;margin-bottom:22px}
    .sk-register-tabs a{flex:1;text-align:center;padding:10px;border-radius:10px;text-decoration:none;font-size:12px;font-weight:800;color:#718075}
    .sk-register-tabs a.active{background:#fff;color:#176b4b;box-shadow:0 3px 10px #1b4a2b0d}
    .sk-register-title{font:800 26px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.7px;color:#203128}
    .sk-register-sub{font-size:12px;color:#7d897f;line-height:1.7;max-width:430px;margin:auto}
    .sk-register-google{width:100%;min-height:52px;display:flex;align-items:center;justify-content:center;gap:12px;padding:12px 16px;border:1px solid #dce4de;border-radius:13px;background:#fff;color:#29352d;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 5px 16px rgba(24,63,40,.04);transition:.18s}
    .sk-register-google:hover{background:#f8fcf9;border-color:#8fb59b;color:#176b4b;transform:translateY(-1px);box-shadow:0 8px 20px rgba(24,63,40,.09)}
    .sk-register-google svg{width:20px;height:20px}
    .sk-register-divider{display:flex;align-items:center;gap:12px;color:#9aa49c;font-size:10px;font-weight:800;letter-spacing:.12em;margin:22px 0 16px}
    .sk-register-divider:before,.sk-register-divider:after{content:"";height:1px;background:#e8ede8;flex:1}
    .sk-register-card{border:1px solid #e4ebe5;border-radius:16px;background:#fff;padding:18px}
    .sk-register-label{display:block;font-size:11px;font-weight:800;color:#4a594e;margin-bottom:6px}
    .sk-register-input{height:45px!important;border:1px solid #e0e8e1!important;border-radius:10px!important;background:#fbfcfb!important;box-shadow:none!important;font-size:13px!important}
    .sk-register-input:focus{border-color:#6eaa83!important;box-shadow:0 0 0 3px #23805215!important;background:#fff!important}
    .sk-register-field{margin-bottom:12px}
    .sk-register-submit{height:47px;border:0;border-radius:10px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800}
    .sk-register-submit:hover{background:#104d38;color:#fff}
    .sk-register-note{display:flex;gap:10px;align-items:flex-start;padding:11px 12px;border:1px solid #e2eee5;background:#f7fbf8;border-radius:11px;color:#53665a;font-size:11px;line-height:1.6;margin-bottom:15px}
    .sk-register-note i{font-size:17px;flex:0 0 auto}
    .sk-register-error{border:1px solid #f2c4c8;background:#fff5f5;color:#8f1d2c;border-radius:10px;padding:10px 12px;font-size:12px;line-height:1.5;margin-bottom:14px}
    @media(max-width:575px){.sk-register-shell{padding-top:4px}.sk-register-title{font-size:23px}.sk-register-card{padding:14px}}
</style>
<div class="sk-register-shell">
    <nav class="sk-register-tabs" aria-label="Customer account pages">
        <a href="{{ route('webLogin') }}">Sign in</a>
        <a href="{{ route('webRegister') }}" class="active" aria-current="page">Create account</a>
    </nav>
    <div class="text-center mb-4">
        <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:58px;height:58px;background:#eaf4ed;color:#176b4b;font-size:23px"><i class="bi bi-cup-hot"></i></span>
        <h1 class="sk-register-title mb-2">Join SK Coffee</h1>
        <p class="sk-register-sub mb-0">Create your customer account and enjoy faster checkout, order tracking and a more personal coffee experience.</p>
    </div>

    @if ($errors->has('register_error'))<div class="sk-register-error" role="alert">{{ $errors->first('register_error') }}</div>@endif
    @if ($errors->any() && ! $errors->has('register_error'))<div class="sk-register-error" role="alert"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if (config('services.google.client_id') && config('services.google.client_secret'))
        <a href="{{ route('google.redirect', ['audience' => 'customer']) }}" class="sk-register-google">
            <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.88c-.58 2.96-2.25 5.48-4.72 7.18l7.62 5.91c4.45-4.11 7.2-10.17 7.2-17.56z"/><path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.89.93 7.58 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.9-5.89l-7.62-5.91c-2.12 1.42-4.84 2.26-8.28 2.26-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            <span>Sign up / Sign in with Google</span>
        </a>
        <p class="text-center text-muted mt-2 mb-0" style="font-size:10px">Google shares your verified email and profile name. No email OTP is needed for Google sign-up.</p>
    @else
        <div class="sk-register-note"><i class="bi bi-info-circle-fill text-success"></i><span>Google sign-in is not configured at the moment. You can still create an account using Telegram verification below.</span></div>
    @endif

    <div class="sk-register-divider">OR REGISTER WITH TELEGRAM</div>
    <div class="sk-register-card">
        <div class="sk-register-note"><i class="bi bi-telegram text-success"></i><span><strong>Telegram verification.</strong> Share your own phone number with @Sa1ona_Bot. SK Coffee creates your account after the shared number matches and you verify the six-digit code.</span></div>
        <form action="{{ route('register') }}" method="post" autocomplete="on">
            @csrf
            <div class="sk-register-field">
                <label class="sk-register-label" for="register-name">Customer name</label>
                <input id="register-name" type="text" class="form-control sk-register-input @error('name') is-invalid @enderror" value="{{ old('name') }}" name="name" placeholder="Your full name" autocomplete="name" maxlength="255" required>
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="sk-register-field">
                <label class="sk-register-label" for="register-phone">Phone number</label>
                <input id="register-phone" type="tel" class="form-control sk-register-input @error('phone') is-invalid @enderror" value="{{ old('phone') }}" name="phone" placeholder="012 345 678" autocomplete="tel" maxlength="20" required>
                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="sk-register-field">
                <label class="sk-register-label" for="register-email">Email address</label>
                <input id="register-email" type="email" class="form-control sk-register-input @error('email') is-invalid @enderror" value="{{ old('email') }}" name="email" placeholder="name@example.com" autocomplete="email" maxlength="255" required>
                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="sk-register-field">
                <label class="sk-register-label" for="register-password">Password</label>
                <input id="register-password" type="password" class="form-control sk-register-input @error('password') is-invalid @enderror" name="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="sk-register-field">
                <label class="sk-register-label" for="register-password-confirmation">Confirm password</label>
                <input id="register-password-confirmation" type="password" class="form-control sk-register-input @error('password_confirmation') is-invalid @enderror" name="password_confirmation" placeholder="Re-enter your password" autocomplete="new-password" minlength="8" required>
                @error('password_confirmation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn sk-register-submit w-100">Continue with Telegram <i class="bi bi-arrow-right ms-2"></i></button>
            <p class="text-center text-muted mt-2 mb-0" style="font-size:10px">Your password is securely hashed and is never sent to Telegram.</p>
        </form>
    </div>
</div>
@endsection
