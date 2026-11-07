@extends('layout.web')
@section('content')
<section class="py-5"><div class="container" style="max-width:760px">
    <div class="text-center mb-4"><span class="badge text-bg-success rounded-pill px-3 py-2">ABA PayWay Sandbox</span><h1 class="h2 fw-bold mt-3">Scan to complete payment</h1><p class="text-muted">Order #{{ $payment->order_id }} · Transaction {{ $payment->tran_id }}</p></div>
    <div class="bg-white border rounded-4 p-4 p-md-5 text-center shadow-sm">
        @if($payment->qr_image)@php $qrImageSrc = str_starts_with($payment->qr_image, 'data:image/') || str_starts_with($payment->qr_image, 'https://') || str_starts_with($payment->qr_image, 'http://') ? $payment->qr_image : 'data:image/png;base64,'.$payment->qr_image; @endphp<img src="{{ $qrImageSrc }}" alt="ABA PayWay payment QR code" class="img-fluid mb-3" style="max-width:310px;width:100%">@else<div class="alert alert-warning">The payment QR image was not returned. Contact the shop before paying.</div>@endif
        <div class="h3 fw-bold text-success mb-1">${{ number_format((float)$payment->amount,2) }} USD</div><div class="text-muted small mb-4">Please scan with ABA Mobile or a KHQR-supported banking app.</div>
        @if($payment->abapay_deeplink)<a href="{{ $payment->abapay_deeplink }}" class="btn btn-success rounded-pill px-4 mb-3"><i class="bi bi-phone me-2"></i>Open ABA Mobile</a>@endif
        <div id="payment-status" class="alert alert-info mb-3"><span class="spinner-border spinner-border-sm me-2" role="status"></span>Waiting for payment confirmation…</div>
        <div class="small text-muted">This screen checks the payment status with ABA PayWay. Do not close it until your payment is confirmed.</div>
        @if($payment->expires_at)<div class="small text-muted mt-2">QR expires at {{ $payment->expires_at->format('d M Y, h:i A') }}.</div>@endif
        <a href="{{ route('web.orders.show',$payment->order_id) }}" class="btn btn-outline-secondary mt-4">Back to order details</a>
    </div>
</div></section>
<script>
(function(){const target=document.getElementById('payment-status');const url=@json(route('web.payments.status',$payment->id));let stopped=false;async function check(){if(stopped)return;try{const response=await fetch(url,{headers:{'Accept':'application/json'}});if(!response.ok)throw new Error('status');const data=await response.json();if(data.paid){stopped=true;target.className='alert alert-success';target.innerHTML='<i class="bi bi-check-circle-fill me-2"></i>Payment confirmed! Redirecting to your order…';setTimeout(()=>window.location.href=(data.success_url || data.order_url),1000);return;}if(['failed','expired','cancelled','review_required'].includes(data.status)){stopped=true;target.className='alert alert-warning';target.textContent='Payment status: '+data.status.replace('_',' ')+'. Return to your order page for next steps.';return;}target.className='alert alert-info';target.textContent='Payment status: '+data.status+'. Waiting for confirmation…';}catch(e){target.textContent='Unable to refresh payment status. This page will try again.';}setTimeout(check,8000);}check();})();
</script>
@endsection
