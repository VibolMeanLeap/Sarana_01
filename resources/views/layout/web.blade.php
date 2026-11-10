<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('web_title', $shopSettings?->shop_name ?: 'Coffee Corner')</title>
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

        /* Google Maps in Store Footer */
        .store-footer-map {
            margin-top: 35px;
            padding-top: 28px;
            border-top: 1px solid rgba(230, 241, 232, .13);
        }

        .store-footer-map-title {
            color: #f0d6a7;
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: 6px;
        }

        .store-footer-map-description {
            color: rgba(229, 239, 231, .65);
            font-size: .84rem;
            margin-bottom: 18px;
        }

        .store-footer-map-frame {
            width: 100%;
            height: 320px;
            display: block;
            border: 0;
            border-radius: 14px;
            background: #fff;
        }

        .store-footer-map-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #e7c58e;
            font-size: .85rem;
            font-weight: 700;
            text-decoration: none;
            margin-top: 12px;
        }

        .store-footer-map-link:hover {
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .store-footer-map {
                margin-top: 25px;
            }

            .store-footer-map-frame {
                height: 260px;
            }
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

        /* Customer-first storefront footer */
        .store-footer {
            position: relative;
            overflow: hidden;
            background: radial-gradient(circle at 88% 8%, rgba(220, 181, 112, .13), transparent 24%), linear-gradient(125deg, #0d241a 0%, #122f22 58%, #183b2a 100%);
            color: #d7e2d9;
            padding: 58px 0 22px;
            font-size: .9rem
        }

        .store-footer:before {
            content: "";
            position: absolute;
            right: -160px;
            top: -210px;
            width: 430px;
            height: 430px;
            border: 1px solid rgba(240, 211, 161, .11);
            border-radius: 50%;
            box-shadow: 0 0 0 35px rgba(255, 255, 255, .018), 0 0 0 75px rgba(255, 255, 255, .014);
            pointer-events: none
        }

        .store-footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
            font-weight: 850;
            font-size: 1.1rem;
            letter-spacing: -.025em
        }

        .store-footer-mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: rgba(231, 198, 143, .13);
            border: 1px solid rgba(231, 198, 143, .25);
            color: #e9c991;
            font-size: 1.25rem;
            overflow: hidden
        }

        .store-footer-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #fff
        }

        .store-footer-copy {
            max-width: 310px;
            color: rgba(224, 236, 226, .66);
            font-size: .84rem;
            line-height: 1.8;
            margin: 17px 0 0
        }

        .store-footer-heading {
            color: #f0d6a7;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
            margin: 5px 0 17px
        }

        .store-footer-links {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 11px
        }

        .store-footer-links a {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: rgba(229, 239, 231, .78);
            font-size: .85rem;
            text-decoration: none;
            transition: color .18s, transform .18s
        }

        .store-footer-links a i {
            color: #d8b978;
            font-size: .9rem
        }

        .store-footer-links a:hover {
            color: #fff;
            transform: translateX(3px)
        }

        .store-footer-contact {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: rgba(229, 239, 231, .76);
            font-size: .84rem;
            line-height: 1.7;
            margin-bottom: 13px
        }

        .store-footer-contact i {
            color: #e0bd7e;
            margin-top: 3px
        }

        .store-footer-contact a {
            color: inherit;
            text-decoration: none;
            overflow-wrap: anywhere
        }

        .store-footer-contact a:hover {
            color: #fff
        }

        .store-footer-bottom {
            border-top: 1px solid rgba(230, 241, 232, .13);
            margin-top: 42px;
            padding-top: 18px;
            color: rgba(220, 233, 223, .52);
            font-size: .76rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap
        }

        .store-footer-bottom strong {
            color: rgba(245, 218, 171, .85);
            font-weight: 700
        }

        @media(max-width:767.98px) {
            .store-footer {
                padding-top: 42px
            }

            .store-footer-bottom {
                margin-top: 30px
            }
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
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center text-dark" href="{{ route('web.shop') }}">
                @if ($shopSettings?->logo && file_exists(public_path('uploads/' . $shopSettings->logo)))
                    <span class="me-2 d-inline-flex align-items-center justify-content-center"
                        style="width:40px;height:40px"><img src="{{ asset('uploads/' . $shopSettings->logo) }}"
                            alt="{{ $shopSettings->shop_name }} logo"
                            style="max-width:100%;max-height:100%;object-fit:contain"></span>
                @else
                    <span
                        class="p-2 rounded-circle bg-light-green text-green me-2 d-inline-flex align-items-center justify-content-center"
                        style="width:36px;height:36px;background-color:var(--light-green-bg);color:var(--primary-green)"><i
                            class="bi bi-cup-hot-fill fs-5"></i></span>
                @endif
                {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#cleanNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="cleanNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ route('web.shop') }}">Shop</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('web.shop') }}#customer-reviews">Customer
                            reviews</a>
                    </li>
                    @auth
                        @if (strtolower((string) Auth::user()->role?->role_name) === 'customer')
                            <li class="nav-item"><a class="nav-link" href="{{ route('web.account') }}">My orders</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('web.inbox') }}">Inbox <span class="badge rounded-pill text-bg-success ms-1" data-store-inbox-count @if(($unreadStoreReplyCount ?? 0) < 1) hidden @endif>{{ $unreadStoreReplyCount ?? 0 }}</span></a></li>
                        @endif
                    @endauth
                </ul>

                <div class="d-flex align-items-center gap-3">

                    <!-- ១. PROFILE POPUP DROPDOWN -->
                    <div class="dropdown">
                        <button class="btn border-0 p-0 text-dark dropdown-toggle hide-arrow" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person" style="font-size: 25px;"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 p-3"
                            style="width: 250px;">
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
                                @if (strtolower((string) Auth::user()->role?->role_name) === 'customer')
                                    <li><a class="dropdown-item rounded-2 py-2" href="{{ route('web.account') }}"><i
                                                class="bi bi-bag-check me-2"></i> My Orders & Account</a></li>
                                    <li><a class="dropdown-item rounded-2 py-2 d-flex align-items-center justify-content-between" href="{{ route('web.inbox') }}"><span><i class="bi bi-inbox me-2"></i> Customer Inbox</span><span class="badge rounded-pill text-bg-success" data-store-inbox-count @if(($unreadStoreReplyCount ?? 0) < 1) hidden @endif>{{ $unreadStoreReplyCount ?? 0 }}</span></a></li>
                                @elseif(in_array(strtolower((string) Auth::user()->role?->role_name), ['admin', 'cashier'], true))
                                    <li><a class="dropdown-item rounded-2 py-2"
                                            href="{{ strtolower((string) Auth::user()->role?->role_name) === 'admin' ? route('admin.dashboard') : route('cashier.dashboard') }}"><i
                                                class="bi bi-speedometer2 me-2"></i> Staff Workspace</a></li>
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
                                    <a href="{{ route('webLogin') }}" class="btn btn-success w-100 rounded-2 mb-2">Log
                                        In</a>
                                    <a href="{{ route('webRegister') }}"
                                        class="btn btn-outline-secondary w-100 rounded-2">Register</a>
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
    @if (session('cart_success') || session('cart_error'))
        <div class="container position-fixed start-50 translate-middle-x"
            style="top:88px;z-index:1080;max-width:680px" aria-live="polite">
            <div class="alert {{ session('cart_error') ? 'alert-danger' : 'alert-success' }} alert-dismissible fade show shadow-sm rounded-4"
                role="alert">
                <i
                    class="bi {{ session('cart_error') ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' }} me-2"></i>
                {{ session('cart_error') ?? session('cart_success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
    {{-- endcontainer --}}

    <!-- Customer-first storefront footer -->
    <footer class="store-footer">
        <div class="container position-relative">
            <div class="row g-4 g-lg-5">
                <div class="col-12 col-md-6 col-lg-3">
                    <a class="store-footer-brand" href="{{ route('web.shop') }}">
                        <span class="store-footer-mark">
                            @if ($shopSettings?->logo && file_exists(public_path('uploads/' . $shopSettings->logo)))
                                <img src="{{ asset('uploads/' . $shopSettings->logo) }}"
                                    alt="{{ $shopSettings->shop_name ?: 'Store' }} logo">
                            @else
                                <i class="bi bi-cup-hot-fill"></i>
                            @endif
                        </span>
                        <span>{{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</span>
                    </a>
                    <p class="store-footer-copy">Good coffee, thoughtful service and an easier way to enjoy your
                        favourites.
                        Browse the menu or contact our team whenever you need help.</p>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <h6 class="store-footer-heading">Quick links</h6>
                    <ul class="store-footer-links">
                        <li><a href="{{ route('web.shop') }}"><i class="bi bi-shop"></i>Shop the menu</a></li>
                        <li><a href="{{ route('web.about') }}"><i class="bi bi-stars"></i>About us</a></li>
                        <li><a
                                href="{{ auth()->check() && strtolower((string) auth()->user()->role?->role_name) === 'customer' ? route('web.account') : route('webLogin') }}"><i
                                    class="bi bi-receipt"></i>Track an order</a></li>
                    </ul>
                </div>
                <div class="col-6 col-md-6 col-lg-3">
                    <h6 class="store-footer-heading">Customer service</h6>
                    <ul class="store-footer-links">
                        <li><a href="{{ route('web.shipping') }}"><i class="bi bi-truck"></i>Shipping information</a>
                        </li>
                        <li><a href="{{ route('web.returns') }}"><i class="bi bi-shield-check"></i>Returns & order
                                issues</a></li>
                        <li><a href="{{ route('web.contact') }}"><i class="bi bi-chat-dots"></i>Contact support</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <h6 class="store-footer-heading">Visit & contact</h6>
                    @if ($shopSettings?->address)
                        <div class="store-footer-contact"><i
                                class="bi bi-geo-alt-fill"></i><span>{{ $shopSettings->address }}</span>
                        </div>
                    @endif
                    @if ($shopSettings?->tel)
                        <div class="store-footer-contact"><i class="bi bi-telephone-fill"></i><a
                                href="tel:{{ preg_replace('/[^0-9+]/', '', $shopSettings->tel) }}">{{ $shopSettings->tel }}</a>
                        </div>
                    @endif
                    <div class="store-footer-contact"><i class="bi bi-chat-square-heart-fill"></i><a
                            href="{{ route('web.contact') }}">Send our team a message</a></div>
                    <a href="{{ route('web.contact') }}" class="btn btn-sm rounded-pill px-3 mt-1"
                        style="background:#e7c58e;color:#253321;font-weight:850"><i
                            class="bi bi-arrow-up-right me-1"></i>Get
                        support</a>
                </div>
            </div>

            {{-- Google Maps Location --}}
            <div class="store-footer-map">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="store-footer-map-title">
                            <i class="bi bi-geo-alt-fill me-2"></i>
                            Find Our Location
                        </h5>

                        <p class="store-footer-map-description mb-0">
                            Visit us and enjoy your favourite coffee.
                        </p>
                    </div>
                </div>

                <iframe class="store-footer-map-frame"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3878.10731613387!2d102.95597197485266!3d13.590255086785147!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x311ae751f409e2a3%3A0x3dbffb38eb34a012!2sBuild%20Bright%20University%2C%20Banteay%20Meanchey%20Campus!5e0!3m2!1sen!2skh!4v1794181749150!5m2!1sen!2skh"
                    title="Coffee Corner Google Maps Location" loading="lazy" allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>

                <a href="https://www.google.com/maps/search/?api=1&query=13.590255086785147,102.95597197485266"
                    target="_blank" rel="noopener noreferrer" class="store-footer-map-link">

                    <i class="bi bi-map"></i>
                    Open in Google Maps
                    <i class="bi bi-arrow-up-right"></i>
                </a>

            </div>
            {{-- End Google Maps Location --}}
            <div class="store-footer-bottom">
                <div class="store-footer-bottom">
                    <span>© {{ date('Y') }} <strong>{{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</strong>.
                        All rights
                        reserved.</span>
                    @if ($shopSettings?->exchange_rate)
                        <span>Reference rate: $1 USD = ៛{{ number_format((float) $shopSettings->exchange_rate, 0) }}
                            KHR</span>
                    @endif
                    <a href="{{ route('web.shop') }}" style="color:rgba(245,218,171,.8);text-decoration:none"><i
                            class="bi bi-arrow-up-circle me-1"></i>Back to shop</a>
                </div>
            </div>

    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        window.CoffeeCornerValidationErrors = @json($errors->toArray());
    </script>
    <script src="{{ asset('assets/js/form-validation.js') }}"></script>
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
</body>

</html>
