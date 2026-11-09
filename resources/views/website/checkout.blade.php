@extends('layout.web')
@section('web_title', 'Secure checkout')
@section('content')
<style>
    .checkout-page{background:linear-gradient(180deg,#f6faf6,#fff);padding:42px 0 70px;min-height:70vh}.checkout-title{font-weight:850;letter-spacing:-.05em;color:#183326}.checkout-card{background:#fff;border:1px solid #e6eee7;border-radius:23px;box-shadow:0 16px 42px rgba(24,56,38,.05)}.checkout-card h2{font-weight:800;color:#203a2b}.checkout-control{border-radius:12px;padding:12px 14px;border-color:#dfe8e1}.checkout-control:focus{border-color:#5c9c72;box-shadow:0 0 0 4px rgba(34,128,76,.1)}.checkout-total{font-size:1.9rem;font-weight:900;color:#167549;letter-spacing:-.04em}.checkout-pay-btn{border:0;border-radius:99px;background:#1d7548;padding:14px 20px;font-weight:850}.checkout-pay-btn:disabled{opacity:.7}
</style>
<section class="checkout-page">
    <div class="container" style="max-width:1080px">
        <div class="mb-4"><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.14em">One last step</div><h1 class="display-6 checkout-title mb-1">Secure checkout</h1><p class="text-muted mb-0">Choose pickup or delivery, review your total, then pay with ABA KHQR.</p></div>
        @include('admin.message.message')
        @if($errors->any())<div class="alert alert-danger rounded-4"><strong>Please review the following:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('web.checkout.place') }}" class="row g-4" id="websiteCheckoutForm">@csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
            <div class="col-lg-7">
                <div class="checkout-card p-3 p-md-4 mb-3">
                    <div class="d-flex align-items-center gap-3 mb-3"><span class="rounded-circle d-grid place-items-center bg-success-subtle text-success" style="width:42px;height:42px"><i class="bi bi-person-check fs-5"></i></span><div><h2 class="h5 mb-0">Your customer details</h2><small class="text-muted">Signed in as {{ auth()->user()->name }} · {{ auth()->user()->phone }}</small></div></div>
                    <div class="d-flex align-items-center gap-3 mb-3"><span class="rounded-circle d-grid place-items-center bg-success-subtle text-success" style="width:42px;height:42px"><i class="bi bi-truck fs-5"></i></span><div><h2 class="h5 mb-0">How would you like to receive it?</h2><small class="text-muted">Select the option that suits you.</small></div></div>
                    <div class="row g-2 mb-3">
                        @foreach($orderTypes as $type)
                            @php($isPickup = strtolower($type->order_name) === 'takeaway')
                            <div class="col-sm-6">
                                <label class="border rounded-4 p-3 h-100 d-flex gap-3 align-items-start w-100" style="cursor:pointer">
                                    <input class="form-check-input mt-1" type="radio" name="order_type_id" value="{{ $type->order_type_id }}" data-type="{{ strtolower($type->order_name) }}" @checked(old('order_type_id', $orderTypes->first()->order_type_id) == $type->order_type_id) required>
                                    <span><strong class="d-block">{{ $isPickup ? 'Pickup at the shop' : 'Home delivery' }}</strong><small class="text-muted">{{ $isPickup ? 'Collect your order when it is ready.' : 'We will prepare your order for delivery.' }}</small></span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-3" id="deliveryAddressWrap" hidden>
                        <label class="form-label fw-semibold" for="delivery_address">Delivery address <span class="text-danger">*</span></label>
                        <textarea class="form-control checkout-control @error('delivery_address') is-invalid @enderror" id="delivery_address" name="delivery_address" rows="3" maxlength="255" placeholder="House number, street, district, city">{{ old('delivery_address') }}</textarea>
                        @error('delivery_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="customer_note">Order note <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea class="form-control checkout-control" id="customer_note" name="customer_note" rows="2" maxlength="500" data-optional placeholder="Door instructions or special requests">{{ old('customer_note') }}</textarea>
                    </div>
                </div>
                <div class="checkout-card p-3 p-md-4">
                    <div class="d-flex align-items-center gap-3"><span class="rounded-circle d-grid place-items-center bg-success-subtle text-success" style="width:42px;height:42px"><i class="bi bi-qr-code fs-5"></i></span><div><h2 class="h5 mb-0">Secure payment</h2><small class="text-muted">Dynamic ABA KHQR · verified server-to-server</small></div></div>
                    <div class="alert alert-success border-0 rounded-3 small mt-3 mb-0"><i class="bi bi-shield-check me-2"></i>Your order is confirmed only after ABA verifies the payment. You can track preparation and delivery from My Account.</div>
                </div>
            </div>
            <div class="col-lg-5">
                <aside class="checkout-card p-3 p-md-4 position-sticky" style="top:100px">
                    <h2 class="h5 mb-3">Your order</h2>
                    @foreach($items as $item)
                        <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                            <div><div class="fw-bold">{{ $item->product->product_name }}</div><small class="text-muted">{{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }} @if($item->offer)<span class="text-success">· {{ number_format((float)$item->offer->discount, 0) }}% off</span>@endif</small></div>
                            <strong>${{ number_format($item->line_total, 2) }}</strong>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between py-2 mt-2"><span class="text-muted">Items subtotal</span><strong>${{ number_format($subtotal, 2) }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Delivery fee</span><strong id="deliveryFeeDisplay">$0.00</strong></div>
                    <div class="border-top mt-2 pt-3 d-flex justify-content-between align-items-end"><span class="fw-bold">Total to pay</span><span class="checkout-total" id="checkoutGrandTotal" data-subtotal="{{ number_format($subtotal, 2, '.', '') }}" data-delivery-fee="{{ number_format($deliveryFee, 2, '.', '') }}">${{ number_format($subtotal, 2) }}</span></div>
                    <small class="text-muted d-block mt-2">Delivery fee applies only to home delivery. Current fee: ${{ number_format($deliveryFee, 2) }}.</small>
                    <button class="btn btn-success checkout-pay-btn w-100 mt-4" id="checkoutSubmit" type="submit"><i class="bi bi-lock-fill me-2"></i>Place order & generate KHQR</button>
                    <a href="{{ route('web.cart') }}" class="btn btn-link text-muted text-decoration-none w-100 mt-2">Back to cart</a>
                </aside>
            </div>
        </form>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('websiteCheckoutForm');
    const radios = [...document.querySelectorAll('input[name="order_type_id"]')];
    const addressWrap = document.getElementById('deliveryAddressWrap');
    const address = document.getElementById('delivery_address');
    const total = document.getElementById('checkoutGrandTotal');
    const feeDisplay = document.getElementById('deliveryFeeDisplay');
    const submit = document.getElementById('checkoutSubmit');
    const subtotal = Number(total.dataset.subtotal || 0);
    const deliveryFee = Number(total.dataset.deliveryFee || 0);
    const currency = amount => '$' + amount.toFixed(2);
    const sync = () => {
        const selected = radios.find(radio => radio.checked);
        const isDelivery = selected?.dataset.type === 'delivery';
        addressWrap.hidden = !isDelivery;
        address.required = isDelivery;
        const fee = isDelivery ? deliveryFee : 0;
        feeDisplay.textContent = currency(fee);
        total.textContent = currency(subtotal + fee);
    };
    radios.forEach(radio => radio.addEventListener('change', sync));
    sync();
    form.addEventListener('submit', () => {
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Creating secure payment…';
    });
});
</script>
@endsection
