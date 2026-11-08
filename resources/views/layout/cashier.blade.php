<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', 'Cashier workspace') · {{ $shopSettings?->shop_name ?: config('app.name', 'Coffee Corner') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--cc-green:#176b4b;--cc-ink:#202a25;--cc-muted:#77827b;--cc-bg:#f5f7f5;--cc-line:#e8ede9}
        *{box-sizing:border-box}body{margin:0;background:var(--cc-bg);color:var(--cc-ink);font-family:'DM Sans',sans-serif;font-size:14px}
        .cashier-top{height:76px;background:#fff;border-bottom:1px solid var(--cc-line);display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:10}
        .brand{display:flex;align-items:center;gap:11px;color:var(--cc-ink);text-decoration:none;font:800 16px Manrope,sans-serif}.brand-mark{width:40px;height:40px;border-radius:13px;background:var(--cc-green);color:#fff;display:grid;place-items:center;font-size:19px}
        .cashier-nav{display:flex;align-items:center;gap:8px}.cashier-nav a{color:#5e6b63;text-decoration:none;font-weight:700;padding:9px 12px;border-radius:9px}.cashier-nav a.active,.cashier-nav a:hover{background:#e9f4ee;color:var(--cc-green)}
        .cashier-main{max-width:1450px;margin:auto;padding:30px 32px 46px}.panel{background:#fff;border:1px solid var(--cc-line);border-radius:16px;box-shadow:0 4px 16px rgba(29,57,39,.035)}
        .eyebrow{font-size:11px;color:var(--cc-muted);font-weight:800;letter-spacing:.9px;text-transform:uppercase}.stat-card{padding:20px}.stat-value{font:800 28px Manrope,sans-serif;letter-spacing:-.8px}.stat-label{color:var(--cc-muted);font-size:12px;font-weight:600}
        .btn-green{background:var(--cc-green);border-color:var(--cc-green);color:white;font-weight:700;border-radius:10px;padding:10px 14px}.btn-green:hover{background:#104d38;color:white}
        .table thead th{font-size:10px;text-transform:uppercase;letter-spacing:.7px;color:#89948d;border-bottom-color:var(--cc-line)}.table td{vertical-align:middle;border-bottom-color:#eef1ef;color:#4f5c54}.badge-paid{background:#e8f5ed;color:#20744d}.badge-pending{background:#fff4df;color:#956516}
        .form-control,.form-select{border-color:#dfe6e1;border-radius:9px;padding:9px 12px}.alert{border-radius:11px}
        @media(max-width:767px){.cashier-top{height:auto;min-height:70px;padding:12px 15px;gap:10px;align-items:flex-start;flex-direction:column}.cashier-nav{flex-wrap:wrap}.cashier-main{padding:20px 14px 32px}}
    </style>
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/form-validation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/password-toggle.css') }}">
</head>
<body>
<header class="cashier-top">
    <a class="brand" href="{{ route('cashier.dashboard') }}">
        @if($shopSettings?->logo && file_exists(public_path('uploads/'.$shopSettings->logo)))
            <span class="brand-mark" style="background:#fff;color:var(--cc-green)"><img src="{{ asset('uploads/'.$shopSettings->logo) }}" alt="{{ $shopSettings->shop_name }} logo" style="width:40px;height:40px;object-fit:contain;border-radius:10px"></span>
        @else
            <span class="brand-mark"><i class="bi bi-cup-hot-fill"></i></span>
        @endif
        <span>{{ $shopSettings?->shop_name ?: 'Coffee Corner' }} <small class="d-block text-muted fw-normal" style="font-size:10px;letter-spacing:.7px">CASHIER WORKSPACE</small></span>
    </a>
    <nav class="cashier-nav" aria-label="Cashier navigation">
        <a href="{{ route('cashier.dashboard') }}" class="{{ request()->routeIs('cashier.dashboard') ? 'active' : '' }}"><i class="bi bi-grid me-1"></i>Overview</a>
        <a href="{{ route('pos.dashboard') }}" class="{{ request()->routeIs('pos.dashboard') ? 'active' : '' }}"><i class="bi bi-shop-window me-1"></i>POS</a>
        <a href="{{ route('cashier.orders') }}" class="{{ request()->routeIs('cashier.orders') ? 'active' : '' }}"><i class="bi bi-receipt me-1"></i>My orders</a>
        <a href="{{ route('web.shop') }}" target="_blank" rel="noopener"><i class="bi bi-globe me-1"></i>Public shop</a>
    </nav>
    <div class="d-flex align-items-center gap-3">
        <span class="small text-muted"><i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('process_logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary rounded-3" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button></form>
    </div>
</header>
<main class="cashier-main">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Please check the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
@if($shopSettings?->address || $shopSettings?->tel)
    <footer class="text-center text-muted small pb-4 px-3">
        {{ $shopSettings?->address }}@if($shopSettings?->address && $shopSettings?->tel)<span class="mx-2">·</span>@endif{{ $shopSettings?->tel }}
    </footer>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
<script>window.CoffeeCornerValidationErrors = @json($errors->toArray());</script>
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
</body>
</html>
