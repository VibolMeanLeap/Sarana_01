@extends('login_resig.main')
@section('page_title', 'Verify administrator sign-in')
@section('content')
    @php
        $brandName = $shopSettings?->shop_name ?: 'SK Coffee';
        $brandLogo = $shopSettings?->logo ? public_path('uploads/' . $shopSettings->logo) : null;
    @endphp
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #0d2119;
            font-family: 'DM Sans', sans-serif !important;
        }

        .aura-otp-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, .95fr);
            background: #f5f8f4;
        }

        .aura-otp-aside {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(32px, 5vw, 76px);
            color: #fff;
            background: radial-gradient(circle at 12% 15%, rgba(128, 199, 145, .24), transparent 31%), radial-gradient(circle at 92% 88%, rgba(211, 183, 123, .16), transparent 33%), linear-gradient(145deg, #0b3023, #14573d 58%, #1c6848);
        }

        .aura-otp-aside:before,
        .aura-otp-aside:after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 50%;
            pointer-events: none;
        }

        .aura-otp-aside:before {
            width: 560px;
            height: 560px;
            right: -230px;
            top: -190px;
        }

        .aura-otp-aside:after {
            width: 390px;
            height: 390px;
            right: -110px;
            top: -105px;
        }

        .aura-otp-aside>* {
            position: relative;
            z-index: 1;
        }

        .aura-otp-brand {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #fff;
            text-decoration: none;
        }

        .aura-otp-logo {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 15px;
            background: rgba(255, 255, 255, .13);
            font-size: 22px;
        }

        .aura-otp-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #fff;
        }

        .aura-otp-brand-name {
            font: 800 17px 'Manrope', sans-serif;
            letter-spacing: -.3px;
        }

        .aura-otp-brand-sub {
            margin-top: 2px;
            color: rgba(255, 255, 255, .64);
            font-size: 10px;
            letter-spacing: 1.1px;
            text-transform: uppercase;
        }

        .aura-otp-aside-copy {
            max-width: 570px;
            padding: 46px 0;
        }

        .aura-otp-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 30px;
            background: rgba(255, 255, 255, .08);
            font-size: 11px;
            font-weight: 700;
        }

        .aura-otp-aside h1 {
            margin: 20px 0 14px;
            font: 800 clamp(34px, 4.1vw, 54px)/1.1 'Manrope', sans-serif;
            letter-spacing: -1.8px;
        }

        .aura-otp-aside h1 span {
            color: #bfe5c8;
        }

        .aura-otp-aside-copy p {
            max-width: 490px;
            color: rgba(255, 255, 255, .72);
            font-size: 14px;
            line-height: 1.8;
        }

        .aura-otp-step {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
        }

        .aura-otp-step-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 13px;
            background: rgba(255, 255, 255, .1);
            font-size: 18px;
        }

        .aura-otp-step strong {
            display: block;
            font-size: 12px;
        }

        .aura-otp-step span {
            display: block;
            margin-top: 3px;
            color: rgba(255, 255, 255, .62);
            font-size: 11px;
        }

        .aura-otp-foot {
            color: rgba(255, 255, 255, .58);
            font-size: 11px;
        }

        .aura-otp-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(22px, 4vw, 54px);
        }

        .aura-otp-card {
            width: min(100%, 470px);
            padding: clamp(28px, 4vw, 44px);
            border: 1px solid #e7eee8;
            border-radius: 25px;
            background: #fff;
            box-shadow: 0 22px 65px rgba(28, 61, 41, .09);
        }

        .aura-otp-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 17px;
            background: #eaf5ed;
            color: #176b4b;
            font-size: 23px;
        }

        .aura-otp-eyebrow {
            margin-top: 22px;
            color: #61806b;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .aura-otp-card h2 {
            margin: 8px 0 8px;
            color: #183b2a;
            font: 800 29px 'Manrope', sans-serif;
            letter-spacing: -.9px;
        }

        .aura-otp-desc {
            margin: 0 0 23px;
            color: #748178;
            font-size: 12px;
            line-height: 1.8;
        }

        .aura-otp-email {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 7px;
            background: #f0f7f1;
            color: #176b4b;
            font-weight: 800;
        }

        .aura-otp-label {
            display: block;
            margin-bottom: 9px;
            color: #405a49;
            font-size: 11px;
            font-weight: 800;
        }

        .aura-otp-input {
            width: 100%;
            height: 60px;
            border: 1px solid #dce7de;
            border-radius: 13px;
            background: #fbfdfb;
            color: #164b34;
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 12px;
            text-align: center;
            outline: none;
            box-shadow: none;
        }

        .aura-otp-input:focus {
            border-color: #4b9a6a;
            box-shadow: 0 0 0 4px rgba(23, 107, 75, .1);
        }

        .aura-otp-submit {
            width: 100%;
            min-height: 49px;
            margin-top: 17px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #176b4b, #104d38);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            box-shadow: 0 9px 20px rgba(23, 107, 75, .17);
        }

        .aura-otp-submit:hover {
            background: linear-gradient(135deg, #145b40, #0d412e);
        }

        .aura-otp-helper {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 17px;
            padding: 12px;
            border: 1px solid #edf2ed;
            border-radius: 11px;
            background: #f8fbf8;
            color: #76847a;
            font-size: 10px;
            line-height: 1.6;
        }

        .aura-otp-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .aura-otp-link {
            padding: 0;
            border: 0;
            background: none;
            color: #176b4b;
            font-size: 11px;
            font-weight: 800;
            text-decoration: none;
        }

        .aura-otp-link:hover {
            color: #104d38;
            text-decoration: underline;
        }

        .aura-otp-cancel {
            color: #7b8980;
        }

        .aura-otp-alert {
            border-radius: 11px;
            font-size: 11px;
        }

        @media(max-width:991.98px) {
            .aura-otp-shell {
                grid-template-columns: 1fr;
            }

            .aura-otp-aside {
                display: none;
            }

            .aura-otp-panel {
                min-height: 100vh;
                padding: 20px;
            }
        }

        @media(max-width:575.98px) {
            .aura-otp-card {
                padding: 27px 21px;
                border-radius: 20px;
            }

            .aura-otp-card h2 {
                font-size: 26px;
            }

            .aura-otp-actions {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <main class="aura-otp-shell">
        <section class="aura-otp-aside">
            <a class="aura-otp-brand" href="{{ route('showlogin') }}">
                <span class="aura-otp-logo">
                    @if ($brandLogo && file_exists($brandLogo))
                        <img src="{{ asset('uploads/' . $shopSettings->logo) }}" alt="{{ $brandName }}">
                    @else
                        <i class="bi bi-cup-hot-fill"></i>
                    @endif
                </span>
                <span><span class="aura-otp-brand-name d-block">{{ $brandName }}</span><span
                        class="aura-otp-brand-sub d-block">Secure administrator access</span></span>
            </a>
            <div class="aura-otp-aside-copy">
                <span class="aura-otp-pill"><i class="bi bi-shield-lock"></i> AURA account protection</span>
                <h1>One more step.<br><span>Then you're in.</span></h1>
                <p>We use a second verification step to help protect sensitive store settings, customer information and
                    business operations.</p>
                <div class="aura-otp-step"><span class="aura-otp-step-icon"><i
                            class="bi bi-google"></i></span><span><strong>Google identity confirmed</strong><span>Your
                            Google account has been verified.</span></span></div>
                <div class="aura-otp-step"><span class="aura-otp-step-icon"><i
                            class="bi bi-envelope-check"></i></span><span><strong>Email verification
                            required</strong><span>Only the correct one-time code can finish sign-in.</span></span></div>
            </div>
            <div class="aura-otp-foot"><i class="bi bi-shield-check me-1"></i> Secure staff portal <span
                    class="mx-2">·</span> {{ $shopSettings?->address ?: 'Coffee shop operations' }}</div>
        </section>

        <section class="aura-otp-panel">
            <div class="aura-otp-card">
                <div class="aura-otp-icon"><i class="bi bi-envelope-check"></i></div>
                <div class="aura-otp-eyebrow">Administrator verification · Step 2 of 2</div>
                <h2>Check your email.</h2>
                <p class="aura-otp-desc">We sent a six-digit security code to <span
                        class="aura-otp-email">{{ $maskedEmail }}</span>. Enter it below to continue to your AURA
                    dashboard.</p>

                @if (session('error'))
                    <div class="alert alert-danger aura-otp-alert py-2"><i
                            class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}</div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success aura-otp-alert py-2"><i
                            class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger aura-otp-alert py-2">{{ $errors->first() }}</div>
                @endif

                <form action="{{ route('staff.google-otp.verify') }}" method="POST">
                    @csrf
                    <label class="aura-otp-label" for="admin-email-otp">6-DIGIT VERIFICATION CODE</label>
                    <input id="admin-email-otp" class="aura-otp-input" type="text" name="code"
                        value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}"
                        maxlength="6" minlength="6" placeholder="••••••" aria-label="Six-digit email verification code"
                        required autofocus>
                    <button class="aura-otp-submit" type="submit">Verify and open dashboard <i
                            class="bi bi-arrow-right ms-2"></i></button>
                </form>

                <div class="aura-otp-helper"><i class="bi bi-info-circle"></i><span>Your code expires after 5 minutes. Never
                        share it with anyone, including anyone claiming to be AURA support.</span></div>

                <div class="aura-otp-actions">
                    <form action="{{ route('staff.google-otp.resend') }}" method="POST">
                        @csrf
                        <button class="aura-otp-link" type="submit"><i class="bi bi-arrow-clockwise me-1"></i>Send a new
                            code</button>
                    </form>
                    <form action="{{ route('staff.google-otp.cancel') }}" method="POST">
                        @csrf
                        <button class="aura-otp-link aura-otp-cancel" type="submit"><i
                                class="bi bi-arrow-left me-1"></i>Cancel sign-in</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
@endsection
