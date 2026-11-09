<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page_title', 'Overview') · {{ $shopSettings?->shop_name ?: config('app.name', 'Coffee Corner') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--cc-ink:#202a25;--cc-muted:#77827b;--cc-green:#176b4b;--cc-green-dark:#104d38;--cc-green-soft:#e9f4ee;--cc-bg:#f5f7f5;--cc-line:#e8ede9;--cc-card:#fff;--cc-sidebar:258px}
        *{box-sizing:border-box}body{margin:0;background:var(--cc-bg);color:var(--cc-ink);font-family:'DM Sans',sans-serif;font-size:14px}
        h1,h2,h3,h4,h5,.brand-name{font-family:'Manrope','DM Sans',sans-serif}
        .app-shell{min-height:100vh}.d-grid.place-items-center{place-items:center}.sidebar{position:fixed;inset:0 auto 0 0;width:var(--cc-sidebar);background:#fff;border-right:1px solid var(--cc-line);padding:24px 16px;display:flex;flex-direction:column;z-index:1030;overflow-y:auto}
        .brand{display:flex;align-items:center;gap:12px;padding:2px 10px 26px;text-decoration:none;color:var(--cc-ink)}.brand-mark{width:42px;height:42px;border-radius:14px;background:var(--cc-green);display:grid;place-items:center;color:#fff;font-size:20px;box-shadow:0 8px 18px #176b4b25}.brand-name{font-size:16px;font-weight:800;letter-spacing:-.4px}.brand-caption{font-size:11px;color:var(--cc-muted);margin-top:2px}
        .nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.15px;font-weight:700;color:#9aa49e;padding:15px 12px 7px}.app-nav{display:flex;flex-direction:column;gap:3px}.app-nav a{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;text-decoration:none;color:#59655e;font-weight:600;transition:.18s}.app-nav a i{font-size:17px;width:20px;text-align:center;color:#849088}.app-nav a:hover{background:#f4f7f5;color:var(--cc-green)}.app-nav a.active{background:var(--cc-green-soft);color:var(--cc-green)}.app-nav a.active i{color:var(--cc-green)}.nav-action-count{margin-left:auto;min-width:21px;height:21px;padding:0 6px;border-radius:999px;background:#fff0e8;color:#b34d27;font-size:10px;font-weight:900;display:inline-flex;align-items:center;justify-content:center;line-height:1}.nav-action-count[hidden]{display:none!important}.orders-live-indicator{display:inline-flex;align-items:center;gap:7px;color:#718078;font-size:10px;font-weight:700}.orders-live-dot{width:7px;height:7px;border-radius:50%;background:#2c9b63;box-shadow:0 0 0 3px #2c9b631b;display:inline-block}.admin-live-notice-stack{position:fixed;top:88px;right:22px;z-index:2000;display:flex;flex-direction:column;gap:9px;width:min(360px,calc(100vw - 28px));pointer-events:none}.admin-live-notice{pointer-events:auto;background:#fff;border:1px solid #dfeae2;border-left:4px solid #258354;border-radius:13px;padding:13px 14px;box-shadow:0 12px 32px rgba(24,55,37,.16);display:flex;gap:11px;align-items:flex-start;animation:adminNoticeIn .2s ease-out}.admin-live-notice-icon{width:32px;height:32px;border-radius:10px;background:#eaf5ee;color:#1b754b;display:grid;place-items:center;flex:0 0 32px}.admin-live-notice strong{display:block;color:#23392d;font-size:12px}.admin-live-notice span{display:block;color:#7b887f;font-size:10px;margin-top:3px}.admin-live-notice a{font-size:10px;font-weight:800;color:#176b4b;text-decoration:none;display:inline-block;margin-top:6px}.admin-live-notice button{margin-left:auto;border:0;background:transparent;color:#96a198;padding:0 2px;font-size:16px}@keyframes adminNoticeIn{from{opacity:0;transform:translateY(-7px)}to{opacity:1;transform:translateY(0)}}
        .sidebar-bottom{margin-top:auto;padding:18px 10px 0}.sidebar-note{border-radius:13px;background:#f5f8f5;padding:13px;color:#657169;font-size:11px;line-height:1.55}.sidebar-note strong{display:block;color:var(--cc-ink);font-size:12px;margin-bottom:3px}
        .main-wrap{margin-left:var(--cc-sidebar);min-height:100vh}.topbar{height:76px;background:#fff;border-bottom:1px solid var(--cc-line);display:flex;align-items:center;justify-content:space-between;padding:0 34px;position:sticky;top:0;z-index:1020}.topbar-eyebrow{font-size:11px;color:var(--cc-muted);margin-bottom:2px}.topbar-title{font-family:'Manrope',sans-serif;font-size:16px;font-weight:800;letter-spacing:-.3px}.user-chip{display:flex;align-items:center;gap:10px}.avatar{width:36px;height:36px;border-radius:12px;background:var(--cc-green-soft);color:var(--cc-green);display:grid;place-items:center;font-weight:800}.user-name{font-size:12px;font-weight:700}.user-role{font-size:11px;color:var(--cc-muted)}.content-area{padding:30px 34px 42px;max-width:1600px;margin:0 auto}.page-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;margin-bottom:24px}.page-heading h1{font-size:25px;font-weight:800;letter-spacing:-.8px;margin:0}.page-heading p{color:var(--cc-muted);margin:6px 0 0}.btn-cc{background:var(--cc-green);border:1px solid var(--cc-green);color:#fff;font-weight:700;border-radius:10px;padding:10px 15px}.btn-cc:hover{background:var(--cc-green-dark);border-color:var(--cc-green-dark);color:#fff}.btn-cc-light{background:#fff;border:1px solid var(--cc-line);color:#425148;font-weight:700;border-radius:10px;padding:10px 14px}.btn-cc-light:hover{background:#f5f8f6;color:var(--cc-green)}
        .card{border:1px solid var(--cc-line);border-radius:15px;box-shadow:0 3px 12px rgba(25,55,38,.035)}.card-header{background:#fff;border-bottom:1px solid var(--cc-line);padding:17px 20px;border-radius:15px 15px 0 0!important}.card-body{padding:20px}.table{--bs-table-bg:transparent}.table thead th{font-size:10px;letter-spacing:.7px;text-transform:uppercase;color:#89948d;font-weight:700;border-bottom-color:var(--cc-line);white-space:nowrap}.table td{color:#4f5c54;border-bottom-color:#eef1ef;vertical-align:middle}.table tbody tr:last-child td{border-bottom:0}.form-control,.form-select{border-color:#dfe6e1;border-radius:9px;padding:9px 12px}.form-control:focus,.form-select:focus{border-color:#65a889;box-shadow:0 0 0 .2rem #176b4b15}.badge-soft-success{background:#e8f5ed;color:#20744d}.badge-soft-warning{background:#fff4df;color:#956516}.badge-soft-danger{background:#fdeceb;color:#b43b36}.text-muted{color:var(--cc-muted)!important}
        .mobile-menu{display:none;border:0;background:transparent;font-size:22px;color:var(--cc-ink)}.sidebar-backdrop{display:none}
        @media(max-width:991.98px){.sidebar{transform:translateX(-100%);transition:transform .2s}.sidebar.open{transform:translateX(0)}.sidebar-backdrop.show{display:block;position:fixed;inset:0;background:#101a1490;z-index:1025}.main-wrap{margin-left:0}.mobile-menu{display:inline-flex}.topbar{padding:0 20px}.content-area{padding:24px 20px 36px}}
        @media(max-width:575.98px){.content-area{padding:20px 14px 30px}.topbar{height:66px;padding:0 14px}.page-heading{align-items:flex-start;flex-direction:column}.page-heading h1{font-size:22px}.user-meta{display:none}}
    </style>
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/form-validation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/password-toggle.css') }}">
</head>
<body data-admin-updates-url="{{ route('admin.operations.updates') }}" data-admin-orders-url="{{ route('admin.orders.index') }}" data-last-website-order-id="{{ $adminLatestWebsiteOrderId ?? 0 }}">
<div class="app-shell">
    <aside class="sidebar" id="appSidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            @if($shopSettings?->logo && file_exists(public_path('uploads/'.$shopSettings->logo)))
                <span class="brand-mark" style="background:#fff;box-shadow:none"><img src="{{ asset('uploads/'.$shopSettings->logo) }}" alt="{{ $shopSettings->shop_name }} logo" style="width:42px;height:42px;object-fit:contain;border-radius:10px"></span>
            @else
                <span class="brand-mark"><i class="bi bi-cup-hot-fill"></i></span>
            @endif
            <span><span class="brand-name d-block">{{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</span><span class="brand-caption">Management workspace</span></span>
        </a>
        <div class="nav-label">Workspace</div>
        <nav class="app-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->is('admin') ? 'active' : '' }}"><i class="bi bi-grid-1x2-fill"></i>Overview</a>
            <a href="{{ route('pos.dashboard') }}" class="{{ request()->is('pos*') ? 'active' : '' }}"><i class="bi bi-shop-window"></i>Point of Sale</a>
        </nav>
        <div class="nav-label">Catalog</div>
        <nav class="app-nav">
            <a href="{{ route('admin.product') }}" class="{{ request()->is('admin/product*') || request()->is('products*') ? 'active' : '' }}"><i class="bi bi-cup-straw"></i>Products</a>
            <a href="{{ route('admin.category') }}" class="{{ request()->is('admin/category*') || request()->is('categories*') ? 'active' : '' }}"><i class="bi bi-collection"></i>Categories</a>
            <a href="{{ route('offer.list') }}" class="{{ request()->is('admin/offer*') ? 'active' : '' }}"><i class="bi bi-tags"></i>Offers</a>
        </nav>
        <div class="nav-label">Operations</div>
        <nav class="app-nav">
            <a href="{{ route('admin.orders.index') }}" class="{{ request()->is('admin/orders*') || request()->is('admin/olders*') ? 'active' : '' }}"><i class="bi bi-receipt"></i><span>Orders</span><span class="nav-action-count" data-action-count="orders" title="Orders waiting for review" @if(($adminActionCounts['orders'] ?? 0) < 1) hidden @endif>{{ min(99, $adminActionCounts['orders'] ?? 0) }}{{ ($adminActionCounts['orders'] ?? 0) > 99 ? '+' : '' }}</span></a>
            <a href="{{ route('purchase.index') }}" class="{{ request()->is('admin/purchases*') ? 'active' : '' }}"><i class="bi bi-cart-check"></i>Purchases</a>
            <a href="{{ route('inventory.index') }}" class="{{ request()->is('admin/inventory*') ? 'active' : '' }}"><i class="bi bi-box-seam"></i><span>Inventory & stock</span><span class="nav-action-count" data-action-count="inventory" title="Active stock-tracked products out of stock" @if(($adminActionCounts['inventory'] ?? 0) < 1) hidden @endif>{{ min(99, $adminActionCounts['inventory'] ?? 0) }}{{ ($adminActionCounts['inventory'] ?? 0) > 99 ? '+' : '' }}</span></a>
            <a href="{{ route('expense_income.index') }}" class="{{ request()->is('admin/expense-income*') ? 'active' : '' }}"><i class="bi bi-cash-stack"></i>Sales & expenses</a>
            <a href="{{ route('reports.index') }}" class="{{ request()->is('admin/reports*') ? 'active' : '' }}"><i class="bi bi-graph-up-arrow"></i>Reports & analytics</a>
            <a href="{{ route('delivery.index') }}" class="{{ request()->is('admin/delivery*') ? 'active' : '' }}"><i class="bi bi-truck"></i><span>Delivery</span><span class="nav-action-count" data-action-count="delivery" title="Active deliveries" @if(($adminActionCounts['delivery'] ?? 0) < 1) hidden @endif>{{ min(99, $adminActionCounts['delivery'] ?? 0) }}{{ ($adminActionCounts['delivery'] ?? 0) > 99 ? '+' : '' }}</span></a>
            <a href="{{ route('reviews.index') }}" class="{{ request()->is('admin/reviews*') ? 'active' : '' }}"><i class="bi bi-star"></i><span>Reviews</span><span class="nav-action-count" data-action-count="reviews" title="Reviews awaiting approval" @if(($adminActionCounts['reviews'] ?? 0) < 1) hidden @endif>{{ min(99, $adminActionCounts['reviews'] ?? 0) }}{{ ($adminActionCounts['reviews'] ?? 0) > 99 ? '+' : '' }}</span></a>
            <a href="{{ route('admin.customer-service.index') }}" class="{{ request()->is('admin/customer-service*') ? 'active' : '' }}"><i class="bi bi-chat-square-text"></i><span>Customer enquiries</span><span class="nav-action-count" data-action-count="enquiries" title="Unread customer enquiries or replies" @if(($adminActionCounts['enquiries'] ?? 0) < 1) hidden @endif>{{ min(99, $adminActionCounts['enquiries'] ?? 0) }}{{ ($adminActionCounts['enquiries'] ?? 0) > 99 ? '+' : '' }}</span></a>
        </nav>
        <div class="nav-label">People & settings</div>
        <nav class="app-nav">
            <a href="{{ route('supplier.list') }}" class="{{ request()->is('admin/supplier*') ? 'active' : '' }}"><i class="bi bi-truck-front"></i>Suppliers</a>
            <a href="{{ route('auth.list') }}" class="{{ request()->is('admin/user*') ? 'active' : '' }}"><i class="bi bi-person-vcard"></i>Customer accounts</a>
            <a href="{{ route('setting.list') }}" class="{{ request()->is('admin/meanleap/setting*') ? 'active' : '' }}"><i class="bi bi-sliders"></i>Settings & staff management</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-note"><strong><i class="bi bi-shield-check me-1"></i>Administrator workspace</strong>Signed-in staff tools are protected by role-based access.</div>
        </div>
    </aside>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main-wrap">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="mobile-menu" id="mobileMenuButton" aria-label="Open navigation"><i class="bi bi-list"></i></button>
                <div><div class="topbar-eyebrow">{{ $shopSettings?->shop_name ?: 'Coffee Corner' }} · Store operations</div><div class="topbar-title">@yield('topbar_title', 'Management overview')</div></div>
            </div>
            <div class="user-chip">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                <div class="user-meta"><div class="user-name">{{ auth()->user()->name ?? 'Administrator' }}</div><div class="user-role">Administrator</div></div>
                <form method="POST" action="{{ route('process_logout') }}" class="ms-2">
                    @csrf
                    <button type="submit" class="btn btn-cc-light btn-sm" title="Sign out"><i class="bi bi-box-arrow-right me-1"></i><span class="d-none d-sm-inline">Sign out</span></button>
                </form>
            </div>
        </header>

        <main class="content-area">
            @if(session('success') || session('success_login') || session('update_success') || session('delete_success'))
                <div class="alert alert-success alert-dismissible fade show border-0" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') ?? session('success_login') ?? session('update_success') ?? session('delete_success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @yield('content')
        </main>
        <footer class="px-4 pb-4 text-center text-muted small">
            <div>{{ $shopSettings?->shop_name ?: 'Coffee Corner' }} Management System <span class="mx-1">·</span> Graduation Demonstration</div>
            @if($shopSettings?->address || $shopSettings?->tel)
                <div class="mt-1">{{ $shopSettings?->address }}@if($shopSettings?->address && $shopSettings?->tel)<span class="mx-2">·</span>@endif{{ $shopSettings?->tel }}</div>
            @endif
        </footer>
    </div>
</div>
<div class="admin-live-notice-stack" id="adminLiveNoticeStack" aria-live="polite" aria-atomic="false"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>if (window.jQuery) { $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } }); }</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('assets/vendors/chart.js/Chart.min.js') }}"></script>
<script>
    window.confirmDelete = (url, name = 'this record') => {
        if (typeof Swal === 'undefined') { if (window.confirm(`Delete ${name}?`)) window.location.href = url; return; }
        Swal.fire({ title: 'Delete this record?', text: `You are about to delete ${name}.`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#b83d38', cancelButtonColor: '#7b887f', confirmButtonText: 'Yes, delete', cancelButtonText: 'Keep it' }).then(result => { if (result.isConfirmed) window.location.href = url; });
    };
</script>
<script>
    (() => {
        const sidebar = document.getElementById('appSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const button = document.getElementById('mobileMenuButton');
        const close = () => { sidebar?.classList.remove('open'); backdrop?.classList.remove('show'); };
        button?.addEventListener('click', () => { sidebar?.classList.toggle('open'); backdrop?.classList.toggle('show'); });
        backdrop?.addEventListener('click', close);
    })();
</script>
<script>
(() => {
    const body = document.body;
    const endpoint = body.dataset.adminUpdatesUrl;
    if (!endpoint) return;

    let sinceOrderId = Number(body.dataset.lastWebsiteOrderId || 0);
    let polling = false;
    const stack = document.getElementById('adminLiveNoticeStack');

    window.CoffeeCornerAdminApplyCounts = (counts = {}) => {
        Object.entries(counts).forEach(([key, value]) => {
            const count = Math.max(0, Number(value || 0));
            document.querySelectorAll(`[data-action-count="${key}"]`).forEach((badge) => {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = count < 1;
                const link = badge.closest('a');
                if (link) link.setAttribute('aria-label', `${key}: ${count} items need attention`);
            });
        });
    };

    const showOrderNotice = (order) => {
        if (!stack) return;
        const notice = document.createElement('div');
        notice.className = 'admin-live-notice';

        const icon = document.createElement('div');
        icon.className = 'admin-live-notice-icon';
        icon.innerHTML = '<i class="bi bi-bag-check-fill"></i>';

        const content = document.createElement('div');
        content.className = 'flex-grow-1';
        const title = document.createElement('strong');
        title.textContent = `New website order #${order.order_id}`;
        const detail = document.createElement('span');
        detail.textContent = `${order.customer || 'Website customer'} · $${Number(order.total || 0).toFixed(2)} · ${order.payment_status || 'Unpaid'}`;
        const link = document.createElement('a');
        link.href = body.dataset.adminOrdersUrl || '/admin/orders';
        link.textContent = 'Open Orders';
        content.append(title, detail, link);

        const close = document.createElement('button');
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = '&times;';
        close.addEventListener('click', () => notice.remove());

        notice.append(icon, content, close);
        stack.prepend(notice);
        while (stack.children.length > 4) stack.lastElementChild.remove();
        window.setTimeout(() => notice.remove(), 14000);
    };

    const poll = async () => {
        if (polling || document.visibilityState !== 'visible') return;
        polling = true;
        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('since_order_id', String(sinceOrderId));
            url.searchParams.set('_poll', String(Date.now()));
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const payload = await response.json();
            if (!payload.success) return;

            window.CoffeeCornerAdminApplyCounts(payload.counts || {});
            const arrivals = Array.isArray(payload.new_orders) ? payload.new_orders : [];
            arrivals.forEach(showOrderNotice);
            sinceOrderId = Number(payload.next_since_order_id ?? payload.latest_website_order_id ?? sinceOrderId);
        } catch (error) {
            // The database remains the source of truth. The next poll retries automatically.
        } finally {
            polling = false;
        }
    };

    window.setTimeout(poll, 1800);
    window.setInterval(poll, 10000);
})();
</script>
@yield('scripts')
<script>window.CoffeeCornerValidationErrors = @json($errors->toArray());</script>
<script src="{{ asset('assets/js/form-validation.js') }}"></script>
<script src="{{ asset('assets/js/password-toggle.js') }}"></script>
</body>
</html>
