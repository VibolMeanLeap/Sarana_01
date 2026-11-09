@extends('layout.admin')

@section('page_title', 'Enquiry #'.$contactMessage->id)
@section('topbar_title', 'Customer enquiry')

@php
    $status = $contactMessage->status ?: 'new';
    $statusLabels = [
        'new' => 'New enquiry',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
    ];
    $statusIcons = [
        'new' => 'bi-stars',
        'in_progress' => 'bi-hourglass-split',
        'resolved' => 'bi-check2-circle',
    ];
    $statusLabel = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    $statusIcon = $statusIcons[$status] ?? 'bi-chat-square-text';
    $customerName = $contactMessage->name ?: 'Store customer';
    $customerInitial = mb_strtoupper(mb_substr($customerName, 0, 1));
    $topicLabel = ucfirst(str_replace('_', ' ', $contactMessage->topic ?: 'General enquiry'));
    $phoneHref = preg_replace('/[^0-9+]/', '', (string) $contactMessage->phone);
@endphp

@push('styles')
<style>
    .cs-view{--cs-ink:#203229;--cs-muted:#7c8981;--cs-line:#e8eee9;--cs-soft:#f5f8f5;--cs-green:#176b4b;--cs-green-dark:#104d38;color:var(--cs-ink)}
    .cs-view *{box-sizing:border-box}
    .cs-view .min-w-0{min-width:0}
    .cs-back-link{display:inline-flex;align-items:center;gap:9px;color:#536159;text-decoration:none;font-weight:750;font-size:.82rem;padding:9px 12px;border:1px solid var(--cs-line);background:#fff;border-radius:11px;transition:.18s}
    .cs-back-link:hover{color:var(--cs-green);border-color:#c8dbce;background:#f8fbf8;transform:translateX(-2px)}
    .cs-view-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px;flex-wrap:wrap}
    .cs-breadcrumb{font-size:.73rem;color:#89958d;font-weight:700;letter-spacing:.02em}
    .cs-breadcrumb i{color:#b4beb7;margin:0 6px}
    .cs-status{display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:8px 12px;font-size:.73rem;font-weight:850;letter-spacing:.01em;white-space:nowrap}
    .cs-status--new{background:#edf4ff;color:#3266a4;border:1px solid #dce9fb}
    .cs-status--progress{background:#fff5df;color:#936515;border:1px solid #f3e5bf}
    .cs-status--resolved{background:#eaf6ee;color:#28734d;border:1px solid #d8ebde}
    .cs-hero{position:relative;overflow:hidden;border-radius:21px;padding:25px 28px;color:#fff;background:radial-gradient(circle at 88% 12%,rgba(222,190,130,.2),transparent 25%),radial-gradient(circle at 2% 100%,rgba(108,167,128,.2),transparent 32%),linear-gradient(120deg,#102d21 0%,#174b35 58%,#1c6042 100%);box-shadow:0 14px 35px rgba(17,67,43,.12);margin-bottom:20px}
    .cs-hero:after{content:"";position:absolute;width:250px;height:250px;border:1px solid rgba(255,255,255,.08);border-radius:50%;right:-88px;bottom:-170px;box-shadow:0 0 0 28px rgba(255,255,255,.025),0 0 0 58px rgba(255,255,255,.02);pointer-events:none}
    .cs-hero-inner{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:22px}
    .cs-hero-eyebrow{display:flex;align-items:center;gap:8px;color:#e7c994;text-transform:uppercase;font-size:.68rem;font-weight:850;letter-spacing:.15em;margin-bottom:11px}
    .cs-hero-title{font-family:'Manrope','DM Sans',sans-serif;font-size:clamp(1.35rem,2.5vw,1.85rem);font-weight:850;letter-spacing:-.055em;line-height:1.18;margin:0 0 7px;overflow-wrap:anywhere}
    .cs-hero-subtitle{color:rgba(255,255,255,.68);font-size:.82rem;margin:0}
    .cs-hero-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:17px}
    .cs-hero-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:9px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.11);color:rgba(255,255,255,.88);font-size:.72rem;font-weight:700}
    .cs-profile-mark{width:70px;height:70px;flex:0 0 70px;border-radius:23px;background:linear-gradient(145deg,#f3dfb8,#d1ae70);color:#244431;display:grid;place-items:center;font-family:'Manrope',sans-serif;font-size:1.65rem;font-weight:900;box-shadow:0 12px 28px rgba(0,0,0,.16);border:4px solid rgba(255,255,255,.16)}
    .cs-grid{display:grid;grid-template-columns:minmax(0,1.72fr) minmax(285px,.88fr);gap:18px;align-items:start}
    .cs-main-column,.cs-side-column{display:grid;gap:18px;min-width:0}
    .cs-panel{background:#fff;border:1px solid var(--cs-line);border-radius:17px;box-shadow:0 5px 18px rgba(27,54,38,.035);overflow:hidden;min-width:0}
    .cs-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 21px;border-bottom:1px solid #edf1ed}
    .cs-panel-title{font-family:'Manrope','DM Sans',sans-serif;color:#26382e;font-size:.96rem;font-weight:850;letter-spacing:-.025em;margin:0}
    .cs-panel-subtitle{color:#8a958e;font-size:.73rem;margin:4px 0 0}
    .cs-panel-icon{width:36px;height:36px;border-radius:12px;background:#edf5ef;color:#28734d;display:grid;place-items:center;font-size:1rem;flex:0 0 36px}
    .cs-count{display:inline-flex;align-items:center;gap:6px;padding:6px 9px;border-radius:99px;background:#f3f6f3;color:#65736a;font-size:.68rem;font-weight:850;white-space:nowrap}
    .cs-conversation{padding:20px 21px 22px;background:linear-gradient(180deg,#fbfcfb 0%,#fff 100%)}
    .cs-thread{display:grid;gap:18px}
    .cs-thread-item{display:flex;align-items:flex-end;gap:10px;max-width:88%;min-width:0}
    .cs-thread-item--staff{margin-left:auto;flex-direction:row-reverse}
    .cs-thread-item--system{max-width:100%;justify-content:center;align-items:center}
    .cs-thread-avatar{width:31px;height:31px;flex:0 0 31px;display:grid;place-items:center;border-radius:11px;background:#e8f1eb;color:#286b49;font-size:.75rem;font-weight:900}
    .cs-thread-item--staff .cs-thread-avatar{background:#1a6244;color:#fff}
    .cs-thread-item--system .cs-thread-avatar{display:none}
    .cs-thread-content{min-width:0;max-width:100%}
    .cs-thread-meta{display:flex;align-items:center;gap:7px;margin:0 3px 6px;color:#89958d;font-size:.67rem;font-weight:700;flex-wrap:wrap}
    .cs-thread-item--staff .cs-thread-meta{justify-content:flex-end}
    .cs-thread-role{color:#45584c;font-weight:850}
    .cs-thread-bubble{padding:12px 15px;border-radius:15px;border:1px solid #e6ece7;background:#fff;color:#4b5c51;font-size:.84rem;line-height:1.75;white-space:pre-wrap;overflow-wrap:anywhere;box-shadow:0 3px 10px rgba(30,53,38,.025)}
    .cs-thread-item--customer .cs-thread-bubble{border-bottom-left-radius:5px;background:#f2f7f3;border-color:#e0ebe2;color:#344a3b}
    .cs-thread-item--staff .cs-thread-bubble{border-bottom-right-radius:5px;background:#176b4b;border-color:#176b4b;color:#fff}
    .cs-thread-item--system .cs-thread-content{width:min(100%,620px);text-align:center}
    .cs-thread-item--system .cs-thread-meta{justify-content:center}
    .cs-thread-item--system .cs-thread-bubble{background:#fff8e9;border-color:#f0e4c8;color:#776139;text-align:left;border-radius:12px;font-size:.8rem}
    .cs-original{margin-bottom:20px;padding-bottom:19px;border-bottom:1px dashed #dfe7e1}
    .cs-original-label{display:flex;align-items:center;gap:8px;color:#89958d;font-size:.66rem;font-weight:900;letter-spacing:.11em;text-transform:uppercase;margin-bottom:10px}
    .cs-original-label:after{content:"";height:1px;background:#e9eeea;flex:1}
    .cs-original .cs-thread-item{max-width:100%}
    .cs-empty-thread{padding:18px;border:1px dashed #dce6de;border-radius:13px;color:#87938a;text-align:center;font-size:.78rem;background:#fafcfa}
    .cs-composer{padding:19px 21px 21px;border-top:1px solid #edf1ed;background:#fff}
    .cs-composer-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:11px}
    .cs-composer textarea{width:100%;min-height:118px;resize:vertical;border:1px solid #dfe8e1;border-radius:13px;background:#fcfdfc;padding:13px 14px;color:#304237;font-size:.83rem;line-height:1.65;box-shadow:none;transition:border-color .18s,box-shadow .18s}
    .cs-composer textarea:focus{outline:0;border-color:#78aa8d;box-shadow:0 0 0 4px rgba(23,107,75,.09);background:#fff}
    .cs-composer textarea::placeholder{color:#a2aca5}
    .cs-composer-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:12px;flex-wrap:wrap}
    .cs-hint{font-size:.69rem;color:#8b968e}
    .cs-send-btn{display:inline-flex;align-items:center;gap:9px;border:0;border-radius:11px;background:#176b4b;color:#fff;font-size:.78rem;font-weight:850;padding:11px 16px;box-shadow:0 6px 14px rgba(23,107,75,.16);transition:.18s}
    .cs-send-btn:hover{background:#104d38;transform:translateY(-1px);box-shadow:0 9px 18px rgba(23,107,75,.2)}
    .cs-send-btn:disabled{opacity:.6;cursor:not-allowed;transform:none}
    .cs-form-error{color:#b33f3b;font-size:.73rem;margin-top:7px}
    .cs-side-body{padding:19px 20px}
    .cs-contact-person{display:flex;align-items:center;gap:12px;padding-bottom:16px;border-bottom:1px solid #edf1ed;margin-bottom:15px}
    .cs-contact-avatar{width:43px;height:43px;border-radius:15px;display:grid;place-items:center;background:linear-gradient(145deg,#e8f2eb,#d9e9de);color:#246844;font-family:'Manrope',sans-serif;font-size:1.02rem;font-weight:900}
    .cs-contact-name{font-family:'Manrope',sans-serif;font-size:.88rem;font-weight:850;color:#293b30;overflow-wrap:anywhere}
    .cs-contact-caption{font-size:.68rem;color:#89958d;margin-top:3px}
    .cs-info-row{display:flex;gap:11px;align-items:flex-start;padding:10px 0}
    .cs-info-row+.cs-info-row{border-top:1px solid #f0f3f0}
    .cs-info-icon{width:31px;height:31px;flex:0 0 31px;border-radius:10px;background:#f3f7f4;color:#5c8069;display:grid;place-items:center;font-size:.83rem}
    .cs-info-label{font-size:.63rem;letter-spacing:.08em;text-transform:uppercase;color:#929d95;font-weight:850;margin-bottom:4px}
    .cs-info-value{font-size:.76rem;font-weight:750;color:#43554a;overflow-wrap:anywhere;line-height:1.5}
    .cs-info-value a{color:#286b49;text-decoration:none}
    .cs-info-value a:hover{text-decoration:underline}
    .cs-divider-label{display:flex;align-items:center;gap:9px;margin:2px 0 12px;color:#829087;font-size:.65rem;letter-spacing:.1em;font-weight:900;text-transform:uppercase}
    .cs-divider-label:after{content:"";height:1px;background:#edf1ed;flex:1}
    .cs-select-label{font-size:.7rem;font-weight:850;color:#526158;margin-bottom:7px}
    .cs-side-body .form-select,.cs-side-body textarea{border:1px solid #dfe8e1;border-radius:11px;padding:10px 12px;font-size:.78rem;box-shadow:none}
    .cs-side-body .form-select:focus,.cs-side-body textarea:focus{border-color:#78aa8d;box-shadow:0 0 0 4px rgba(23,107,75,.08)}
    .cs-update-btn{width:100%;display:flex;justify-content:center;align-items:center;gap:8px;border:1px solid #dfe8e1;border-radius:11px;background:#f7faf7;color:#315441;padding:10px 13px;font-size:.76rem;font-weight:850;transition:.18s}
    .cs-update-btn:hover{background:#edf5ef;border-color:#c9ddcf;color:#176b4b}
    .cs-private-note{padding:10px 11px;border-radius:10px;background:#fff8e9;border:1px solid #f1e7ce;color:#886c38;font-size:.68rem;line-height:1.55;margin-bottom:13px}
    .cs-case-meta{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:15px}
    .cs-case-meta-item{padding:10px;border-radius:11px;background:#f7f9f7;min-width:0}
    .cs-case-meta-label{font-size:.6rem;text-transform:uppercase;letter-spacing:.07em;color:#929d95;font-weight:850;margin-bottom:5px}
    .cs-case-meta-value{font-size:.7rem;color:#526158;font-weight:800;overflow-wrap:anywhere}
    .cs-alert{border:1px solid #d7eadc;background:#eff8f1;color:#286c45;border-radius:12px;padding:11px 14px;font-size:.78rem;margin-bottom:16px}
    @media(max-width:1100px){.cs-grid{grid-template-columns:minmax(0,1.35fr) minmax(270px,.9fr)}}
    @media(max-width:850px){.cs-grid{grid-template-columns:1fr}.cs-side-column{grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}}
    @media(max-width:620px){.cs-hero{padding:21px 19px}.cs-hero-inner{align-items:flex-start}.cs-profile-mark{width:52px;height:52px;flex-basis:52px;border-radius:17px;font-size:1.2rem}.cs-grid{gap:13px}.cs-side-column{grid-template-columns:1fr}.cs-panel-head,.cs-conversation,.cs-composer,.cs-side-body{padding-left:15px;padding-right:15px}.cs-thread-item{max-width:96%}.cs-case-meta{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<div class="cs-view">
    <div class="cs-view-head">
        <a href="{{ route('admin.customer-service.index') }}" class="cs-back-link">
            <i class="bi bi-arrow-left"></i><span>Back to enquiries</span>
        </a>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="cs-breadcrumb">Customer service <i class="bi bi-chevron-right"></i> Enquiry #{{ $contactMessage->id }}</span>
            <span class="cs-status {{ $status === 'resolved' ? 'cs-status--resolved' : ($status === 'in_progress' ? 'cs-status--progress' : 'cs-status--new') }}">
                <i class="bi {{ $statusIcon }}"></i>{{ $statusLabel }}
            </span>
        </div>
    </div>

    <section class="cs-hero">
        <div class="cs-hero-inner">
            <div class="min-w-0">
                <div class="cs-hero-eyebrow"><i class="bi bi-chat-heart-fill"></i> Customer experience · Support case #{{ $contactMessage->id }}</div>
                <h1 class="cs-hero-title">{{ $customerName }}</h1>
                <p class="cs-hero-subtitle">A customer has reached out to the Coffee Corner Aura team.</p>
                <div class="cs-hero-meta">
                    <span class="cs-hero-chip"><i class="bi bi-tag"></i>{{ $topicLabel }}</span>
                    <span class="cs-hero-chip"><i class="bi bi-calendar3"></i>Received {{ optional($contactMessage->created_at)->format('d M Y') }}</span>
                    @if($contactMessage->order_reference)
                        <span class="cs-hero-chip"><i class="bi bi-receipt"></i>Order #{{ $contactMessage->order_reference }}</span>
                    @endif
                </div>
            </div>
            <div class="cs-profile-mark" aria-hidden="true">{{ $customerInitial }}</div>
        </div>
    </section>

    <div class="cs-grid">
        <div class="cs-main-column">
            <section class="cs-panel" aria-labelledby="cs-conversation-title">
                <div class="cs-panel-head">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <div class="cs-panel-icon"><i class="bi bi-chat-square-dots-fill"></i></div>
                        <div class="min-w-0">
                            <h2 class="cs-panel-title" id="cs-conversation-title">Conversation</h2>
                            <p class="cs-panel-subtitle">Messages exchanged between the customer and your store.</p>
                        </div>
                    </div>
                    <span class="cs-count"><i class="bi bi-chat-left-text"></i>{{ $contactMessage->replies->count() + 1 }} messages</span>
                </div>

                <div class="cs-conversation">
                    <div class="cs-original">
                        <div class="cs-original-label">Original enquiry</div>
                        <div class="cs-thread-item cs-thread-item--customer">
                            <div class="cs-thread-avatar">{{ $customerInitial }}</div>
                            <div class="cs-thread-content">
                                <div class="cs-thread-meta">
                                    <span class="cs-thread-role">{{ $customerName }}</span>
                                    <span>·</span>
                                    <time datetime="{{ optional($contactMessage->created_at)->toIso8601String() }}">{{ optional($contactMessage->created_at)->format('d M, h:i A') }}</time>
                                </div>
                                <div class="cs-thread-bubble">{{ $contactMessage->message }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="cs-thread" aria-label="Conversation replies">
                        @forelse($contactMessage->replies as $reply)
                            @php
                                $isSystemReply = $reply->sender_type === 'system';
                                $isCustomerReply = $reply->sender_type === 'customer';
                                $replySender = $isSystemReply
                                    ? 'Automatic store response'
                                    : ($isCustomerReply ? $customerName : ($reply->sender?->name ?: 'Coffee Corner team'));
                                $replyInitial = $isSystemReply ? 'S' : ($isCustomerReply ? $customerInitial : mb_strtoupper(mb_substr($replySender, 0, 1)));
                                $replyClass = $isSystemReply ? 'system' : ($isCustomerReply ? 'customer' : 'staff');
                            @endphp
                            <div class="cs-thread-item cs-thread-item--{{ $replyClass }}">
                                <div class="cs-thread-avatar">{{ $replyInitial }}</div>
                                <div class="cs-thread-content">
                                    <div class="cs-thread-meta">
                                        <span class="cs-thread-role">{{ $replySender }}</span>
                                        <span>·</span>
                                        <time datetime="{{ optional($reply->created_at)->toIso8601String() }}">{{ optional($reply->created_at)->format('d M, h:i A') }}</time>
                                    </div>
                                    <div class="cs-thread-bubble">{{ $reply->body }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="cs-empty-thread">
                                <i class="bi bi-chat-dots me-1"></i> No replies yet. Send the first response to continue this conversation.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="cs-composer">
                    <div class="cs-composer-top">
                        <div>
                            <h3 class="cs-panel-title mb-1">Write a response</h3>
                            <p class="cs-panel-subtitle">Your reply will appear in the customer's Store Account inbox.</p>
                        </div>
                        <span class="cs-panel-icon"><i class="bi bi-send-fill"></i></span>
                    </div>
                    <form method="POST" action="{{ route('admin.customer-service.reply', $contactMessage) }}" id="cs-reply-form">
                        @csrf
                        <label class="visually-hidden" for="cs-reply-body">Write your customer reply</label>
                        <textarea id="cs-reply-body" name="body" maxlength="3000" minlength="2" placeholder="Hello {{ $customerName }}, thank you for contacting Coffee Corner Aura..." required>{{ old('body') }}</textarea>
                        @error('body')<div class="cs-form-error"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                        <div class="cs-composer-foot">
                            <span class="cs-hint"><i class="bi bi-lock me-1"></i>Sent securely to this customer's inbox · 2–3,000 characters</span>
                            <button class="cs-send-btn" type="submit"><i class="bi bi-send-fill"></i>Send reply</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <aside class="cs-side-column">
            <section class="cs-panel" aria-labelledby="cs-customer-title">
                <div class="cs-panel-head">
                    <div class="d-flex align-items-center gap-3">
                        <div class="cs-panel-icon"><i class="bi bi-person-vcard-fill"></i></div>
                        <div>
                            <h2 class="cs-panel-title" id="cs-customer-title">Customer details</h2>
                            <p class="cs-panel-subtitle">Contact information for follow-up.</p>
                        </div>
                    </div>
                </div>
                <div class="cs-side-body">
                    <div class="cs-contact-person">
                        <div class="cs-contact-avatar">{{ $customerInitial }}</div>
                        <div class="min-w-0">
                            <div class="cs-contact-name">{{ $customerName }}</div>
                            <div class="cs-contact-caption">{{ $contactMessage->user_id ? 'Registered store customer' : 'Website enquiry' }}</div>
                        </div>
                    </div>
                    <div class="cs-info-row">
                        <div class="cs-info-icon"><i class="bi bi-envelope"></i></div>
                        <div class="min-w-0">
                            <div class="cs-info-label">Email address</div>
                            <div class="cs-info-value"><a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></div>
                        </div>
                    </div>
                    @if($contactMessage->phone)
                        <div class="cs-info-row">
                            <div class="cs-info-icon"><i class="bi bi-telephone"></i></div>
                            <div class="min-w-0">
                                <div class="cs-info-label">Phone number</div>
                                <div class="cs-info-value"><a href="tel:{{ $phoneHref }}">{{ $contactMessage->phone }}</a></div>
                            </div>
                        </div>
                    @endif
                    @if($contactMessage->order_reference)
                        <div class="cs-info-row">
                            <div class="cs-info-icon"><i class="bi bi-receipt"></i></div>
                            <div class="min-w-0">
                                <div class="cs-info-label">Related order</div>
                                <div class="cs-info-value">#{{ $contactMessage->order_reference }}</div>
                            </div>
                        </div>
                    @endif
                    <div class="cs-case-meta">
                        <div class="cs-case-meta-item">
                            <div class="cs-case-meta-label">Received</div>
                            <div class="cs-case-meta-value">{{ optional($contactMessage->created_at)->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="cs-case-meta-item">
                            <div class="cs-case-meta-label">Last updated</div>
                            <div class="cs-case-meta-value">{{ optional($contactMessage->updated_at)->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="cs-panel" aria-labelledby="cs-manage-title">
                <div class="cs-panel-head">
                    <div class="d-flex align-items-center gap-3">
                        <div class="cs-panel-icon"><i class="bi bi-sliders2"></i></div>
                        <div>
                            <h2 class="cs-panel-title" id="cs-manage-title">Manage enquiry</h2>
                            <p class="cs-panel-subtitle">Keep the support case organised.</p>
                        </div>
                    </div>
                </div>
                <div class="cs-side-body">
                    <form method="POST" action="{{ route('admin.customer-service.update', $contactMessage) }}">
                        @csrf
                        @method('PATCH')

                        <label class="cs-select-label d-block" for="cs-status">Enquiry status</label>
                        <select class="form-select mb-3" name="status" id="cs-status" required>
                            <option value="new" @selected(old('status', $status) === 'new')>New enquiry</option>
                            <option value="in_progress" @selected(old('status', $status) === 'in_progress')>In progress</option>
                            <option value="resolved" @selected(old('status', $status) === 'resolved')>Resolved</option>
                        </select>
                        @error('status')<div class="cs-form-error mb-2">{{ $message }}</div>@enderror

                        <div class="cs-divider-label">Private staff note</div>
                        <div class="cs-private-note"><i class="bi bi-shield-lock-fill me-1"></i>Only administrators can see this note. It will never be sent to the customer.</div>
                        <label class="cs-select-label d-block" for="cs-admin-note">Internal note <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control mb-3" rows="5" name="admin_note" id="cs-admin-note" maxlength="3000" data-optional placeholder="Record follow-up actions or resolution details...">{{ old('admin_note', $contactMessage->admin_note) }}</textarea>
                        @error('admin_note')<div class="cs-form-error mb-2">{{ $message }}</div>@enderror

                        <button class="cs-update-btn" type="submit"><i class="bi bi-check2-circle"></i>Save case updates</button>
                    </form>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
