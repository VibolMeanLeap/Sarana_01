@extends('layout.admin')

@section('page_title', 'Overview')
@section('topbar_title', 'Store overview')

@push('styles')
<style>
    .aura-hero{position:relative;isolation:isolate;overflow:hidden;border-radius:24px;padding:28px 30px;color:#fff;background:linear-gradient(115deg,#0c3d2e 0%,#176b4b 52%,#2b8a63 100%);box-shadow:0 18px 38px rgba(16,77,56,.18)}
    .aura-hero:before,.aura-hero:after{content:"";position:absolute;border-radius:50%;z-index:-1;pointer-events:none}.aura-hero:before{width:330px;height:330px;right:-70px;top:-190px;background:rgba(255,255,255,.11)}.aura-hero:after{width:220px;height:220px;right:180px;bottom:-170px;border:1px solid rgba(255,255,255,.17)}
    .aura-kicker{display:inline-flex;align-items:center;gap:8px;font-size:10px;font-weight:800;letter-spacing:1.35px;text-transform:uppercase;color:#d8f1e3}.aura-kicker i{font-size:14px}.aura-hero h1{font:800 clamp(24px,3vw,34px)/1.12 'Manrope',sans-serif;letter-spacing:-1.2px;margin:12px 0 8px}.aura-hero p{color:rgba(255,255,255,.76);max-width:580px;margin:0}.aura-hero-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:20px}.aura-hero .btn{border-radius:11px;padding:10px 15px;font-weight:800}.aura-btn-white{background:#fff;color:#14563d;border:1px solid #fff}.aura-btn-white:hover{background:#eff8f2;color:#10452f}.aura-btn-glass{background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.24)}.aura-btn-glass:hover{background:rgba(255,255,255,.2);color:#fff}
    .aura-live-card{background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.18);border-radius:17px;padding:16px;backdrop-filter:blur(6px);min-width:190px}.aura-live-card .label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.7);font-weight:800}.aura-live-card .value{font:800 28px 'Manrope',sans-serif;margin-top:6px}.aura-live-card .foot{font-size:11px;color:rgba(255,255,255,.7)}
    .aura-section-title{font:800 16px 'Manrope',sans-serif;letter-spacing:-.3px;color:#24332b}.aura-section-sub{font-size:11px;color:#87938b;margin-top:3px}.aura-stat{position:relative;overflow:hidden;height:100%;border:1px solid #e8eee9;background:#fff;border-radius:17px;padding:18px 19px;box-shadow:0 5px 16px rgba(23,65,42,.035);transition:transform .2s,box-shadow .2s}.aura-stat:hover{transform:translateY(-3px);box-shadow:0 12px 24px rgba(23,65,42,.08)}.aura-stat:after{content:"";position:absolute;width:100px;height:100px;border-radius:50%;right:-42px;bottom:-50px;background:var(--orb,#e8f5ed);opacity:.7}.aura-stat-top{display:flex;align-items:center;justify-content:space-between;gap:12px}.aura-stat-label{font-size:11px;font-weight:700;color:#7d8981}.aura-stat-icon{width:40px;height:40px;border-radius:13px;display:grid;place-items:center;background:var(--orb,#e8f5ed);color:var(--accent,#176b4b);font-size:18px}.aura-stat-value{font:800 26px 'Manrope',sans-serif;letter-spacing:-.8px;color:#202d25;margin-top:13px;line-height:1}.aura-stat-foot{font-size:11px;color:#87938b;margin-top:9px}.aura-panel{border:1px solid #e8eee9;border-radius:18px;background:#fff;box-shadow:0 5px 17px rgba(23,65,42,.035);height:100%}.aura-panel-head{padding:18px 20px 14px;border-bottom:1px solid #eff2ef;display:flex;align-items:center;justify-content:space-between;gap:12px}.aura-panel-body{padding:18px 20px}.aura-link{color:#176b4b;text-decoration:none;font-size:11px;font-weight:800}.aura-link:hover{color:#104d38}.aura-chart-wrap{height:260px;position:relative}.aura-stock-row{display:flex;align-items:center;gap:12px;padding:11px 0}.aura-stock-icon{width:36px;height:36px;border-radius:11px;background:#f1f6f2;color:#547862;display:grid;place-items:center;flex:0 0 auto}.aura-stock-progress{height:5px;border-radius:10px;background:#edf2ee;overflow:hidden;margin-top:7px}.aura-stock-progress span{height:100%;display:block;border-radius:10px;background:linear-gradient(90deg,#70b58d,#176b4b)}.aura-product-rank{width:24px;height:24px;border-radius:8px;display:grid;place-items:center;background:#f1f6f2;color:#48745a;font-weight:800;font-size:10px;flex:0 0 auto}.aura-table thead th{font-size:9px;letter-spacing:.8px;text-transform:uppercase;color:#96a098;border-bottom:1px solid #edf1ee;padding:12px 14px}.aura-table tbody td{font-size:12px;color:#56635a;padding:13px 14px;border-bottom:1px solid #f0f3f0}.aura-table tbody tr:last-child td{border-bottom:0}.aura-order-id{font-weight:800;color:#26382e}.aura-mini-badge{display:inline-flex;align-items:center;gap:5px;border-radius:30px;padding:5px 9px;font-size:10px;font-weight:800;background:#eef5ef;color:#34744c}.aura-mini-badge.pending{background:#fff5e5;color:#9b6a1b}.aura-mini-badge.cancelled{background:#fff0ef;color:#a74c45}.aura-empty{padding:32px 16px;text-align:center;color:#87938b;font-size:12px}.aura-empty i{font-size:26px;color:#77a88a;display:block;margin-bottom:8px}
    @media(max-width:767px){.aura-hero{padding:23px 20px;border-radius:19px}.aura-live-card{min-width:0;width:100%}.aura-chart-wrap{height:220px}.aura-panel-head,.aura-panel-body{padding-left:15px;padding-right:15px}}
</style>
@endpush

@section('content')
@php
    $firstName = collect(preg_split('/\s+/', trim(auth()->user()->name ?? 'there')))->first() ?: 'there';
    $shopName = $shopSettings?->shop_name ?: 'Coffee Corner';
@endphp
<div class="d-flex flex-column gap-4">
    <section class="aura-hero">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="aura-kicker"><i class="bi bi-stars"></i> Your command center · {{ now()->format('l, d M Y') }}</div>
                <h1>Good to see you, {{ $firstName }}.</h1>
                <p>Here is your store at a glance. Keep the team moving, watch the numbers, and make every cup count.</p>
                <div class="aura-hero-actions">
                    <a href="{{ route('pos.dashboard') }}" class="btn aura-btn-white"><i class="bi bi-lightning-charge-fill me-1"></i> Open point of sale</a>
                    <a href="{{ route('admin.orders.index') }}" class="btn aura-btn-glass"><i class="bi bi-receipt me-1"></i> Manage orders</a>
                </div>
            </div>
            <div class="col-lg-4 d-flex justify-content-lg-end">
                <div class="aura-live-card w-100">
                    <div class="label"><i class="bi bi-activity me-1"></i> Today's performance</div>
                    <div class="value">${{ number_format($todayRevenue, 2) }}</div>
                    <div class="foot">{{ number_format($todayOrderCount) }} orders recorded today</div>
                    <div class="mt-3 d-flex align-items-center justify-content-between small"><span class="text-white-50">Payment completion</span><strong>{{ $paymentCompletionRate }}%</strong></div>
                    <div class="progress mt-2" style="height:6px;background:rgba(255,255,255,.2)"><div class="progress-bar bg-white" style="width:{{ $paymentCompletionRate }}%"></div></div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-6 col-xl-3"><div class="aura-stat" style="--orb:#e8f5ed;--accent:#238052"><div class="aura-stat-top"><span class="aura-stat-label">Total revenue</span><span class="aura-stat-icon"><i class="bi bi-wallet2"></i></span></div><div class="aura-stat-value">${{ number_format($totalRevenue, 2) }}</div><div class="aura-stat-foot">From {{ number_format($paidOrders) }} verified paid orders</div></div></div>
        <div class="col-6 col-xl-3"><div class="aura-stat" style="--orb:#edf1ff;--accent:#5869ad"><div class="aura-stat-top"><span class="aura-stat-label">Total orders</span><span class="aura-stat-icon"><i class="bi bi-receipt-cutoff"></i></span></div><div class="aura-stat-value">{{ number_format($totalOrders) }}</div><div class="aura-stat-foot">{{ number_format($pendingOrders) }} awaiting payment</div></div></div>
        <div class="col-6 col-xl-3"><div class="aura-stat" style="--orb:#fff3df;--accent:#a8792f"><div class="aura-stat-top"><span class="aura-stat-label">Active products</span><span class="aura-stat-icon"><i class="bi bi-cup-hot"></i></span></div><div class="aura-stat-value">{{ number_format($productCount) }}</div><div class="aura-stat-foot">Across {{ number_format($customerCount) }} registered customers</div></div></div>
        <div class="col-6 col-xl-3"><div class="aura-stat" style="--orb:#f2eafa;--accent:#8055a3"><div class="aura-stat-top"><span class="aura-stat-label">Stock units</span><span class="aura-stat-icon"><i class="bi bi-box-seam"></i></span></div><div class="aura-stat-value">{{ number_format($stockUnits) }}</div><div class="aura-stat-foot">{{ number_format($lowStockProducts->count()) }} products need attention</div></div></div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="aura-panel">
                <div class="aura-panel-head"><div><div class="aura-section-title">Revenue momentum</div><div class="aura-section-sub">Paid sales over the last seven days</div></div><span class="aura-mini-badge"><i class="bi bi-graph-up-arrow"></i> 7-day view</span></div>
                <div class="aura-panel-body"><div class="aura-chart-wrap"><canvas id="salesTrendChart" aria-label="Revenue over the last seven days"></canvas></div></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="aura-panel">
                <div class="aura-panel-head"><div><div class="aura-section-title">Top sellers</div><div class="aura-section-sub">Best-performing products by paid units</div></div><a href="{{ route('admin.product') }}" class="aura-link">Catalog <i class="bi bi-arrow-up-right"></i></a></div>
                <div class="aura-panel-body">
                    @forelse($topSellingProducts as $product)
                        <div class="d-flex align-items-center gap-2 py-2">
                            <span class="aura-product-rank">{{ $loop->iteration }}</span>
                            <div class="flex-grow-1 min-w-0"><div class="small fw-bold text-truncate">{{ $product->product_name }}</div><div class="aura-stock-progress"><span style="width:{{ min(100, ((int)$product->units_sold / $topSellingMax) * 100) }}%"></span></div></div>
                            <span class="small fw-bold text-dark">{{ number_format((int)$product->units_sold) }} <span class="text-muted fw-normal">sold</span></span>
                        </div>
                    @empty
                        <div class="aura-empty"><i class="bi bi-cup-hot"></i>No paid product sales yet. Your best sellers will appear here.</div>
                    @endforelse
                    <div class="border-top mt-3 pt-3 d-flex justify-content-between"><span class="small text-muted">Average paid order</span><strong class="small">${{ number_format($averageOrderValue, 2) }}</strong></div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="aura-panel">
                <div class="aura-panel-head"><div><div class="aura-section-title">Latest orders</div><div class="aura-section-sub">Recent activity from your shop</div></div><a href="{{ route('admin.orders.index') }}" class="aura-link">All orders <i class="bi bi-arrow-up-right"></i></a></div>
                <div class="table-responsive"><table class="table aura-table align-middle mb-0"><thead><tr><th class="ps-4">Order</th><th>Customer</th><th>Handled by</th><th>Amount</th><th>Payment</th><th class="pe-4">Date</th></tr></thead><tbody>
                    @forelse($recentOrders as $order)
                        @php $status = strtolower((string)($order->payment_status ?: ($order->is_paid ? 'paid' : 'pending'))); @endphp
                        <tr><td class="ps-4"><span class="aura-order-id">#{{ $order->order_id }}</span></td><td>{{ $order->customer->name ?? 'Walk-in' }}</td><td>{{ $order->cashier->name ?? 'Website' }}</td><td class="fw-bold">${{ number_format((float)($order->final_price ?? $order->total_amount ?? 0), 2) }}</td><td><span class="aura-mini-badge {{ $order->is_paid ? '' : 'pending' }}">{{ $order->is_paid ? 'Paid' : 'Pending' }}</span></td><td class="pe-4 text-nowrap">{{ $order->order_date ? $order->order_date->format('d M, h:i A') : '—' }}</td></tr>
                    @empty
                        <tr><td colspan="6"><div class="aura-empty"><i class="bi bi-receipt"></i>No orders yet. Your first customer order will appear here.</div></td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="aura-panel">
                <div class="aura-panel-head"><div><div class="aura-section-title">Stock watch</div><div class="aura-section-sub">Low inventory that needs a restock</div></div><span class="aura-mini-badge {{ $lowStockProducts->count() ? 'pending' : '' }}">{{ $lowStockProducts->count() }} alerts</span></div>
                <div class="aura-panel-body">
                    @forelse($lowStockProducts as $product)
                        <div class="aura-stock-row {{ !$loop->last ? 'border-bottom' : '' }}"><span class="aura-stock-icon"><i class="bi bi-box-seam"></i></span><div class="flex-grow-1 min-w-0"><div class="small fw-bold text-truncate">{{ $product->product_name }}</div><div class="small text-muted">{{ $product->category->category_name ?? 'Uncategorized' }}</div></div><span class="aura-mini-badge {{ (int)$product->qty <= 2 ? 'cancelled' : 'pending' }}">{{ (int)$product->qty }} left</span></div>
                    @empty
                        <div class="aura-empty"><i class="bi bi-check-circle"></i>All active products have healthy stock levels.</div>
                    @endforelse
                    <a href="{{ route('inventory.index') }}" class="btn btn-light w-100 mt-3 rounded-3 fw-bold small"><i class="bi bi-box-arrow-in-right me-1"></i> Open inventory management</a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const canvas = document.getElementById('salesTrendChart');
    if (!canvas || typeof Chart === 'undefined') return;
    const labels = @json($salesTrend->pluck('label')->values());
    const values = @json($salesTrend->pluck('revenue')->values());
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(39,128,82,.22)');
    gradient.addColorStop(1, 'rgba(39,128,82,0)');
    new Chart(canvas, {
        type: 'line',
        data: { labels, datasets: [{ label: 'Revenue (USD)', data: values, borderColor: '#238052', backgroundColor: gradient, borderWidth: 3, pointRadius: 4, pointHoverRadius: 6, pointBackgroundColor: '#ffffff', pointBorderColor: '#238052', pointBorderWidth: 2, fill: true, lineTension: .36 }] },
        options: { responsive: true, maintainAspectRatio: false, legend: { display: false }, tooltips: { backgroundColor: '#18382a', titleFontFamily: 'DM Sans', bodyFontFamily: 'DM Sans', cornerRadius: 9, displayColors: false, callbacks: { label: item => '$' + Number(item.yLabel || 0).toFixed(2) } }, scales: { yAxes: [{ ticks: { beginAtZero: true, fontColor: '#87938b', callback: value => '$' + value }, gridLines: { color: '#edf2ee', drawBorder: false } }], xAxes: [{ ticks: { fontColor: '#7c8980' }, gridLines: { display: false } }] } }
    });
})();
</script>
@endsection
