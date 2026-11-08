@extends('layout.web')
@section('web_title', 'Checkout')
@section('content')
<section class="py-5">
    <div class="container" style="max-width:980px">
        <div class="mb-4"><span class="text-success fw-bold small text-uppercase">Almost there</span><h1 class="fw-bold mb-1">Checkout</h1><p class="text-muted mb-0">Review your order and pay securely with ABA KHQR. Active promotions are included automatically.</p></div>
        @include('admin.message.message')
        <form method="POST" action="{{ route('web.checkout.place') }}" class="row g-4">@csrf
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Fulfilment details</h2>
                    <div class="mb-3"><label class="form-label fw-semibold" for="order_type_id">Order type</label><select class="form-select" id="order_type_id" name="order_type_id" required>@foreach($orderTypes as $type)<option value="{{ $type->order_type_id }}" data-type="{{ strtolower($type->order_name) }}" @selected(strtolower($type->order_name)==='delivery')>{{ $type->order_name }}</option>@endforeach</select></div>
                    <div class="mb-3" id="deliveryAddressWrap"><label class="form-label fw-semibold" for="delivery_address">Delivery address</label><textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" maxlength="255" data-optional data-required-when="order_type_id" data-required-when-value="delivery" placeholder="House number, street, district, Phnom Penh"></textarea></div>
                    <div class="mb-3"><label class="form-label fw-semibold" for="customer_note">Order note <span class="text-muted fw-normal">(optional)</span></label><textarea class="form-control" id="customer_note" name="customer_note" rows="3" maxlength="500" placeholder="Any special instructions?" data-optional></textarea></div>
                    <div class="alert alert-info small mb-0"><i class="bi bi-qr-code me-2"></i>After confirming, a dynamic ABA KHQR code will be generated. The order remains unpaid until the server verifies the transaction with ABA.</div>
                </div></div>
            </div>
            <div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 fw-bold mb-3">Order summary</h2>
                @foreach($items as $item)
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $item->product->product_name }}</div>
                            <small class="text-muted">{{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}
                                @if($item->offer)<span class="text-success fw-bold">({{ number_format((float)$item->offer->discount, 2) }}% off)</span>@endif
                            </small>
                        </div>
                        <div class="text-end"><strong>${{ number_format($item->line_total, 2) }}</strong>@if($item->line_saving > 0)<small class="d-block text-success">Saved ${{ number_format($item->line_saving, 2) }}</small>@endif</div>
                    </div>
                @endforeach
                <div class="d-flex justify-content-between pt-3"><span>Subtotal</span><strong>${{ number_format($subtotal, 2) }}</strong></div><div class="d-flex justify-content-between pt-2 fs-5"><strong>Total</strong><strong class="text-success">${{ number_format($subtotal, 2) }}</strong></div>
                <button class="btn btn-success w-100 rounded-pill mt-4 py-2" type="submit">Create order & generate KHQR</button><a href="{{ route('web.cart') }}" class="btn btn-link w-100 mt-2 text-muted">Back to cart</a>
            </div></div></div>
        </form>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('order_type_id');
    const wrap = document.getElementById('deliveryAddressWrap');
    const address = document.getElementById('delivery_address');
    const sync = () => { const delivery = type.selectedOptions[0]?.dataset.type === 'delivery'; wrap.hidden = !delivery; address.required = delivery; };
    type.addEventListener('change', sync); sync();
});
</script>
@endsection
