@extends('layout.admin')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('offer.list') }}" class="btn btn-light rounded-circle shadow-sm" aria-label="Back to offers"><i class="bi bi-arrow-left"></i></a>
        <div><div class="small text-success fw-bold text-uppercase" style="letter-spacing:.12em">Promotion studio</div><h1 class="h3 fw-bold mb-0">Edit offer #{{ $offer->offer_id }}</h1></div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-3"><strong>Please check these details:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4 align-items-start">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4 p-lg-5">
                <h2 class="h5 fw-bold mb-1">Offer details</h2><p class="text-muted small mb-4">Change the product, discount or schedule. The website banner updates automatically.</p>
                <form action="{{ route('offer.update', $offer->offer_id) }}" method="POST" id="offerForm">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label for="product_id" class="form-label fw-semibold">Featured product</label>
                        <select name="product_id" id="product_id" class="form-select form-select-lg" required>
                            @foreach($products as $product)
                                <option value="{{ $product->product_id }}" data-price="{{ (float)$product->price }}" data-name="{{ $product->product_name }}" @selected(old('product_id', $offer->product_id) == $product->product_id)>{{ $product->product_name }} — ${{ number_format((float)$product->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="discount" class="form-label fw-semibold">Discount percentage</label><div class="input-group input-group-lg"><input type="number" id="discount" name="discount" class="form-control" min="0.01" max="99.99" step="0.01" value="{{ old('discount', $offer->discount) }}" required><span class="input-group-text">%</span></div></div>
                        <div class="col-md-6"><label for="new_price_preview" class="form-label fw-semibold">New offer price</label><div class="input-group input-group-lg"><span class="input-group-text">$</span><input type="text" id="new_price_preview" class="form-control fw-bold text-success bg-light" readonly></div><div class="form-text">Calculated automatically.</div></div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6"><label for="start_date" class="form-label fw-semibold">Start date</label><input type="date" name="start_date" id="start_date" class="form-control form-control-lg" value="{{ old('start_date', \Carbon\Carbon::parse($offer->start_date)->format('Y-m-d')) }}" required></div>
                        <div class="col-md-6"><label for="end_date" class="form-label fw-semibold">End date</label><input type="date" name="end_date" id="end_date" class="form-control form-control-lg" value="{{ old('end_date', \Carbon\Carbon::parse($offer->end_date)->format('Y-m-d')) }}" required></div>
                    </div>
                    <div class="alert alert-success bg-success-subtle border-0 mt-4 small"><i class="bi bi-arrow-repeat me-2"></i>Saving changes updates the website banner and online offer price immediately when the offer is currently active.</div>
                    <div class="d-flex flex-wrap gap-2 mt-4"><button type="submit" class="btn btn-success btn-lg rounded-pill px-4"><i class="bi bi-check2-circle me-2"></i>Save changes</button><a href="{{ route('offer.list') }}" class="btn btn-light btn-lg rounded-pill px-4">Cancel</a></div>
                </form>
            </div></div>
        </div>
        <div class="col-xl-5">
            <div class="offer-preview-shell rounded-4 p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-center text-white-50 small mb-3"><span class="fw-bold text-uppercase" style="letter-spacing:.14em">Live preview</span><span><i class="bi bi-eye me-1"></i>Website shop</span></div>
                <div class="offer-preview-card rounded-4 p-4 text-white position-relative overflow-hidden">
                    <div class="position-relative" style="z-index:2;max-width:78%"><span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-3"><i class="bi bi-stars me-1"></i>LIMITED TIME</span><div class="small text-white-50 fw-semibold text-uppercase mb-2">A little something special</div><h3 class="fw-bold mb-2" id="previewProduct">{{ $offer->product->product_name ?? 'Your product' }}</h3><div class="display-6 fw-bold mb-1" id="previewPrice">${{ number_format((float)$offer->new_price, 2) }}</div><div class="small text-white-50"><span id="previewDiscount">{{ number_format((float)$offer->discount, 2) }}%</span> savings on your favourite pick</div></div>
                    <div class="offer-preview-orbit" aria-hidden="true"></div><div class="offer-preview-stamp"><i class="bi bi-tag-fill"></i><span id="previewStamp">{{ number_format((float)$offer->discount, 2) }}% OFF</span></div>
                </div>
                <p class="small text-white-50 mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>The public banner uses the selected product's actual image and active schedule.</p>
            </div>
        </div>
    </div>
</div>
<style>
.offer-preview-shell{background:linear-gradient(140deg,#10251d,#1d4534 58%,#96703a);box-shadow:0 20px 60px rgba(16,37,29,.18)}.offer-preview-card{min-height:300px;background:radial-gradient(circle at 90% 20%,rgba(236,190,105,.3),transparent 35%),linear-gradient(120deg,#18372a,#0d2119 72%);border:1px solid rgba(255,255,255,.13);box-shadow:inset 0 1px rgba(255,255,255,.08)}.offer-preview-orbit{position:absolute;width:230px;height:230px;border:1px solid rgba(245,209,143,.22);border-radius:50%;right:-48px;bottom:-92px;box-shadow:0 0 0 24px rgba(245,209,143,.04),0 0 0 50px rgba(245,209,143,.035)}.offer-preview-stamp{position:absolute;right:20px;top:24px;width:94px;height:94px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;border-radius:50%;background:#edc47b;color:#382714;font-weight:900;transform:rotate(10deg);box-shadow:0 8px 28px rgba(0,0,0,.2);font-size:12px}.offer-preview-stamp i{font-size:20px}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const product=document.getElementById('product_id'), discount=document.getElementById('discount'), preview=document.getElementById('new_price_preview');
    const name=document.getElementById('previewProduct'), price=document.getElementById('previewPrice'), rate=document.getElementById('previewDiscount'), stamp=document.getElementById('previewStamp');
    function update(){const option=product.options[product.selectedIndex], base=Number(option?.dataset.price||0), pct=Math.min(99.99,Math.max(0,Number(discount.value||0))), value=Math.max(0,Math.round((base*(1-pct/100)+Number.EPSILON)*100)/100), label=pct.toFixed(2).replace(/\.00$/,''); preview.value=base?'$'+value.toFixed(2):'—';name.textContent=option?.dataset.name||'Your product';price.textContent='$'+value.toFixed(2);rate.textContent=label+'%';stamp.textContent=label+'% OFF';}
    product.addEventListener('change',update);discount.addEventListener('input',update);update();
});
</script>
@endsection
