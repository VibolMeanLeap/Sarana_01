@extends('layout.admin')
@section('page_title', 'Customer conversations')
@section('topbar_title', 'Customer Messenger')

@push('styles')
<style>
    .cs-messenger{--cs-ink:#203329;--cs-muted:#829087;--cs-line:#e7ede8;--cs-green:#176b4b;--cs-green-dark:#104d38;color:var(--cs-ink)}
    .cs-messenger *{box-sizing:border-box}
    .cs-messenger-hero{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 25px;margin-bottom:17px;border-radius:19px;color:#fff;background:radial-gradient(circle at 90% 10%,rgba(226,194,137,.18),transparent 25%),linear-gradient(120deg,#102d21,#1b583c);box-shadow:0 12px 28px rgba(20,67,43,.10)}
    .cs-messenger-eyebrow{font-size:.65rem;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#e8c990;margin-bottom:7px}
    .cs-messenger-title{font-size:1.45rem;font-weight:900;letter-spacing:-.045em;margin:0}
    .cs-messenger-subtitle{font-size:.78rem;color:rgba(255,255,255,.68);margin:5px 0 0}
    .cs-live-state{display:inline-flex;align-items:center;gap:8px;padding:8px 11px;border:1px solid rgba(255,255,255,.14);border-radius:99px;background:rgba(255,255,255,.09);font-size:.69rem;font-weight:800;white-space:nowrap}
    .cs-live-dot{width:7px;height:7px;background:#79d69c;border-radius:50%;box-shadow:0 0 0 4px rgba(121,214,156,.13)}
    .cs-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px;margin-bottom:17px}
    .cs-stat{padding:13px 15px;border:1px solid var(--cs-line);border-radius:14px;background:#fff;min-width:0}
    .cs-stat-label{font-size:.66rem;text-transform:uppercase;letter-spacing:.07em;color:#8a958e;font-weight:850}
    .cs-stat-value{font-size:1.35rem;line-height:1.3;font-weight:900;letter-spacing:-.04em;color:#233a2c;margin-top:3px}
    .cs-stat--new .cs-stat-value{color:#3975b7}.cs-stat--progress .cs-stat-value{color:#a47725}.cs-stat--resolved .cs-stat-value{color:#24754d}.cs-stat--unread .cs-stat-value{color:#b14c43}
    .cs-messenger-grid{display:grid;grid-template-columns:minmax(290px,355px) minmax(0,1fr);gap:15px;align-items:stretch;min-height:650px}
    .cs-inbox-panel,.cs-chat-host{background:#fff;border:1px solid var(--cs-line);border-radius:17px;box-shadow:0 5px 18px rgba(27,54,38,.035);overflow:hidden;min-width:0}
    .cs-inbox-panel{display:flex;flex-direction:column;min-height:650px}
    .cs-inbox-heading{display:flex;align-items:center;justify-content:space-between;padding:17px 17px 13px}
    .cs-inbox-heading h2{font-size:.93rem;font-weight:900;letter-spacing:-.02em;margin:0}
    .cs-inbox-heading span{font-size:.66rem;color:#87928b}
    .cs-search-wrap{padding:0 13px 13px;border-bottom:1px solid #edf1ed}
    .cs-search-input{position:relative}
    .cs-search-input i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#99a49c;font-size:.83rem}
    .cs-search-input input{width:100%;height:39px;padding:0 12px 0 34px;border:1px solid #e2e9e3;border-radius:10px;background:#fafcfa;color:#314437;font-size:.75rem;outline:0}
    .cs-search-input input:focus{border-color:#8ab59a;box-shadow:0 0 0 3px rgba(23,107,75,.07);background:#fff}
    .cs-filter-tabs{display:flex;gap:5px;overflow-x:auto;padding-top:10px;scrollbar-width:thin}
    .cs-filter-tabs button{border:1px solid transparent;border-radius:99px;padding:6px 10px;background:#f4f7f4;color:#728077;font-size:.65rem;font-weight:850;white-space:nowrap}
    .cs-filter-tabs button.is-active{background:#eaf4ed;color:#176b4b;border-color:#d8e9dc}
    .cs-conversation-list{flex:1;overflow:auto;max-height:650px}
    .cs-conversation-items{display:flex;flex-direction:column}
    .cs-conversation-item{display:flex;align-items:center;gap:11px;padding:13px 13px;text-decoration:none;color:inherit;border-bottom:1px solid #f0f3f0;min-width:0;transition:background .15s}
    .cs-conversation-item:hover{background:#f8fbf8;color:inherit}
    .cs-conversation-item.is-active{background:#edf6ef;box-shadow:inset 3px 0 #24764d}
    .cs-list-avatar,.cs-chat-avatar{width:42px;height:42px;flex:0 0 42px;border-radius:15px;display:grid;place-items:center;background:linear-gradient(145deg,#e6f1e9,#d7e8dc);color:#236845;font-size:.91rem;font-weight:900}
    .cs-list-main{display:block;min-width:0;flex:1}
    .cs-list-topline,.cs-list-bottomline{display:flex;align-items:center;justify-content:space-between;gap:7px;min-width:0}
    .cs-list-name{font-size:.76rem;color:#2d4034;font-weight:900;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .cs-list-topline time{font-size:.59rem;color:#99a39c;white-space:nowrap;flex:0 0 auto}
    .cs-list-preview{display:block;color:#87928b;font-size:.68rem;line-height:1.45;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin:3px 0 5px}
    .cs-conversation-item.is-unread .cs-list-preview{color:#45594b;font-weight:750}
    .cs-topic-mini{font-size:.59rem;color:#829087;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cs-status-dot{font-size:.56rem;font-weight:850;white-space:nowrap}
    .cs-status-dot--new{color:#3c77b8}.cs-status-dot--in_progress{color:#a47725}.cs-status-dot--resolved{color:#288055}
    .cs-unread-indicator{width:18px;height:18px;flex:0 0 18px;display:grid;place-items:center;border-radius:50%;background:#176b4b;color:white;font-size:.58rem;font-weight:900}
    .cs-unread-indicator i{font-size:.42rem}
    .cs-list-empty{display:flex;align-items:center;justify-content:center;flex-direction:column;gap:7px;padding:55px 18px;text-align:center;color:#87928b}
    .cs-list-empty-icon{width:49px;height:49px;border-radius:16px;background:#edf5ef;color:#28734d;display:grid;place-items:center;font-size:1.2rem;margin-bottom:3px}
    .cs-list-empty strong{font-size:.79rem;color:#405348}.cs-list-empty span{font-size:.68rem;max-width:220px;line-height:1.5}
    .cs-list-pagination{padding:10px 12px;border-top:1px solid #edf1ed}.cs-list-pagination .pagination{margin:0;justify-content:center}.cs-list-pagination .page-link{font-size:.65rem;padding:5px 9px;color:#286b49}
    .cs-chat-host{min-height:650px;display:flex;flex-direction:column}
    .cs-chat-panel{display:flex;flex-direction:column;min-height:650px;height:100%;min-width:0}
    .cs-chat-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 19px;border-bottom:1px solid #edf1ed;background:#fff}
    .cs-chat-person{display:flex;align-items:center;gap:11px;min-width:0}
    .cs-chat-avatar{width:39px;height:39px;flex-basis:39px;border-radius:14px}
    .cs-chat-person h2{font-size:.86rem;font-weight:900;margin:0;color:#293b30;overflow-wrap:anywhere}
    .cs-chat-person p{font-size:.66rem;color:#87928b;margin:3px 0 0;overflow-wrap:anywhere}
    .cs-chat-header-actions{display:flex;align-items:center;gap:8px}
    .cs-case-status{display:inline-flex;align-items:center;gap:6px;border-radius:99px;padding:7px 10px;font-size:.63rem;font-weight:900;white-space:nowrap}
    .cs-case-status--new{background:#eef4ff;color:#3a70b2}.cs-case-status--in_progress{background:#fff5df;color:#946d20}.cs-case-status--resolved{background:#eaf6ee;color:#28734d}
    .cs-icon-btn{width:33px;height:33px;border:1px solid #e5ece6;border-radius:10px;background:#fff;color:#708076;display:grid;place-items:center}
    .cs-icon-btn:hover{background:#f1f7f2;color:#176b4b}
    .cs-details-collapse{padding:12px 18px;background:#f8fbf8;border-bottom:1px solid #edf1ed}
    .cs-details-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
    .cs-details-grid div{min-width:0}.cs-details-grid span{display:block;font-size:.57rem;text-transform:uppercase;letter-spacing:.08em;color:#929d95;font-weight:900;margin-bottom:4px}.cs-details-grid strong{display:block;color:#4c5d52;font-size:.68rem;overflow-wrap:anywhere}
    .cs-guest-notice{margin-top:10px;padding:9px 11px;border-radius:9px;background:#fff8e9;color:#896b36;font-size:.65rem;line-height:1.5}
    .cs-thread-scroll{flex:1;min-height:300px;max-height:470px;overflow-y:auto;padding:21px 22px;background:linear-gradient(180deg,#fbfcfb,#fff)}
    .cs-day-divider{display:flex;align-items:center;gap:10px;margin:0 0 17px;color:#9aa49d;font-size:.59rem;font-weight:900;letter-spacing:.09em;text-transform:uppercase}.cs-day-divider:before,.cs-day-divider:after{content:"";height:1px;background:#e9eeea;flex:1}
    .cs-message{display:flex;align-items:flex-end;gap:9px;margin:0 0 15px;max-width:88%;min-width:0}.cs-message--staff{margin-left:auto;flex-direction:row-reverse}.cs-message--system{max-width:100%;justify-content:center}
    .cs-message-avatar{width:29px;height:29px;flex:0 0 29px;border-radius:10px;background:#e8f1eb;color:#286b49;display:grid;place-items:center;font-size:.67rem;font-weight:900}
    .cs-message-avatar--staff{background:#176b4b;color:#fff}
    .cs-message-content{min-width:0;max-width:100%}.cs-message-meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin:0 3px 5px;color:#98a29b;font-size:.59rem}.cs-message-meta strong{color:#506257;font-weight:900}.cs-message--staff .cs-message-meta{justify-content:flex-end}.cs-message-bubble{padding:10px 13px;border:1px solid #e5ece6;border-radius:14px 14px 14px 4px;background:#fff;color:#4a5c50;font-size:.75rem;line-height:1.65;white-space:pre-wrap;overflow-wrap:anywhere;box-shadow:0 3px 10px rgba(30,53,38,.025)}
    .cs-message--customer .cs-message-bubble{background:#f0f6f1;border-color:#e0ebe2;color:#344b3c}.cs-message--staff .cs-message-bubble{background:#176b4b;border-color:#176b4b;color:#fff;border-radius:14px 14px 4px 14px}.cs-message--system .cs-message-content{width:min(100%,540px);text-align:center}.cs-message--system .cs-message-meta{justify-content:center}.cs-message--system .cs-message-bubble{background:#fff8e9;border-color:#f0e4c8;color:#776139;text-align:left;border-radius:12px}.cs-message-tag{font-size:.5rem;letter-spacing:.08em;font-weight:900;color:#a07d3f}
    .cs-no-replies{margin:18px auto 4px;max-width:400px;text-align:center;color:#929d95;font-size:.66rem;padding:11px}
    .cs-composer-wrap{padding:14px 17px 15px;border-top:1px solid #edf1ed;background:#fff}.cs-composer-wrap textarea{width:100%;resize:vertical;min-height:66px;max-height:150px;padding:11px 13px;border:1px solid #dfe8e1;border-radius:12px;background:#fafcfa;color:#304237;font-size:.75rem;line-height:1.6;outline:0}.cs-composer-wrap textarea:focus{border-color:#83b295;box-shadow:0 0 0 3px rgba(23,107,75,.08);background:#fff}.cs-composer-bottom{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:9px}.cs-composer-bottom>span{font-size:.62rem;color:#929d95}.cs-send-button{display:inline-flex;align-items:center;gap:9px;padding:9px 14px;border:0;border-radius:10px;background:#176b4b;color:#fff;font-size:.7rem;font-weight:900;box-shadow:0 5px 12px rgba(23,107,75,.13)}.cs-send-button:hover{background:#104d38}.cs-send-button:disabled{opacity:.55;cursor:wait}.cs-composer-error{display:none;padding:8px 10px;margin-bottom:9px;border-radius:9px;background:#fff0ef;color:#a63d36;font-size:.67rem}.cs-composer-error.is-visible{display:block}
    .cs-manage-details{border-top:1px solid #edf1ed;background:#fbfcfb}.cs-manage-details summary{display:flex;align-items:center;gap:5px;padding:12px 18px;color:#64746a;font-size:.67rem;font-weight:900;cursor:pointer;list-style:none}.cs-manage-details summary::-webkit-details-marker{display:none}.cs-manage-details summary span{margin-left:auto;color:#9aa49d;font-weight:600;font-size:.6rem}.cs-manage-form{padding:2px 18px 15px}.cs-manage-form label{font-size:.63rem;color:#65756b;font-weight:850}.cs-manage-form .form-control,.cs-manage-form .form-select{border-radius:9px;border-color:#e0e8e1;font-size:.7rem;box-shadow:none}.cs-save-status{margin-top:11px;border:1px solid #dce8de;border-radius:9px;background:#f2f8f3;color:#2e6948;padding:8px 11px;font-size:.66rem;font-weight:850}.cs-save-status:hover{background:#e8f3ea}
    .cs-empty-chat{display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;flex:1;min-height:650px;padding:30px;text-align:center;background:radial-gradient(circle at 50% 40%,#f3f8f4,transparent 55%)}.cs-empty-chat-icon{width:74px;height:74px;border-radius:25px;background:#eaf4ed;color:#28734d;display:grid;place-items:center;font-size:1.8rem}.cs-empty-chat h2{font-size:1rem;font-weight:900;color:#34493b;margin:5px 0 0}.cs-empty-chat p{max-width:300px;color:#8a958e;font-size:.73rem;line-height:1.6;margin:0}
    @media(max-width:1050px){.cs-messenger-grid{grid-template-columns:minmax(260px,320px) minmax(0,1fr)}.cs-details-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:800px){.cs-messenger-grid{grid-template-columns:1fr}.cs-inbox-panel{min-height:360px}.cs-conversation-list{max-height:360px}.cs-chat-host,.cs-chat-panel{min-height:580px}.cs-thread-scroll{max-height:420px}.cs-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:520px){.cs-messenger-hero{align-items:flex-start;padding:18px}.cs-messenger-title{font-size:1.2rem}.cs-live-state{font-size:0}.cs-live-dot{margin:2px}.cs-stats{gap:7px}.cs-stat{padding:10px}.cs-stat-value{font-size:1.15rem}.cs-chat-header{padding:13px}.cs-thread-scroll{padding:16px 13px}.cs-message{max-width:96%}.cs-composer-wrap{padding:12px}.cs-details-grid{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="cs-messenger" id="csMessenger"
     data-list-updates-url="{{ route('admin.customer-service.updates') }}"
     data-index-url="{{ route('admin.customer-service.index') }}">
    <section class="cs-messenger-hero">
        <div>
            <div class="cs-messenger-eyebrow"><i class="bi bi-chat-heart-fill me-1"></i> Coffee Corner Aura · Customer care</div>
            <h1 class="cs-messenger-title">Customer conversations</h1>
            <p class="cs-messenger-subtitle">Every enquiry and follow-up in one live support inbox.</p>
        </div>
        <div class="cs-live-state"><span class="cs-live-dot"></span><span>Live inbox · auto-updating</span></div>
    </section>

    <section class="cs-stats" aria-label="Customer service summary">
        <div class="cs-stat"><div class="cs-stat-label"><i class="bi bi-inbox me-1"></i>All conversations</div><div class="cs-stat-value" data-cs-count="total">{{ number_format($counts['total']) }}</div></div>
        <div class="cs-stat cs-stat--new"><div class="cs-stat-label"><i class="bi bi-stars me-1"></i>New</div><div class="cs-stat-value" data-cs-count="new">{{ number_format($counts['new']) }}</div></div>
        <div class="cs-stat cs-stat--progress"><div class="cs-stat-label"><i class="bi bi-hourglass-split me-1"></i>In progress</div><div class="cs-stat-value" data-cs-count="in_progress">{{ number_format($counts['in_progress']) }}</div></div>
        <div class="cs-stat cs-stat--unread"><div class="cs-stat-label"><i class="bi bi-chat-left-dots me-1"></i>Needs attention</div><div class="cs-stat-value" data-cs-count="unread">{{ number_format($counts['unread']) }}</div></div>
    </section>

    <div class="cs-messenger-grid">
        <aside class="cs-inbox-panel">
            <div class="cs-inbox-heading"><h2><i class="bi bi-chat-square-text-fill text-success me-2"></i>Inbox</h2><span><span data-cs-count="total">{{ number_format($counts['total']) }}</span> conversations</span></div>
            <div class="cs-search-wrap">
                <form method="GET" action="{{ route('admin.customer-service.index') }}" id="csSearchForm">
                    <input type="hidden" name="status" id="csStatusFilter" value="{{ request('status') }}">
                    <div class="cs-search-input"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" placeholder="Search customers or messages…" aria-label="Search conversations"></div>
                    <div class="cs-filter-tabs" aria-label="Filter conversations">
                        <button type="button" data-cs-filter="" class="{{ !request('status') ? 'is-active' : '' }}">All</button>
                        <button type="button" data-cs-filter="new" class="{{ request('status') === 'new' ? 'is-active' : '' }}">New</button>
                        <button type="button" data-cs-filter="in_progress" class="{{ request('status') === 'in_progress' ? 'is-active' : '' }}">In progress</button>
                        <button type="button" data-cs-filter="resolved" class="{{ request('status') === 'resolved' ? 'is-active' : '' }}">Resolved</button>
                    </div>
                </form>
            </div>
            <div class="cs-conversation-list" id="csConversationList">
                @include('admin.customer-service.partials.conversation-list', ['messages' => $messages, 'selectedId' => $contactMessage?->id ?? 0])
            </div>
        </aside>

        <main class="cs-chat-host" id="csChatHost" aria-label="Selected customer conversation">
            @if ($contactMessage)
                @include('admin.customer-service.partials.thread-panel', ['contactMessage' => $contactMessage, 'lastReplyId' => $lastReplyId])
            @else
                <div class="cs-empty-chat">
                    <div class="cs-empty-chat-icon"><i class="bi bi-chat-dots-fill"></i></div>
                    <h2>Your customer inbox is ready</h2>
                    <p>Select a conversation on the left to read the full message history and reply to the customer.</p>
                </div>
            @endif
        </main>
    </div>
</div>
@endsection

@section('scripts')
<script>
(() => {
    const root = document.getElementById('csMessenger');
    if (!root) return;

    const chatHost = document.getElementById('csChatHost');
    const listHost = document.getElementById('csConversationList');
    const searchForm = document.getElementById('csSearchForm');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let listBusy = false;
    let threadBusy = false;

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                ...(options.headers || {})
            }
        });
        let payload = {};
        try { payload = await response.json(); } catch (_) {}
        if (!response.ok) {
            const messages = payload.errors ? Object.values(payload.errors).flat().join(' ') : (payload.message || `Request failed (${response.status}).`);
            throw new Error(messages);
        }
        return payload;
    };

    const updateCounts = (counts = {}) => {
        Object.entries(counts).forEach(([key, value]) => {
            document.querySelectorAll(`[data-cs-count="${key}"]`).forEach(node => node.textContent = Number(value || 0).toLocaleString());
        });
    };

    const updateAdminBadges = (payload) => {
        if (payload?.admin_counts && typeof window.CoffeeCornerAdminApplyCounts === 'function') {
            window.CoffeeCornerAdminApplyCounts(payload.admin_counts);
        }
    };

    const activePanel = () => chatHost.querySelector('[data-cs-thread-panel]');

    const refreshList = async () => {
        if (listBusy || document.visibilityState !== 'visible') return;
        listBusy = true;
        try {
            const url = new URL(root.dataset.listUpdatesUrl, window.location.origin);
            const params = new URLSearchParams(new FormData(searchForm));
            const currentPage = new URL(window.location.href).searchParams.get('page');
            if (currentPage) params.set('page', currentPage);
            const current = activePanel();
            if (current) params.set('conversation', current.dataset.conversationId);
            url.search = params.toString();
            url.searchParams.set('_poll', String(Date.now()));
            const payload = await requestJson(url.toString());
            if (payload.list_html !== undefined) listHost.innerHTML = payload.list_html;
            updateCounts(payload.counts || {});
            updateAdminBadges(payload);
        } catch (_) {
            // Retry on the next interval; the server-rendered inbox remains usable.
        } finally {
            listBusy = false;
        }
    };

    const refreshThread = async () => {
        const panel = activePanel();
        if (!panel || threadBusy || document.visibilityState !== 'visible') return;
        threadBusy = true;
        try {
            const url = new URL(panel.dataset.threadUpdatesUrl, window.location.origin);
            url.searchParams.set('after_id', panel.dataset.lastReplyId || '0');
            url.searchParams.set('_poll', String(Date.now()));
            const payload = await requestJson(url.toString());
            if (payload.html && payload.html.trim()) {
                chatHost.querySelector('[data-cs-no-replies]')?.remove();
                const feed = chatHost.querySelector('#csReplyFeed');
                if (feed) {
                    feed.insertAdjacentHTML('beforeend', payload.html);
                    const body = chatHost.querySelector('#csThreadBody');
                    if (body) body.scrollTop = body.scrollHeight;
                }
            }
            panel.dataset.lastReplyId = String(payload.last_reply_id ?? panel.dataset.lastReplyId);
            if (payload.status) setStatusBadge(payload.status);
            updateCounts(payload.counts || {});
        } catch (_) {
            // Keep the chat interactive if the network is temporarily unavailable.
        } finally {
            threadBusy = false;
        }
    };

    const setStatusBadge = (status) => {
        const label = status === 'in_progress' ? 'In progress' : status.charAt(0).toUpperCase() + status.slice(1);
        const badge = chatHost.querySelector('[data-cs-status-label]');
        if (badge) {
            badge.className = `cs-case-status cs-case-status--${status}`;
            badge.innerHTML = `<i class="bi ${status === 'resolved' ? 'bi-check-circle-fill' : (status === 'in_progress' ? 'bi-hourglass-split' : 'bi-stars')}"></i> ${label}`;
        }
        const select = chatHost.querySelector('[data-cs-update-form] select[name="status"]');
        if (select) select.value = status;
    };

    const showFeedback = (panel, message, isError = false) => {
        const box = panel.querySelector('[data-cs-feedback]');
        if (!box) return;
        box.textContent = message;
        box.classList.toggle('is-visible', Boolean(message));
        box.style.background = isError ? '#fff0ef' : '#eff8f1';
        box.style.color = isError ? '#a63d36' : '#286c45';
    };

    document.addEventListener('click', async (event) => {
        const filter = event.target.closest('[data-cs-filter]');
        if (filter) {
            const statusInput = document.getElementById('csStatusFilter');
            if (statusInput) statusInput.value = filter.dataset.csFilter || '';
            searchForm.querySelectorAll('[data-cs-filter]').forEach(button => button.classList.toggle('is-active', button === filter));
            searchForm.submit();
            return;
        }

        const link = event.target.closest('[data-cs-open-thread]');
        if (!link) return;
        event.preventDefault();
        const previous = chatHost.innerHTML;
        chatHost.setAttribute('aria-busy', 'true');
        try {
            const payload = await requestJson(link.dataset.threadUrl);
            chatHost.innerHTML = payload.html;
            window.history.pushState({ conversation: link.dataset.conversationId }, '', link.href);
            listHost.querySelectorAll('[data-cs-open-thread]').forEach(item => {
                item.classList.toggle('is-active', item.dataset.conversationId === link.dataset.conversationId);
                item.setAttribute('aria-current', item.dataset.conversationId === link.dataset.conversationId ? 'true' : 'false');
            });
            updateCounts(payload.counts || {});
            updateAdminBadges(payload);
            const body = chatHost.querySelector('#csThreadBody');
            if (body) body.scrollTop = body.scrollHeight;
        } catch (error) {
            chatHost.innerHTML = previous;
            const notice = document.createElement('div');
            notice.className = 'alert alert-danger m-3';
            notice.textContent = error.message;
            chatHost.prepend(notice);
        } finally {
            chatHost.removeAttribute('aria-busy');
        }
    });

    document.addEventListener('submit', async (event) => {
        const replyForm = event.target.closest('[data-cs-reply-form]');
        if (replyForm) {
            event.preventDefault();
            const panel = activePanel();
            if (!panel) return;
            const button = replyForm.querySelector('button[type="submit"]');
            const textarea = replyForm.querySelector('textarea[name="body"]');
            const formData = new FormData(replyForm);
            if (button) { button.disabled = true; button.innerHTML = '<span>Sending…</span><i class="bi bi-arrow-repeat"></i>'; }
            showFeedback(panel, '');
            try {
                const payload = await requestJson(replyForm.action, { method: 'POST', body: formData });
                const empty = panel.querySelector('[data-cs-no-replies]');
                empty?.remove();
                const feed = panel.querySelector('#csReplyFeed');
                if (feed && payload.reply_html) feed.insertAdjacentHTML('beforeend', payload.reply_html);
                panel.dataset.lastReplyId = String(payload.last_reply_id || panel.dataset.lastReplyId);
                if (textarea) { textarea.value = ''; textarea.focus(); }
                setStatusBadge(payload.status || 'in_progress');
                const body = panel.querySelector('#csThreadBody');
                if (body) body.scrollTop = body.scrollHeight;
                showFeedback(panel, payload.message || 'Reply sent.');
                updateCounts(payload.counts || {});
                await refreshList();
            } catch (error) {
                showFeedback(panel, error.message, true);
            } finally {
                if (button) { button.disabled = false; button.innerHTML = '<span>Send</span><i class="bi bi-send-fill"></i>'; }
            }
            return;
        }

        const updateForm = event.target.closest('[data-cs-update-form]');
        if (updateForm) {
            event.preventDefault();
            const panel = activePanel();
            const button = updateForm.querySelector('button[type="submit"],button:not([type])');
            if (button) { button.disabled = true; button.textContent = 'Saving…'; }
            try {
                const payload = await requestJson(updateForm.action, { method: 'POST', body: new FormData(updateForm) });
                setStatusBadge(payload.status || updateForm.querySelector('[name="status"]').value);
                showFeedback(panel, payload.message || 'Conversation updated.');
                updateCounts(payload.counts || {});
                await refreshList();
            } catch (error) {
                showFeedback(panel, error.message, true);
            } finally {
                if (button) { button.disabled = false; button.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Save conversation details'; }
            }
        }
    });

    // Search can still be submitted normally; polling uses the current search/filter values.
    searchForm?.addEventListener('submit', () => {});
    refreshList();
    refreshThread();
    window.setInterval(refreshList, 7000);
    window.setInterval(refreshThread, 4500);
    window.addEventListener('popstate', () => {
        const url = new URL(window.location.href);
        const conversationId = url.searchParams.get('conversation');
        if (!conversationId) return;
        const link = listHost.querySelector(`[data-cs-open-thread][data-conversation-id="${conversationId}"]`);
        if (link) link.click();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') { refreshList(); refreshThread(); }
    });
})();
</script>
@endsection
