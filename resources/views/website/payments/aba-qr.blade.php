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
        <a href="{{ route('web.orders.show', $payment->order_id, false) }}" class="btn btn-outline-secondary mt-4">Back to order details</a>
    </div>
</div></section>
<script>
(function () {
    const target = document.getElementById('payment-status');
    const statusUrl = @json(route('web.payments.status', $payment->id, false));
    const loginUrl = @json(route('webLogin', [], false));
    let stopped = false;
    let timer = null;
    let consecutiveFailures = 0;

    function show(kind, message) {
        target.className = 'alert mb-3 ' + (kind === 'success' ? 'alert-success' : kind === 'warning' ? 'alert-warning' : 'alert-info');
        target.textContent = message;
    }

    async function check() {
        if (stopped) return;

        try {
            // Relative URL is intentional: it keeps the polling request on the exact
            // hostname that opened this QR page, even when APP_URL differs locally.
            const separator = statusUrl.includes('?') ? '&' : '?';
            const response = await fetch(statusUrl + separator + '_=' + Date.now(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'ngrok-skip-browser-warning': 'true'
                },
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (response.status === 401) {
                throw new Error('Your customer login session has expired. Sign in again, then return to this payment page.');
            }
            if (response.status === 403 || response.status === 404) {
                stopped = true;
                show('warning', 'This payment cannot be accessed by the current customer account. Please return to your account or contact the shop.');
                return;
            }
            if (!response.ok) {
                throw new Error('The status endpoint returned HTTP ' + response.status + '.');
            }
            if (response.redirected) {
                throw new Error('The status request was redirected to a login page. Please sign in again.');
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.toLowerCase().includes('application/json')) {
                throw new Error('The status endpoint returned HTML instead of JSON. Check the login session and application hostname.');
            }

            const data = await response.json();
            consecutiveFailures = 0;

            if (data.paid || data.status === 'paid') {
                stopped = true;
                show('success', 'Payment confirmed by ABA! Redirecting to your order…');
                window.setTimeout(function () {
                    window.location.assign(data.success_url || data.order_url);
                }, 1100);
                return;
            }

            if (['failed', 'declined', 'expired', 'cancelled', 'review_required'].includes(data.status)) {
                stopped = true;
                show('warning', data.message || ('Payment status: ' + String(data.status).replaceAll('_', ' ') + '. Return to your order page for next steps.'));
                return;
            }

            if (data.verification_error) {
                show('warning', 'ABA has not confirmed this payment yet. The server could not complete its latest verification attempt; it will retry automatically.');
            } else {
                show('info', data.message || 'Payment is still pending. Waiting for ABA confirmation…');
            }
        } catch (error) {
            consecutiveFailures += 1;
            const message = error && error.message ? error.message : 'Network error while checking payment status.';
            show('warning', message + ' Retrying automatically.');
        }

        if (!stopped) {
            const delay = Math.min(8000 + (consecutiveFailures * 2000), 20000);
            timer = window.setTimeout(check, delay);
        }
    }

    check();
    window.addEventListener('beforeunload', function () {
        if (timer) window.clearTimeout(timer);
    });
})();
</script>
@endsection
