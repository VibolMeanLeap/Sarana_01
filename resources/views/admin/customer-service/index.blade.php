@extends('layout.admin')
@section('page_title', 'Customer enquiries')
@section('topbar_title', 'Customer service inbox')
@section('content')
<style>
    .cs-hero{background:radial-gradient(circle at 88% 10%,rgba(215,187,128,.19),transparent 23%),linear-gradient(120deg,#123324,#1d5139);border-radius:20px;color:#fff;padding:25px 28px;margin-bottom:20px}
    .cs-hero h1{font-size:1.55rem;font-weight:850;letter-spacing:-.04em;margin:0}.cs-hero p{font-size:.86rem;color:rgba(255,255,255,.7);margin:7px 0 0}
    .cs-stat{background:#fff;border:1px solid #e8eee9;border-radius:15px;padding:17px 18px;height:100%}.cs-stat span{color:#7c8880;font-size:.75rem;font-weight:700}.cs-stat strong{display:block;color:#1d3427;font-size:1.65rem;letter-spacing:-.04em;margin-top:5px}.cs-panel{background:#fff;border:1px solid #e8eee9;border-radius:17px;overflow:hidden}.cs-table th{font-size:.7rem;color:#849088;text-transform:uppercase;letter-spacing:.08em;font-weight:850;border-bottom:1px solid #edf1ed;padding:14px 16px}.cs-table td{padding:15px 16px;vertical-align:middle;border-color:#f0f3f0}.cs-topic{font-size:.7rem;font-weight:800;padding:6px 9px;border-radius:99px;background:#edf5ef;color:#237149}.cs-message-link{color:#20382a;text-decoration:none;font-weight:800}.cs-message-link:hover{color:#187448}.cs-muted{font-size:.77rem;color:#87928b}.cs-filter .form-control,.cs-filter .form-select{border-radius:10px;border-color:#e1e8e2;min-height:41px;box-shadow:none}.cs-filter .form-control:focus,.cs-filter .form-select:focus{border-color:#438d65;box-shadow:0 0 0 3px #438d6518}
</style>
<div class="cs-hero"><div class="d-flex flex-wrap align-items-center justify-content-between gap-3"><div><div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.14em;color:#e8c990"><i class="bi bi-chat-heart me-1"></i>Customer experience</div><h1>Customer enquiries</h1><p>Messages from your public storefront contact form. Review, follow up and keep each enquiry organised.</p></div><span class="badge rounded-pill px-3 py-2" style="background:#ffffff19;color:#fff"><i class="bi bi-shield-check me-1"></i>Administrator only</span></div></div>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="cs-stat"><span><i class="bi bi-inbox me-1"></i>Total enquiries</span><strong>{{ number_format($counts['total']) }}</strong></div></div>
    <div class="col-6 col-xl-3"><div class="cs-stat"><span><i class="bi bi-bell me-1"></i>New</span><strong>{{ number_format($counts['new']) }}</strong></div></div>
    <div class="col-6 col-xl-3"><div class="cs-stat"><span><i class="bi bi-hourglass-split me-1"></i>In progress</span><strong>{{ number_format($counts['in_progress']) }}</strong></div></div>
    <div class="col-6 col-xl-3"><div class="cs-stat"><span><i class="bi bi-check-circle me-1"></i>Resolved</span><strong>{{ number_format($counts['resolved']) }}</strong></div></div>
</div>
<div class="cs-panel">
    <div class="p-3 p-lg-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div><h2 class="h5 fw-bold mb-1">Inbox</h2><p class="cs-muted mb-0">Newest messages are shown first.</p></div>
        <form method="GET" class="cs-filter d-flex flex-wrap gap-2">
            <input type="search" class="form-control" name="q" value="{{ request('q') }}" placeholder="Search name, email, order..." style="min-width:220px">
            <select class="form-select" name="status" style="min-width:145px"><option value="">All statuses</option><option value="new" @selected(request('status')==='new')>New</option><option value="in_progress" @selected(request('status')==='in_progress')>In progress</option><option value="resolved" @selected(request('status')==='resolved')>Resolved</option></select>
            <button class="btn btn-cc" type="submit"><i class="bi bi-search me-1"></i>Filter</button>
            @if(request()->hasAny(['q','status']))<a href="{{ route('admin.customer-service.index') }}" class="btn btn-cc-light">Clear</a>@endif
        </form>
    </div>
    @if($messages->isEmpty())
        <div class="text-center py-5 px-3"><div class="rounded-circle mx-auto mb-3 d-grid place-items-center" style="width:58px;height:58px;background:#edf5ef;color:#237149;font-size:1.4rem"><i class="bi bi-chat-square-text"></i></div><h3 class="h6 fw-bold">No enquiries found</h3><p class="small text-muted mb-0">New customer messages submitted from the storefront will appear here.</p></div>
    @else
        <div class="table-responsive"><table class="table cs-table mb-0"><thead><tr><th>Customer</th><th>Topic / order</th><th>Message</th><th>Status</th><th>Received</th><th class="text-end">View</th></tr></thead><tbody>
            @foreach($messages as $item)
                <tr>
                    <td><a class="cs-message-link d-block" href="{{ route('admin.customer-service.show', $item) }}">{{ $item->name }}</a><span class="cs-muted">{{ $item->email }}</span>@if(!$item->read_at)<span class="badge rounded-pill text-bg-success ms-1">Unread</span>@endif</td>
                    <td><span class="cs-topic">{{ ucfirst(str_replace('_',' ',$item->topic)) }}</span>@if($item->order_reference)<span class="cs-muted d-block mt-1">Order #{{ $item->order_reference }}</span>@endif</td>
                    <td style="min-width:220px;max-width:360px"><span class="small text-muted">{{ \Illuminate\Support\Str::limit($item->message, 105) }}</span></td>
                    <td><span class="badge rounded-pill {{ $item->status==='resolved' ? 'text-bg-success' : ($item->status==='in_progress' ? 'text-bg-warning' : 'text-bg-primary') }}">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></td>
                    <td class="small text-muted text-nowrap">{{ optional($item->created_at)->format('d M Y, h:i A') }}</td>
                    <td class="text-end"><a href="{{ route('admin.customer-service.show', $item) }}" class="btn btn-sm btn-cc-light"><i class="bi bi-arrow-up-right"></i></a></td>
                </tr>
            @endforeach
        </tbody></table></div>
        <div class="p-3">{{ $messages->links() }}</div>
    @endif
</div>
@endsection
