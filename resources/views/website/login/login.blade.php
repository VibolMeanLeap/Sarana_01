@extends('website.component.master')
@section('web_title', 'Customer Login')
@section('content')
    <style>
        .customer-auth-tabs {
            display: flex;
            gap: 8px;
            padding: 5px;
            background: #f1f5f1;
            border-radius: 13px;
            margin-bottom: 25px
        }

        .customer-auth-tabs a {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            color: #718075
        }

        .customer-auth-tabs a.active {
            background: #fff;
            color: #176b4b;
            box-shadow: 0 3px 10px #1b4a2b0d
        }

        .customer-login-title {
            font: 800 25px 'Manrope', 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -.7px;
            color: #203128
        }

        .customer-login-sub {
            font-size: 12px;
            color: #7d897f;
            line-height: 1.7
        }

        .customer-auth-label {
            font-size: 11px;
            font-weight: 800;
            color: #4a594e;
            margin-bottom: 7px
        }

        .customer-auth-field {
            position: relative
        }

        .customer-auth-field .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a196;
            z-index: 2
        }

        .customer-auth-input {
            height: 49px !important;
            border: 1px solid #e0e8e1 !important;
            border-radius: 11px !important;
            background: #fbfcfb !important;
            padding: 10px 42px !important;
            box-shadow: none !important;
            font-size: 13px !important
        }

        .customer-auth-input:focus {
            border-color: #6eaa83 !important;
            box-shadow: 0 0 0 4px #23805215 !important;
            background: #fff !important
        }

        .customer-auth-eye {
            position: absolute;
            right: 7px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            color: #7d8a80
        }

        .customer-auth-eye:hover {
            background: #edf5ef;
            color: #176b4b
        }

        .customer-auth-submit {
            height: 49px;
            border: 0;
            border-radius: 11px;
            background: linear-gradient(110deg, #176b4b, #218557);
            color: #fff;
            font-size: 12px;
            font-weight: 800
        }

        .customer-auth-submit:hover {
            background: #104d38;
            color: #fff
        }

        .customer-auth-foot {
            font-size: 11px;
            color: #89958c
        }

        .customer-google-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #9aa49c;
            font-size: 10px;
            font-weight: 700;
            margin: 20px 0 12px
        }

        .customer-google-divider:before,
        .customer-google-divider:after {
            content: "";
            height: 1px;
            background: #e8ede8;
            flex: 1
        }

        .customer-google-button {
            width: 100%;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            padding: 10px 15px;
            border: 1px solid #dadce0;
            border-radius: 11px;
            background: #fff;
            color: #3c4043;
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            transition: background .18s, border-color .18s, box-shadow .18s
        }

        .customer-google-button:hover {
            background: #f8faff;
            border-color: #c6d5ef;
            color: #202124;
            box-shadow: 0 2px 8px rgba(60, 64, 67, .12)
        }

        .customer-google-button svg {
            width: 19px;
            height: 19px;
            flex: 0 0 19px
        }
    </style>
    <div class="customer-auth-tabs"><a href="{{ route('webLogin') }}" class="active">Sign in</a><a
            href="{{ route('webRegister') }}">Create account</a></div>
    <div class="text-center mb-4"><span class="d-inline-grid place-items-center rounded-circle mb-3"
            style="width:54px;height:54px;background:#eaf4ed;color:#176b4b;font-size:22px"><i
                class="bi bi-person-check"></i></span>
        <h1 class="customer-login-title mb-2">Welcome back</h1>
        <p class="customer-login-sub mb-0">Sign in to track your orders and enjoy a smoother checkout at
            {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}.</p>
    </div>
    @if (session('error'))
        <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('login') }}" method="post" data-login-lockout data-login-portal="customer"
        data-login-lock-seconds="{{ (int) session('login_lock_seconds', 0) }}"
        data-login-wait-label="{{ app()->getLocale() === 'km' ? 'សូមរង់ចាំ' : 'Please wait' }}">
        @csrf
        @include('partials.login-lockout-script')
        <div class="mb-3"><label class="customer-auth-label" for="customer-phone">Phone number</label>
            <div class="customer-auth-field"><i class="bi bi-telephone field-icon"></i><input id="customer-phone"
                    type="text" class="form-control customer-auth-input @error('phone') is-invalid @enderror"
                    value="{{ old('phone') }}" name="phone" placeholder="012 345 678" autocomplete="tel" required></div>
        </div>
        <div class="mb-3"><label class="customer-auth-label" for="customer-password">Password</label>
            <div class="customer-auth-field"><i class="bi bi-lock field-icon"></i><input id="customer-password"
                    type="password" class="form-control customer-auth-input" name="password"
                    placeholder="Enter your password" autocomplete="current-password" required><button
                    class="customer-auth-eye" type="button" id="customer-toggle-password" aria-label="Show password"><i
                        class="bi bi-eye" id="customer-toggle-icon"></i></button></div>
        </div>
        <div class="form-check mb-4"><input class="form-check-input" type="checkbox" value="1" id="customer-remember"
                name="remember"><label class="form-check-label customer-auth-foot" for="customer-remember">Keep me signed
                in</label></div>
        <button type="submit" class="btn customer-auth-submit w-100">Sign in to my account <i
                class="bi bi-arrow-right ms-2"></i></button>
    </form>
    @if (config('services.google.client_id') && config('services.google.client_secret'))
        <div class="customer-google-divider">OR CONTINUE WITH</div>
        <a href="{{ route('google.redirect', ['audience' => 'customer']) }}" class="customer-google-button">
            <svg viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#EA4335"
                    d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
                <path fill="#4285F4"
                    d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.88c-.58 2.96-2.25 5.48-4.72 7.18l7.62 5.91c4.45-4.11 7.2-10.17 7.2-17.56z" />
                <path fill="#FBBC05"
                    d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.89.93 7.58 2.56 10.78l7.97-6.19z" />
                <path fill="#34A853"
                    d="M24 48c6.48 0 11.93-2.13 15.9-5.89l-7.62-5.91c-2.12 1.42-4.84 2.26-8.28 2.26-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
            </svg>
            <span>Continue with Google</span>
        </a>
    @endif
    <div class="text-center customer-auth-foot mt-4"><i class="bi bi-shield-check me-1 text-success"></i>Your account and
        order details stay protected.</div>
    <script>
        (() => {
            const input = document.getElementById('customer-password');
            const button = document.getElementById('customer-toggle-password');
            const icon = document.getElementById('customer-toggle-icon');
            button?.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
@endsection
