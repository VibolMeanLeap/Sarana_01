@extends('layout.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('offer.list') }}" class="btn btn-light rounded-circle shadow-sm" aria-label="Back to offers"><i class="bi bi-arrow-left"></i></a>
        <div><div class="small text-success fw-bold text-uppercase" style="letter-spacing:.12em">Promotion studio</div><h1 class="h3 fw-bold mb-0">Create an offer</h1></div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-3"><strong>Please check these details:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4 align-items-start">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-lg-5">
                <div class="d-flex align-items-center gap-2 mb-4"><span class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width:42px;height:42px"><i class="bi bi-stars fs-5"></i></span><div><h2 class="h5 fw-bold mb-0">Offer details</h2><p class="small text-muted mb-0">Choose a product and discount. The sale price is calculated automatically.</p></div></div>

                <form action="{{ route('offer.store') }}" method="POST" id="offerForm">
                    @csrf
                    <div class="mb-4">
                        <label for="product_id" class="form-label fw-semibold">Featured product</label>
                        <select name="product_id" id="product_id" class="form-select form-select-lg" required>
                            <option value="">Choose a product...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->product_id }}" data-price="{{ (float)$product->price }}" data-name="{{ $product->product_name }}" @selected(old('product_id') == $product->product_id)>{{ $product->product_name }} — ${{ number_format((float)$product->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="discount" class="form-label fw-semibold">Discount percentage</label>
                            <div class="input-group input-group-lg"><input type="number" id="discount" name="discount" class="form-control" min="0.01" max="99.99" step="0.01" value="{{ old('discount', 15) }}" placeholder="15" required><span class="input-group-text">%</span></div>
                            <div class="form-text">Use a value from 0.01% to 99.99%.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="new_price_preview" class="form-label fw-semibold">Your offer price</label>
                            <div class="input-group input-group-lg"><span class="input-group-text">$</span><input type="text" id="new_price_preview" class="form-control fw-bold text-success bg-light" value="—" readonly aria-readonly="true"></div>
                            <div class="form-text">Calculated securely from the product's regular price.</div>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6"><label for="start_date" class="form-label fw-semibold">Start date</label><input type="date" name="start_date" id="start_date" class="form-control form-control-lg" value="{{ old('start_date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-6"><label for="end_date" class="form-label fw-semibold">End date</label><input type="date" name="end_date" id="end_date" class="form-control form-control-lg" value="{{ old('end_date', now()->addDays(7)->toDateString()) }}" min="{{ old('start_date', now()->toDateString()) }}" required></div>
                    </div>

                    <div class="alert alert-success bg-success-subtle border-0 mt-4 small"><i class="bi bi-lightning-charge-fill me-2"></i><strong>Automatic website banner:</strong> when the start date arrives, this offer appears in the public shop with the product image, discount and sale price. It disappears automatically after the end date.</div>
                    <div class="d-flex flex-wrap gap-2 mt-4"><button type="submit" class="btn btn-success btn-lg rounded-pill px-4"><i class="bi bi-megaphone me-2"></i>Publish offer schedule</button><a href="{{ route('offer.list') }}" class="btn btn-light btn-lg rounded-pill px-4">Cancel</a></div>
                </form>
            </div></div>
        </div>

        <div class="col-xl-5">
            <div class="offer-preview-shell rounded-4 p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center text-white-50 small mb-3"><span class="fw-bold text-uppercase" style="letter-spacing:.14em">Live preview</span><span><i class="bi bi-eye me-1"></i>Website shop</span></div>
                <div class="offer-preview-card rounded-4 p-4 text-white position-relative overflow-hidden">
                    <div class="position-relative" style="z-index:2;max-width:78%">
                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-3"><i class="bi bi-stars me-1"></i>LIMITED TIME</span>
                        <div class="small text-white-50 fw-semibold text-uppercase mb-2">A little something special</div>
                        <h3 class="fw-bold mb-2" id="previewProduct">Your product</h3>
                        <div class="display-6 fw-bold mb-1" id="previewPrice">$0.00</div>
                        <div class="small text-white-50"><span id="previewDiscount">15%</span> savings on your favourite pick</div>
                    </div>
                    <div class="offer-preview-orbit" aria-hidden="true"></div>
                    <div class="offer-preview-stamp"><i class="bi bi-tag-fill"></i><span id="previewStamp">15% OFF</span></div>
                </div>
                <p class="small text-white-50 mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>The public banner uses the selected product's actual image and live dates. This preview updates as you change the form.</p>
            </div>
        </div>
    </div>
</div>

<style>
.offer-preview-shell{background:linear-gradient(140deg,#10251d,#1d4534 58%,#96703a);box-shadow:0 20px 60px rgba(16,37,29,.18)}
.offer-preview-card{min-height:300px;background:radial-gradient(circle at 90% 20%,rgba(236,190,105,.3),transparent 35%),linear-gradient(120deg,#18372a,#0d2119 72%);border:1px solid rgba(255,255,255,.13);box-shadow:inset 0 1px rgba(255,255,255,.08)}
.offer-preview-orbit{position:absolute;width:230px;height:230px;border:1px solid rgba(245,209,143,.22);border-radius:50%;right:-48px;bottom:-92px;box-shadow:0 0 0 24px rgba(245,209,143,.04),0 0 0 50px rgba(245,209,143,.035)}
.offer-preview-stamp{position:absolute;right:20px;top:24px;width:94px;height:94px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;border-radius:50%;background:#edc47b;color:#382714;font-weight:900;transform:rotate(10deg);box-shadow:0 8px 28px rgba(0,0,0,.2);font-size:12px}
.offer-preview-stamp i{font-size:20px}
@media(max-width:575px){.offer-preview-stamp{width:74px;height:74px;right:14px;top:18px;font-size:10px}.offer-preview-card{min-height:260px}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const product = document.getElementById('product_id');
    const discount = document.getElementById('discount');
    const pricePreview = document.getElementById('new_price_preview');
    const previewProduct = document.getElementById('previewProduct');
    const previewPrice = document.getElementById('previewPrice');
    const previewDiscount = document.getElementById('previewDiscount');
    const previewStamp = document.getElementById('previewStamp');
    const start = document.getElementById('start_date');
    const end = document.getElementById('end_date');

    function updatePreview() {
        const option = product.options[product.selectedIndex];
        const original = Number(option?.dataset.price || 0);
        const rate = Math.min(99.99, Math.max(0, Number(discount.value || 0)));
        const sale = Math.max(0, Math.round((original * (1 - rate / 100) + Number.EPSILON) * 100) / 100);
        const name = option?.dataset.name || 'Your product';
        const money = '$' + sale.toFixed(2);
        pricePreview.value = original > 0 ? money : '—';
        previewProduct.textContent = name;
        previewPrice.textContent = money;
        previewDiscount.textContent = rate.toFixed(2).replace(/\.00$/, '') + '%';
        previewStamp.textContent = rate.toFixed(2).replace(/\.00$/, '') + '% OFF';
    }
    function syncDateLimits() {
        if (start.value) end.min = start.value;
        if (end.value && start.value && end.value < start.value) end.value = start.value;
    }
    product.addEventListener('change', updatePreview);
    discount.addEventListener('input', updatePreview);
    start.addEventListener('change', syncDateLimits);
    updatePreview();
    syncDateLimits();
});
</script>
@endsection
