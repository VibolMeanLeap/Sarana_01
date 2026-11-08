<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('web_title', 'Customer account') · {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary-green: #10b981;
            --primary-green-hover: #059669;
            --light-green-bg: #f0fdf4;
            --border-green: #a7f3d0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-white: #ffffff;
            --bg-slate: #f8fafc;
        }

        body {
            background-color: var(--bg-slate);
            color: var(--text-dark);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Auth Card Styling */
        .auth-card {
            background-color: var(--bg-white);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            max-width: 440px;
            width: 100%;
            margin: auto;
            overflow: hidden;
        }

        /* Custom Switcher Tabs */
        .auth-tabs {
            background-color: var(--bg-slate);
            border-radius: 12px;
            padding: 4px;
            display: flex;
        }

        .auth-tabs .nav-link {
            flex: 1;
            text-align: center;
            border-radius: 8px;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 16px;
            border: none;
            transition: all 0.2s ease;
        }

        .auth-tabs .nav-link.active {
            background-color: var(--bg-white);
            color: var(--primary-green);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        /* Form Controls */
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.15);
        }

        .input-group-text {
            background-color: transparent;
            color: var(--text-muted);
            border-right: none;
        }

        .form-control-with-icon {
            border-left: none;
        }

        .form-control-with-icon:focus {
            border-color: #dee2e6;
        }

        /* Buttons */
        .btn-green {
            background-color: var(--primary-green);
            color: var(--bg-white);
            font-weight: 600;
            border: none;
            padding: 10px 16px;
            transition: all 0.2s ease;
        }

        .btn-green:hover {
            background-color: var(--primary-green-hover);
            color: var(--bg-white);
        }

        .btn-social {
            border: 1px solid #e2e8f0;
            background-color: var(--bg-white);
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-social:hover {
            background-color: var(--bg-slate);
            border-color: #cbd5e1;
        }

        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .link-green {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }

        .link-green:hover {
            color: var(--primary-green-hover);
            text-decoration: underline;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/css/form-validation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/password-toggle.css') }}">
</head>

<body>

    <!-- Top Simple Brand Bar -->
    <header class="py-4 text-center">
        <a href="{{ route('web.home') }}" class="text-decoration-none fw-bold fs-3 text-dark d-inline-flex align-items-center">
            @if($shopSettings?->logo && file_exists(public_path('uploads/'.$shopSettings->logo)))
                <span class="me-2 d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px"><img src="{{ asset('uploads/'.$shopSettings->logo) }}" alt="{{ $shopSettings->shop_name }} logo" style="max-width:100%;max-height:100%;object-fit:contain"></span>
            @else
                <span class="p-2 rounded-circle me-2 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;background-color:var(--light-green-bg);color:var(--primary-green)"><i class="bi bi-cup-hot-fill fs-5"></i></span>
            @endif
            {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}
        </a>
    </header>

    <!-- Auth Main Container -->
    <main class="container d-flex align-items-center py-4">
        <div class="auth-card p-4 p-sm-5">

            <!-- Tab Controller Switcher -->
            {{-- <ul class="nav auth-tabs mb-4" id="authTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-panel"
                        type="button" role="tab">Log In</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register-panel"
                        type="button" role="tab">Register</button>
                </li>
            </ul> --}}

            <div class="tab-content">

                @yield('content')

            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>window.CoffeeCornerValidationErrors = @json($errors->toArray());</script>
    <script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
</body>

</html>