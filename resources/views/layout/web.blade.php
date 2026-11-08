<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary-green: #2e7d32;
            --primary-green-hover: #1b5e20;
            --dark-bg: #121518;
            --dark-card: #1c2126;
            --light-card-bg: #f3f4f6;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-white: #ffffff;
            --badge-red: #d32f2f;
            --badge-green: #2e7d32;
        }

        body {
            background-color: #f8fafc;
            color: var(--text-dark);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Navbar Header */
        .navbar-tech {
            background-color: var(--dark-bg);
            padding: 16px 0;
        }

        .navbar-tech .navbar-brand {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .navbar-tech .nav-link {
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.95rem;
            margin: 0 10px;
            transition: color 0.2s;
        }

        .navbar-tech .nav-link:hover {
            color: #ffffff;
        }

        /* Buttons */
        .btn-green {
            background-color: var(--primary-green);
            color: #ffffff;
            font-weight: 700;
            border: none;
            border-radius: 4px;
            padding: 10px 24px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-green:hover {
            background-color: var(--primary-green-hover);
            color: #ffffff;
        }

        /* Hero Section */
        .hero-tech {
            background-color: var(--dark-bg);
            color: #ffffff;
            padding: 60px 0 80px 0;
            position: relative;
        }

        .hero-tech h1 {
            font-weight: 800;
            font-size: 3rem;
            line-height: 1.15;
        }

        .hero-tech p {
            color: #94a3b8;
            font-size: 1.15rem;
        }

        /* Feature Benefit Bar */
        .value-bar {
            background-color: var(--dark-card);
            border-top: 1px solid #2d3748;
            color: #ffffff;
            padding: 16px 0;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Section Headers */
        .section-title {
            font-weight: 800;
            color: #1e293b;
            position: relative;
            display: inline-block;
        }

        /* Product Cards */
        .product-card {
            background-color: var(--light-card-bg);
            border-radius: 8px;
            padding: 20px;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.06);
        }

        .product-img-box {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .product-img-box img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 4px;
            color: #ffffff;
        }

        .badge-sale {
            background-color: var(--badge-red);
        }

        .badge-new {
            background-color: var(--badge-green);
        }

        .product-title {
            font-weight: 700;
            font-size: 1rem;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .product-price {
            font-weight: 800;
            font-size: 1.1rem;
            color: #0f172a;
            margin-bottom: 15px;
        }

        .product-price .old-price {
            text-decoration: line-through;
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 500;
            margin-left: 6px;
        }

        /* Middle Banner Promo */
        .middle-promo-banner {
            background-color: var(--dark-bg);
            border-radius: 8px;
            overflow: hidden;
            color: #ffffff;
            padding: 40px;
        }

        /* Dual Category Banners */
        .cat-banner-card {
            background-color: var(--light-card-bg);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .cat-banner-img {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .cat-banner-img img {
            max-height: 100%;
            object-fit: contain;
        }

        /* Newsletter Section */
        .newsletter-section {
            background-color: var(--dark-bg);
            color: #ffffff;
            padding: 50px 0;
        }

        /* Footer */
        footer {
            background-color: #0b0d0e;
            color: #94a3b8;
            padding: 40px 0 20px 0;
            font-size: 0.875rem;
        }

        footer a {
            color: #cbd5e1;
            text-decoration: none;
        }

        footer a:hover {
            color: #ffffff;
        }
    </style>
    <style>
        .hide-arrow::after {
        display: none !important;
        }
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
        }

        /* Navbar */
        .navbar-clean {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
        }

        .navbar-clean .nav-link {
            color: var(--text-dark);
            font-weight: 500;
            margin: 0 8px;
        }

        .navbar-clean .nav-link:hover {
            color: var(--primary-green);
        }

        /* Buttons */
        .btn-green {
            background-color: var(--primary-green);
            color: var(--bg-white);
            font-weight: 600;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-green:hover {
            background-color: var(--primary-green-hover);
            color: var(--bg-white);
        }

        .btn-outline-green {
            border: 1px solid var(--primary-green);
            color: var(--primary-green);
            font-weight: 600;
            background-color: transparent;
        }

        .btn-outline-green:hover {
            background-color: var(--primary-green);
            color: var(--bg-white);
        }

        /* Hero */
        .hero-clean {
            background: linear-gradient(180deg, var(--light-green-bg) 0%, var(--bg-slate) 100%);
            padding: 70px 0 50px 0;
        }

        /* Category Navigation Pills */
        .category-pills .nav-link {
            color: var(--text-muted);
            background-color: var(--bg-white);
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 10px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            margin: 4px;
            transition: all 0.2s ease;
        }

        .category-pills .nav-link:hover {
            border-color: var(--primary-green);
            color: var(--primary-green);
        }

        .category-pills .nav-link.active {
            background-color: var(--primary-green);
            color: var(--bg-white);
            border-color: var(--primary-green);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        }

        /* Product Cards */
        .product-card {
            background-color: var(--bg-white);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.05);
            border-color: var(--border-green);
        }

        .product-img-box {
            height: 200px;
            position: relative;
            background-color: #f1f5f9;
            overflow: hidden;
        }

        .product-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .badge-green {
            position: absolute;
            top: 12px;
            left: 12px;
            background-color: var(--primary-green);
            color: var(--bg-white);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Offcanvas */
        .offcanvas-clean {
            background-color: var(--bg-white);
        }

        .cart-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #ffffff;
        }
    
        /* Info Bar Outer Window */
        .info-bar {
            background-color: #0b1519;
            /* Dark background matching the image */
            color: #ffffff;
            padding: 14px 0;
            width: 100%;
            overflow: hidden;
            /* Hide anything cutting outside the wrapper bounds */
            position: relative;
        }
    
        /* Continuous Left-to-Right Scrolling Marquee Wrapper */
        .info-bar-marquee {
            display: flex;
            width: max-content;
            /* Ensure container matches absolute child sizes */
            animation: scrollRight 25s linear infinite;
            /* Loops forever */
        }
    
        /* Pause rotation animation on mouse hover for easy reading/clicking */
        .info-bar:hover .info-bar-marquee {
            animation-play-state: paused;
        }
    
        /* Main duplicate groups flanking one another for infinite seamless looping */
        .marquee-group {
            display: flex;
            align-items: center;
            justify-content: space-around;
            min-width: 100vw;
            /* Takes full window width to compute fluid transitions */
            gap: 60px;
            padding-right: 60px;
            /* Maintains uniform spacers when tracking sets */
        }
    
        /* Individual Feature Item */
        .info-bar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            /* Space between icon and text */
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.2px;
            cursor: pointer;
            transition: opacity 0.2s ease;
            white-space: nowrap;
            /* Prevent copy lines breaking in mid-sentence */
        }
    
        .info-bar-item:hover {
            opacity: 0.85;
        }
    
        /* SVG Icon Customization */
        .info-bar-icon {
            width: 20px;
            height: 20px;
            stroke: #10b981;
            /* Green accent color for icons */
            stroke-width: 2;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }
    
        /* Toast Message Notification Styles */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    
        .toast {
            background-color: white;
            color: black;
            padding: 12px 24px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            margin-top: 10px;
            border-left: 4px solid #10b981;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
    
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
    
        /* Infinite Left-to-Right Scrolling Animation Core */
        @keyframes scrollRight {
            0% {
                transform: translateX(-50%);
                /* Starts offset back left by one complete duplicated set length */
            }
    
            100% {
                transform: translateX(0%);
                /* Smoothly travels forward across exactly one set width */
            }
        }
    
        /* Tablet and Mobile Optimization adjustments */
        @media (max-width: 768px) {
            .info-bar-item {
                font-size: 13px;
            }
    
            .marquee-group {
                gap: 40px;
                padding-right: 40px;
            }
    
            @keyframes scrollRight {
                0% {
                    transform: translateX(-50%);
                }
    
                100% {
                    transform: translateX(0%);
                }
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/css/form-validation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/password-toggle.css') }}">
</head>

<body>
    <div class="info-bar">
        <!-- The scrolling marquee inner carriage tracks sets infinitely -->
        <div class="info-bar-marquee">
    
            <!-- MARQUEE SET 1 (Original track node) -->
            <div class="marquee-group">
                <!-- Item 1: Shipping -->
                <div class="info-bar-item"
                    onclick="showInfo('Free shipping applies automatically at checkout for orders over \$35.')">
                    <svg class="info-bar-icon" viewBox="0 0 24 24">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    <span>Free Express Shipping Over \$35</span>
                </div>
    
                <!-- Item 2: Organic Quality -->
                <div class="info-bar-item"
                    onclick="showInfo('Our beans are sustainably certified organic, locally sourced, and freshly roasted daily.')">
                    <svg class="info-bar-icon" viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <path d="M9 11l2 2 4-4"></path>
                    </svg>
                    <span>100% Organic & Freshly Roasted</span>
                </div>
    
                <!-- Item 3: Support -->
                <div class="info-bar-item"
                    onclick="showInfo('Need assistance? Our support team is available via chat, email, or telephone anytime.')">
                    <svg class="info-bar-icon" viewBox="0 0 24 24">
                        <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                        <path
                            d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z">
                        </path>
                    </svg>
                    <span>24/7 Dedicated Support</span>
                </div>
            </div>
    
            <!-- MARQUEE SET 2 (Cloned twin duplicate to prevent white whitespace gaps during transitions) -->
            <div class="marquee-group" aria-hidden="true">
                <!-- Item 1 Duplicate -->
                <div class="info-bar-item"
                    onclick="showInfo('Free shipping applies automatically at checkout for orders over \$35.')">
                    <svg class="info-bar-icon" viewBox="0 0 24 24">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    <span>Free Express Shipping Over \$35</span>
                </div>
    
                <!-- Item 2 Duplicate -->
                <div class="info-bar-item"
                    onclick="showInfo('Our beans are sustainably certified organic, locally sourced, and freshly roasted daily.')">
                    <svg class="info-bar-icon" viewBox="0 0 24 24">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        <path d="M9 11l2 2 4-4"></path>
                    </svg>
                    <span>100% Organic & Freshly Roasted</span>
                </div>
    
                <!-- Item 3 Duplicate -->
                <div class="info-bar-item"
                    onclick="showInfo('Need assistance? Our support team is available via chat, email, or telephone anytime.')">
                    <svg class="info-bar-icon" viewBox="0 0 24 24">
                        <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                        <path
                            d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z">
                        </path>
                    </svg>
                    <span>24/7 Dedicated Support</span>
                </div>
            </div>
    
        </div>
    </div>
    
    <!-- Container for dynamic text popups (Toasts) -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- 1. Header Navbar -->
    <nav class="navbar navbar-expand-lg navbar-clean sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center text-dark" href="{{ route('web.home') }}">
                @if($shopSettings?->logo && file_exists(public_path('uploads/'.$shopSettings->logo)))
                    <span class="me-2 d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px"><img src="{{ asset('uploads/'.$shopSettings->logo) }}" alt="{{ $shopSettings->shop_name }} logo" style="max-width:100%;max-height:100%;object-fit:contain"></span>
                @else
                    <span class="p-2 rounded-circle bg-light-green text-green me-2 d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;background-color:var(--light-green-bg);color:var(--primary-green)"><i class="bi bi-cup-hot-fill fs-5"></i></span>
                @endif
                {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#cleanNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="cleanNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="/web/shop">Shop</a></li>
                    <li class="nav-item"><a class="nav-link" href="#new-arrivals">New Arrivals</a></li>
                    <li class="nav-item"><a class="nav-link" href="#best-sellers">Best Sellers</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"
                            onclick="event.preventDefault(); if(confirm('Do you want to logout?')) { document.getElementById('logout-form').submit(); }">
                            Sign Out <i class="dropdown-item-icon ti-power-off"></i>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                
                    <!-- ១. PROFILE POPUP DROPDOWN -->
                    <div class="dropdown">
                        <button class="btn border-0 p-0 text-dark dropdown-toggle hide-arrow" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-person" style="font-size: 25px;"></i>
                        </button>
                
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 p-3" style="width: 250px;">
                            @auth
                            {{-- ករណី User បាន Login រួច --}}
                            <li class="mb-2">
                                <div class="fw-bold text-dark text-truncate">
                                    Name: {{ Auth::user()->customer?->name ?? Auth::user()->name }}
                                </div>
                                <small class="text-muted d-block text-truncate">
                                    Phone: {{ Auth::user()->phone ?? Auth::user()->email }}
                                </small>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            @if(strtolower((string) Auth::user()->role?->role_name) === 'customer')
                            <li><a class="dropdown-item rounded-2 py-2" href="{{ route('web.account') }}"><i class="bi bi-bag-check me-2"></i> My Orders & Account</a></li>
                            @elseif(in_array(strtolower((string) Auth::user()->role?->role_name), ['admin', 'cashier'], true))
                            <li><a class="dropdown-item rounded-2 py-2" href="{{ strtolower((string) Auth::user()->role?->role_name) === 'admin' ? route('admin.dashboard') : route('cashier.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i> Staff Workspace</a></li>
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                {{-- Form សម្រាប់ Logout តាមវិធីត្រឹមត្រូវ --}}
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="dropdown-item rounded-2 py-2 text-danger w-100 text-start border-0 bg-transparent">
                                        <i class="bi bi-box-arrow-right me-2"></i> Log Out
                                    </button>
                                </form>
                            </li>
                            @else
                            {{-- ករណីមិនទាន់បាន Login --}}
                            <li class="text-center py-2">
                                <p class="small text-muted mb-3">Welcome! Please login to manage your account.</p>
                                <a href="{{ url('web/login') }}" class="btn btn-success w-100 rounded-2 mb-2">Log In</a>
                                <a href="{{ url('web/register') }}" class="btn btn-outline-secondary w-100 rounded-2">Register</a>
                            </li>
                            @endauth
                        </ul>
                    </div>
                
                    @php($webCartCount = array_sum(array_map('intval', (array) session('web_cart', []))))
                    <a class="btn border-0 p-0 position-relative text-dark" href="{{ route('web.cart') }}"
                        aria-label="View shopping cart ({{ $webCartCount }} items)" title="View cart">
                        <i class="bi bi-bag" style="font-size: 22px;"></i>
                        <span id="cart-badge"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-success text-white {{ $webCartCount > 0 ? '' : 'd-none' }}"
                            style="font-size: 0.65rem;">{{ $webCartCount }}</span>
                    </a>
                
                </div>
            </div>
        </div>
    </nav>
    {{-- container --}}
    @yield('content')
    @if(session('cart_success') || session('cart_error'))
        <div class="container position-fixed start-50 translate-middle-x" style="top:88px;z-index:1080;max-width:680px" aria-live="polite">
            <div class="alert {{ session('cart_error') ? 'alert-danger' : 'alert-success' }} alert-dismissible fade show shadow-sm rounded-4" role="alert">
                <i class="bi {{ session('cart_error') ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' }} me-2"></i>
                {{ session('cart_error') ?? session('cart_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
    {{-- endcontainer --}}
    <!-- 8. Footer -->
    <footer>
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-6 col-md-3">
                    <h6 class="text-white fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2">
                        <li><a href="/web/shop">Shop</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Subscriptions</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-3">
                    <h6 class="text-white fw-bold mb-3">Customer Service</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2">
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Return Policy</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="text-white fw-bold mb-3">{{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</h6>
                    @if($shopSettings?->address)
                        <p class="small mb-2 text-white-50"><i class="bi bi-geo-alt me-2"></i>{{ $shopSettings->address }}</p>
                    @endif
                    @if($shopSettings?->tel)
                        <p class="small mb-2"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $shopSettings->tel) }}"><i class="bi bi-telephone me-2"></i>{{ $shopSettings->tel }}</a></p>
                    @endif
                    <div class="d-flex justify-content-md-end gap-3 fs-5 mt-3">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter-x"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-top border-secondary pt-3 small text-white-50 d-flex flex-wrap justify-content-between gap-2">
                <span>© {{ date('Y') }} {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}. All rights reserved.</span>
                @if($shopSettings?->exchange_rate)<span>Reference rate: $1 USD = ៛{{ number_format((float)$shopSettings->exchange_rate, 0) }} KHR</span>@endif
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>window.CoffeeCornerValidationErrors = @json($errors->toArray());</script>
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
</body>

</html>