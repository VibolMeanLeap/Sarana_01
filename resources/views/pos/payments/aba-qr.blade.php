<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ABA payment · Order #{{ $payment->order_id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at 12% 5%, rgba(30, 123, 79, .10), transparent 30%),
                linear-gradient(135deg, #f1f6f2 0%, #f8faf8 55%, #eef3ef 100%);
            color: #203128;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }
        .payment-shell { width: min(100% - 40px, 1220px); margin: 22px auto 18px; }
        .topbar { min-height: 36px; margin-bottom: 14px; }
        .topbar .small { font-size: .85rem; }
        .payment-card {
            overflow: hidden;
            background: #fff;
            border: 1px solid #e1eae3;
            border-radius: 24px;
            box-shadow: 0 22px 65px rgba(29, 57, 39, .10);
        }
        .payment-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr);
            align-items: stretch;
            gap: 0;
            min-height: 650px;
        }
        .qr-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 38px 34px;
            text-align: center;
            background: linear-gradient(155deg, #f8fbf8 0%, #f0f6f1 100%);
            border-right: 1px solid #e8eee9;
        }
        .brand-mark {
            width: 46px; height: 46px; margin-bottom: 12px;
            border-radius: 15px; background: #176b4b; color: #fff;
            display: grid; place-items: center; font-size: 21px;
            box-shadow: 0 7px 18px rgba(23, 107, 75, .18);
        }
        .qr-column h1 { font-size: clamp(1.45rem, 2vw, 1.9rem); letter-spacing: -.035em; }
        .qr-reference { font-size: .85rem; color: #738178; overflow-wrap: anywhere; }
        .qr-frame {
            width: clamp(380px, 35vw, 490px);
            aspect-ratio: 1 / 1;
            margin: 22px auto 14px;
            padding: 19px;
            display: grid;
            place-items: center;
            background: #fff;
            border: 1px solid #dce6de;
            border-radius: 22px;
            box-shadow: 0 12px 36px rgba(25, 56, 37, .09);
        }
        .qr-image {
            display: block;
            width: 100%;
            height: 100%;
            max-width: none;
            max-height: none;
            object-fit: contain;
            image-rendering: pixelated;
        }
        .qr-payload { width: min(100%, 490px); font-size: .78rem; }
        .qr-scan-hint { color: #56675d; font-size: .9rem; font-weight: 600; }
        .payment-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px clamp(30px, 4vw, 58px);
        }
        .detail-label {
            color: #758279;
            font-size: .76rem;
            font-weight: 750;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .amount {
            color: #176b4b;
            font-size: clamp(2.65rem, 4.2vw, 3.8rem);
            line-height: 1.05;
            font-weight: 850;
            letter-spacing: -.055em;
            overflow-wrap: anywhere;
        }
        .muted { color: #758279; }
        .status-panel {
            border-radius: 14px;
            font-size: .95rem;
            line-height: 1.45;
            margin-bottom: 8px;
            padding: 15px 16px !important;
        }
        .status-meta { min-height: 20px; font-size: .77rem; }
        .instruction { font-size: .92rem; line-height: 1.55; }
        .payment-note {
            border: 1px solid #e7eee8;
            border-radius: 13px;
            background: #f8faf8;
            color: #65736a;
            padding: 13px 15px;
            font-size: .83rem;
            line-height: 1.5;
        }
        .btn { --bs-btn-font-size: .92rem; }
        .btn-open-aba { padding: 11px 18px; font-weight: 650; }
        .divider { height: 1px; background: #e9efea; margin: 23px 0; }

        /* Tablet and phone: preserve a single-column flow with a smaller, readable QR. */
        @media (max-width: 991.98px) {
            .payment-shell { width: min(100% - 28px, 650px); margin-top: 14px; }
            .payment-layout { grid-template-columns: minmax(0, 1fr); min-height: 0; }
            .qr-column { padding: 26px 22px 24px; border-right: 0; border-bottom: 1px solid #e8eee9; }
            .qr-frame { width: min(76vw, 350px); margin: 16px auto 12px; padding: 14px; }
            .payment-details { padding: 26px 26px 30px; }
            .amount { font-size: clamp(2.2rem, 8vw, 3rem); }
        }
        @media (max-width: 575.98px) {
            .payment-shell { width: min(100% - 16px, 480px); margin-top: 8px; }
            .topbar { margin-bottom: 8px; }
            .topbar .small { font-size: .72rem; }
            .payment-card { border-radius: 17px; }
            .qr-column { padding: 20px 14px 18px; }
            .qr-frame { width: min(82vw, 290px); padding: 11px; border-radius: 16px; }
            .payment-details { padding: 21px 17px 23px; }
            .status-panel { font-size: .86rem; }
        }
        @media (min-width: 992px) and (max-height: 800px) {
            .payment-shell { margin-top: 12px; }
            .payment-layout { min-height: 590px; }
            .qr-column { padding-top: 24px; padding-bottom: 24px; }
            .qr-frame { width: clamp(350px, 32vw, 430px); margin-top: 14px; margin-bottom: 10px; }
            .payment-details { padding-top: 30px; padding-bottom: 30px; }
            .divider { margin: 16px 0; }
        }
    </style>
</head>
<body>
@php
    $roleName = strtolower((string) auth()->user()->role?->role_name);
    $staffOrdersUrl = $roleName === 'cashier' ? route('cashier.orders') : route('admin.orders.index');
    $qrImageSrc = $payment->qr_image
        ? (str_starts_with($payment->qr_image, 'data:image/') || str_starts_with($payment->qr_image, 'https://') || str_starts_with($payment->qr_image, 'http://') ? $payment->qr_image : 'data:image/png;base64,'.$payment->qr_image)
        : null;
    $currency = strtoupper((string) $payment->currency);
    $currencySymbol = $currency === 'KHR' ? '៛' : '$';
@endphp
<div class="payment-shell">
    <div class="topbar d-flex align-items-center justify-content-between">
        <a href="{{ $staffOrdersUrl }}" class="text-decoration-none fw-semibold text-success small">
            <i class="bi bi-arrow-left me-1"></i>Back to orders
        </a>
        <span class="small muted">Coffee Corner · Staff POS payment</span>
    </div>

    <main class="payment-card">
        <div class="payment-layout">
            <section class="qr-column" aria-labelledby="payment-title">
                <div class="brand-mark"><i class="bi bi-cup-hot-fill"></i></div>
                <span class="badge rounded-pill text-bg-success px-3 py-2">ABA PayWay Sandbox</span>
                <h1 id="payment-title" class="fw-bold mt-3 mb-1">Scan to complete payment</h1>
                <p class="qr-reference mb-0">Order #{{ $payment->order_id }} <span class="mx-1">·</span> {{ $payment->tran_id }}</p>

                @if($qrImageSrc)
                    <div class="qr-frame">
                        <img src="{{ $qrImageSrc }}" alt="ABA PayWay KHQR payment code" class="qr-image">
                    </div>
                @elseif($payment->qr_string)
                    <div class="alert alert-warning text-start py-2 mb-2 qr-payload">
                        ABA returned a QR payload but no QR image. Use the payload with a compatible KHQR viewer.
                    </div>
                    <textarea class="form-control qr-payload mb-2" rows="4" readonly>{{ $payment->qr_string }}</textarea>
                @else
                    <div class="alert alert-danger py-2 qr-payload">No QR image or payload was saved. Check <code>storage/logs/laravel.log</code>.</div>
                @endif

                <div class="qr-scan-hint"><i class="bi bi-camera me-1"></i>Open ABA Mobile and scan this code</div>
            </section>

            <section class="payment-details" aria-label="Payment details and status">
                <div class="detail-label mb-2">Amount to pay</div>
                <div class="amount mb-2">{{ $currencySymbol }}{{ number_format((float) $payment->amount, $currency === 'KHR' ? 0 : 2) }}</div>
                <p class="muted instruction mb-0">Scan the large QR code on the left using ABA Mobile or another KHQR-supported banking app.</p>

                @if($payment->abapay_deeplink)
                    <a href="{{ $payment->abapay_deeplink }}" class="btn btn-success btn-open-aba rounded-pill mt-4 align-self-start">
                        <i class="bi bi-phone me-2"></i>Open ABA Mobile
                    </a>
                @endif

                <div class="divider"></div>

                <div class="detail-label mb-2">Payment status</div>
                <div id="payment-status" class="alert alert-info status-panel" role="status" aria-live="polite">
                    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    Waiting for ABA payment confirmation…
                </div>
                <div id="payment-checked-at" class="status-meta muted mb-3">Connecting to payment verification…</div>

                <div class="payment-note mb-3">
                    <i class="bi bi-shield-lock me-1 text-success"></i>
                    Keep this page open after scanning. Coffee Corner verifies the transaction directly with ABA before marking the sale as paid.
                </div>

                @if($payment->expires_at)
                    <div class="small muted mb-3"><i class="bi bi-clock me-1"></i>QR expiry: {{ $payment->expires_at->format('d M Y, h:i A') }}</div>
                @endif

                <a href="{{ $staffOrdersUrl }}" class="btn btn-outline-secondary rounded-pill align-self-start px-4">
                    <i class="bi bi-arrow-left me-1"></i>Return to order queue
                </a>
            </section>
        </div>
    </main>
    <p class="text-center small muted mt-3 mb-0">Coffee Corner · Secure payment verification</p>
</div>

<script>
(function () {
    const target = document.getElementById('payment-status');
    const checkedAt = document.getElementById('payment-checked-at');
    const statusUrl = @json(route('pos.payments.status', $payment->id));
    let stopped = false;
    let timer = null;

    function setStatus(kind, message) {
        target.className = 'alert status-panel';
        target.classList.add(kind === 'success' ? 'alert-success' : kind === 'warning' ? 'alert-warning' : 'alert-info');
        target.textContent = message;
    }

    async function checkStatus() {
        if (stopped) return;

        try {
            const response = await fetch(statusUrl + '?_=' + Date.now(), {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Status endpoint returned HTTP ' + response.status);
            }

            const data = await response.json();
            checkedAt.textContent = 'Last checked: ' + new Date().toLocaleTimeString();

            if (data.paid || data.status === 'paid') {
                stopped = true;
                setStatus('success', 'Payment verified by ABA! The POS sale is now marked as paid. Returning to orders…');
                checkedAt.textContent = 'Payment confirmed · ' + new Date().toLocaleTimeString();
                window.setTimeout(function () {
                    window.location.assign(data.success_url || data.order_url || @json($staffOrdersUrl));
                }, 1600);
                return;
            }

            if (['failed', 'declined', 'expired', 'cancelled', 'review_required'].includes(data.status)) {
                stopped = true;
                setStatus('warning', data.message || ('Payment status: ' + data.status.replaceAll('_', ' ') + '. Check the order before retrying.'));
                return;
            }

            setStatus('info', data.message || 'Payment is still pending. Waiting for ABA confirmation…');
        } catch (error) {
            checkedAt.textContent = 'Could not reach the status checker · retrying automatically';
            setStatus('warning', 'We could not refresh the payment status. Your order is still unpaid; checking again shortly…');
        }

        if (!stopped) {
            timer = window.setTimeout(checkStatus, 5000);
        }
    }

    checkStatus();
    window.addEventListener('beforeunload', function () {
        if (timer) window.clearTimeout(timer);
    });
})();
</script>
</body>
</html>
