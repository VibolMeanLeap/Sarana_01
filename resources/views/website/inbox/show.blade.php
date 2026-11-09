@extends('layout.web')
@section('web_title', 'Conversation #'.$contactMessage->id.' · '.($shopSettings?->shop_name ?: 'Coffee Corner'))
@section('content')
<style>
    .thread-page{background:#f6f9f6;min-height:70vh;padding:38px 0 72px}.thread-shell{max-width:900px}.thread-card{background:#fff;border:1px solid #e5eee7;border-radius:20px;box-shadow:0 10px 28px rgba(23,59,38,.04)}.thread-message{max-width:88%;border-radius:17px;padding:15px 17px;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.65}.thread-message.customer{margin-left:auto;background:#edf6ef;color:#244b32;border:1px solid #dcecdf}.thread-message.staff{margin-right:auto;background:#f6f8f6;color:#34483a;border:1px solid #e8eee9}.thread-message.system{margin:0 auto;background:#fff8e8;color:#765a20;border:1px solid #f1e2bb;max-width:100%}.thread-label{font-size:.72rem;font-weight:850;text-transform:uppercase;letter-spacing:.08em;color:#748178}.thread-date{font-size:.75rem;color:#8a958d}.thread-compose textarea{border:1px solid #dce8df;border-radius:13px;min-height:115px;box-shadow:none}.thread-compose textarea:focus{border-color:#39865c;box-shadow:0 0 0 3px #39865c18}
</style>
<section class="thread-page">
    <div class="container thread-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><a href="{{ route('web.inbox') }}" class="btn btn-outline-success rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i>Back to inbox</a><span class="badge rounded-pill {{ $contactMessage->status === 'resolved' ? 'text-bg-success' : ($contactMessage->status === 'in_progress' ? 'text-bg-warning' : 'text-bg-primary') }}">{{ ucfirst(str_replace('_',' ',$contactMessage->status)) }}</span></div>
        <div class="thread-card p-3 p-md-4 mb-3"><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.12em">Enquiry #{{ $contactMessage->id }} · {{ ucfirst(str_replace('_',' ',$contactMessage->topic)) }}</div><h1 class="h3 fw-bold mb-1" style="color:#183326">Conversation with the shop</h1><p class="small text-muted mb-0">Started {{ optional($contactMessage->created_at)->format('d M Y, h:i A') }}@if($contactMessage->order_reference) · Order #{{ $contactMessage->order_reference }}@endif</p></div>
        @if(session('success'))<div class="alert alert-success border-0 rounded-4">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger rounded-4"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="thread-card p-3 p-md-4 mb-3">
            <div class="d-grid gap-3">
                <div><div class="thread-label text-end mb-1">You · Original enquiry</div><div class="thread-message customer">{{ $contactMessage->message }}</div><div class="thread-date text-end mt-1">{{ optional($contactMessage->created_at)->format('d M Y, h:i A') }}</div></div>
                @foreach($contactMessage->replies as $reply)
                    @php($isCustomer = $reply->sender_type === 'customer')
                    @php($isSystem = $reply->sender_type === 'system')
                    <div>
                        <div class="thread-label {{ $isCustomer ? 'text-end' : '' }} mb-1">{{ $isCustomer ? 'You' : ($isSystem ? 'Automatic store response' : ($reply->sender?->name ?: 'Store team')) }}</div>
                        <div class="thread-message {{ $isCustomer ? 'customer' : ($isSystem ? 'system' : 'staff') }}">{{ $reply->body }}</div>
                        <div class="thread-date {{ $isCustomer ? 'text-end' : '' }} mt-1">{{ optional($reply->created_at)->format('d M Y, h:i A') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="thread-card p-3 p-md-4 thread-compose"><h2 class="h5 fw-bold mb-1">Reply to the shop</h2><p class="small text-muted mb-3">Your reply will be added to this private conversation and sent to the team inbox.</p>
            <form method="POST" action="{{ route('web.inbox.reply', $contactMessage->id) }}">@csrf
                <label for="reply-body" class="form-label small fw-bold">Your message</label><textarea id="reply-body" name="body" class="form-control mb-3" maxlength="3000" minlength="2" placeholder="Write your follow-up or answer..." required>{{ old('body') }}</textarea>
                <div class="d-flex justify-content-end"><button type="submit" class="btn btn-success rounded-pill px-4"><i class="bi bi-send me-2"></i>Send reply</button></div>
            </form>
        </div>
    </div>
</section>
@endsection
