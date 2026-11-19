@extends('website.component.master')
@section('web_title', 'Complete your customer profile')
@section('content')
<style>
    .sk-google-complete{max-width:500px;width:100%;margin:0 auto;padding:12px 0 32px}
    .sk-google-card{background:#fff;border:1px solid #e3ebe4;border-radius:20px;padding:24px;box-shadow:0 12px 35px rgba(24,63,40,.06)}
    .sk-google-title{font:800 25px 'Manrope','Plus Jakarta Sans',sans-serif;letter-spacing:-.7px;color:#203128}
    .sk-google-sub{font-size:12px;color:#7d897f;line-height:1.7}
    .sk-google-label{display:block;font-size:11px;font-weight:800;color:#4a594e;margin-bottom:7px}
    .sk-google-input{height:48px;border:1px solid #e0e8e1;border-radius:11px;background:#fbfcfb;font-size:13px}
    .sk-google-input:focus{border-color:#6eaa83;box-shadow:0 0 0 4px #23805215}
    .sk-google-submit{height:49px;border:0;border-radius:11px;background:linear-gradient(110deg,#176b4b,#218557);color:#fff;font-size:12px;font-weight:800}
    .sk-google-submit:hover{background:#104d38;color:#fff}
    .sk-google-note{display:flex;gap:10px;padding:12px;border:1px solid #dcebe0;background:#f4faf5;border-radius:12px;color:#53665a;font-size:11px;line-height:1.6}
</style>
<div class="sk-google-complete">
    <div class="sk-google-card">
        <div class="text-center mb-4">
            <span class="d-inline-grid place-items-center rounded-circle mb-3" style="width:58px;height:58px;background:#eaf4ed;color:#176b4b;font-size:24px"><i class="bi bi-google"></i></span>
            <h1 class="sk-google-title mb-2">One last step!</h1>
            <p class="sk-google-sub mb-0">Your Google email is verified. Please confirm your customer name and add a phone number for orders and delivery updates.</p>
        </div>
        @if ($errors->any())<div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>@endif
        <div class="sk-google-note mb-4"><i class="bi bi-shield-check text-success fs-5"></i><span><strong>Google account verified</strong><br>{{ $googleEmail }}<br><span class="text-muted">Your email is taken directly from Google and cannot be changed on this screen.</span></span></div>
        <form method="POST" action="{{ route('web.google.customer.complete.store') }}">
            @csrf
            <div class="mb-3">
                <label class="sk-google-label" for="google-customer-name">Customer name</label>
                <input id="google-customer-name" class="form-control sk-google-input @error('name') is-invalid @enderror" name="name" type="text" value="{{ old('name', $googleName) }}" autocomplete="name" maxlength="255" required>
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="sk-google-label" for="google-customer-phone">Phone number</label>
                <input id="google-customer-phone" class="form-control sk-google-input @error('phone') is-invalid @enderror" name="phone" type="tel" value="{{ old('phone') }}" placeholder="012 345 678" autocomplete="tel" maxlength="20" required>
                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <small class="text-muted d-block mt-2" style="font-size:10px">Use a number where SK Coffee can reach you about pickup or delivery orders.</small>
            </div>
            <button type="submit" class="btn sk-google-submit w-100">Create my SK Coffee account <i class="bi bi-arrow-right ms-2"></i></button>
        </form>
        <div class="text-center mt-3"><a href="{{ route('webRegister') }}" class="small fw-bold text-success text-decoration-none">Cancel and choose another registration method</a></div>
    </div>
</div>
@endsection
