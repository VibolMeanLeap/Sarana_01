@extends('layout.web')
@section('web_title', 'Customer inbox · '.($shopSettings?->shop_name ?: 'Coffee Corner'))
@section('content')
<style>
    .inbox-page{background:#f6f9f6;min-height:70vh;padding:42px 0 72px}.inbox-shell{max-width:1040px}.inbox-hero{background:linear-gradient(125deg,#102b1e,#1e563b);border-radius:24px;padding:clamp(25px,4vw,38px);color:#fff}.inbox-hero h1{font-weight:900;letter-spacing:-.045em}.inbox-card{background:#fff;border:1px solid #e5eee7;border-radius:18px;box-shadow:0 10px 28px rgba(23,59,38,.04)}.inbox-row{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:20px 22px;border-bottom:1px solid #edf2ee;color:inherit;text-decoration:none}.inbox-row:last-child{border-bottom:0}.inbox-row:hover{background:#f9fcfa}.inbox-subject{font-weight:850;color:#213a2c}.inbox-meta{font-size:.82rem;color:#77847b}.inbox-pill{font-size:.72rem;font-weight:800;border-radius:30px;padding:6px 10px}.inbox-unread{background:#e9f5ed;color:#176b43}
    .inbox-live-indicator{font-size:.75rem;color:#e1f0e5}.inbox-live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:#8fe0a8;margin-right:7px;box-shadow:0 0 0 4px #8fe0a822}
</style>
<section class="inbox-page">
    <div class="container inbox-shell">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.14em">Customer care</div><h1 class="h2 fw-bold mb-1" style="color:#173426">Your inbox</h1><p class="text-muted mb-0">Questions, replies and follow-ups with the {{ $shopSettings?->shop_name ?: 'shop' }} team.</p></div>
            <a href="{{ route('web.contact') }}" class="btn btn-success rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>New enquiry</a>
        </div>
        <div class="inbox-hero mb-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><div class="small fw-bold text-uppercase mb-2" style="letter-spacing:.13em;color:#e9c98f">Store conversations</div><h2 class="h3 mb-1">A direct line to our team.</h2><p class="small mb-0 text-white-50">Our automatic acknowledgement confirms receipt. A team member will reply in the same conversation.</p></div><div class="text-end"><div class="display-6 fw-bold" id="customer-inbox-unread">{{ $unreadCount }}</div><div class="small text-white-50">Unread replies</div><div class="inbox-live-indicator mt-2"><span class="inbox-live-dot"></span>Live updates enabled</div></div></div></div>
        @if(session('success'))<div class="alert alert-success border-0 rounded-4">{{ session('success') }}</div>@endif
        <div class="inbox-card overflow-hidden" id="customer-inbox-list" aria-live="polite">
            @include('website.inbox.partials.message-list', ['messages' => $messages])
        </div>
        <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-check me-1 text-success"></i>Your conversations are private to your signed-in customer account. This inbox checks for new replies automatically while the page is open.</p>
    </div>
</section>
<script>
(() => {
    const updatesUrl = @json(route('web.inbox.updates', request()->query()));
    const list = document.getElementById('customer-inbox-list');
    const unread = document.getElementById('customer-inbox-unread');
    let busy = false;
    let stopped = false;

    const updateHeaderBadges = (count) => {
        document.querySelectorAll('[data-store-inbox-count]').forEach((badge) => {
            badge.textContent = String(count);
            badge.hidden = Number(count) < 1;
        });
    };

    const poll = async () => {
        if (busy || stopped || document.visibilityState !== 'visible') return;
        busy = true;
        try {
            const response = await fetch(updatesUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!response.ok) return;
            const data = await response.json();
            if (typeof data.html === 'string' && list) list.innerHTML = data.html;
            if (unread) unread.textContent = String(data.unread_count ?? 0);
            updateHeaderBadges(data.unread_count ?? 0);
        } catch (error) {
            // Keep the inbox usable if the connection briefly drops.
        } finally {
            busy = false;
        }
    };

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') poll();
    });
    window.setInterval(poll, 10000);
})();
</script>
@endsection
