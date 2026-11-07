@extends('layout.admin')

@section('page_title', 'Edit store profile')
@section('topbar_title', 'Edit store profile')

@section('content')
<div class="page-heading">
    <div><h1>Edit store profile</h1><p>Changes made here are shared across your admin, cashier, POS and public website.</p></div>
    <a href="{{ route('setting.list') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-2"></i>Back to settings</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger"><strong>Please correct the following:</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <form action="{{ route('setting.update', $shop->id) }}" method="POST" enctype="multipart/form-data" class="card">
            @csrf
            <div class="card-header"><h2 class="h5 fw-bold mb-1">Business information</h2><p class="small text-muted mb-0">Keep these details accurate for your customers and staff.</p></div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <label for="shop_name" class="form-label fw-semibold">Shop name <span class="text-danger">*</span></label>
                    <input type="text" id="shop_name" name="shop_name" class="form-control form-control-lg" maxlength="255" value="{{ old('shop_name', $shop->shop_name) }}" placeholder="e.g. Coffee Corner" required data-preview-name>
                    <div class="form-text">This name appears in the navigation, browser titles and customer-facing pages.</div>
                </div>
                <div class="mb-4">
                    <label for="logo" class="form-label fw-semibold">Shop logo</label>
                    <input type="file" id="logo" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-file>
                    <div class="form-text">Use a square or horizontal PNG, JPG or WebP image. Maximum file size: 3 MB. Leave blank to keep your current logo.</div>
                </div>
                <div class="mb-4">
                    <label for="address" class="form-label fw-semibold">Shop address</label>
                    <textarea id="address" name="address" class="form-control" rows="3" maxlength="2000" placeholder="Street, district, city, country" data-preview-address>{{ old('address', $shop->address) }}</textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="tel" class="form-label fw-semibold">Telephone number</label>
                        <input type="text" id="tel" name="tel" class="form-control" maxlength="50" value="{{ old('tel', $shop->tel) }}" placeholder="e.g. 010 123 456" data-preview-phone>
                    </div>
                    <div class="col-md-6">
                        <label for="exchange_rate" class="form-label fw-semibold">USD to KHR exchange rate <span class="text-danger">*</span></label>
                        <div class="input-group"><span class="input-group-text">$1 = ៛</span><input type="number" id="exchange_rate" name="exchange_rate" class="form-control" value="{{ old('exchange_rate', $shop->exchange_rate) }}" min="0.0001" max="1000000" step="0.0001" required data-preview-rate></div>
                        <div class="form-text">Used by the POS to show the KHR equivalent of USD prices. Existing orders keep the rate recorded when they were sold.</div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 p-3">
                <span class="small text-muted"><i class="bi bi-shield-check me-1"></i>Only administrators can change store settings.</span>
                <div class="d-flex gap-2"><a href="{{ route('setting.list') }}" class="btn btn-cc-light">Cancel</a><button type="submit" class="btn btn-cc"><i class="bi bi-check2-circle me-2"></i>Save changes</button></div>
            </div>
        </form>
    </div>
    <div class="col-12 col-xl-4">
        <section class="card position-sticky" style="top:100px">
            <div class="card-header"><h2 class="h6 fw-bold mb-0">Live brand preview</h2></div>
            <div class="card-body p-4 text-center">
                <div class="d-flex align-items-center justify-content-center mx-auto rounded-4 border bg-light mb-3" style="width:112px;height:112px;overflow:hidden" id="brandPreviewBox">
                    @if($shop->logo && file_exists(public_path('uploads/'.$shop->logo)))
                        <img src="{{ asset('uploads/'.$shop->logo) }}" alt="Current shop logo" id="brandPreviewImage" style="max-width:100%;max-height:100%;object-fit:contain">
                    @else
                        <i class="bi bi-cup-hot-fill fs-1 text-success" id="brandPreviewIcon"></i><img src="" alt="Logo preview" id="brandPreviewImage" class="d-none" style="max-width:100%;max-height:100%;object-fit:contain">
                    @endif
                </div>
                <h3 class="h5 fw-bold mb-1" data-preview-output-name>{{ $shop->shop_name }}</h3>
                <p class="small text-muted mb-4">Store identity preview</p>
                <div class="text-start border-top pt-3">
                    <div class="small text-muted mb-1">Address</div><div class="fw-semibold mb-3" data-preview-output-address>{{ $shop->address ?: 'Not configured' }}</div>
                    <div class="small text-muted mb-1">Telephone</div><div class="fw-semibold mb-3" data-preview-output-phone>{{ $shop->tel ?: 'Not configured' }}</div>
                    <div class="small text-muted mb-1">Exchange rate</div><div class="fw-semibold">$1 USD = <span data-preview-output-rate>{{ number_format((float)$shop->exchange_rate, 0) }}</span> KHR</div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.querySelector('[data-preview-name]');
    const addressInput = document.querySelector('[data-preview-address]');
    const phoneInput = document.querySelector('[data-preview-phone]');
    const rateInput = document.querySelector('[data-preview-rate]');
    const nameOutput = document.querySelector('[data-preview-output-name]');
    const addressOutput = document.querySelector('[data-preview-output-address]');
    const phoneOutput = document.querySelector('[data-preview-output-phone]');
    const rateOutput = document.querySelector('[data-preview-output-rate]');
    const fileInput = document.querySelector('[data-preview-file]');
    const image = document.getElementById('brandPreviewImage');
    const icon = document.getElementById('brandPreviewIcon');

    nameInput?.addEventListener('input', () => nameOutput.textContent = nameInput.value.trim() || 'Your shop name');
    addressInput?.addEventListener('input', () => addressOutput.textContent = addressInput.value.trim() || 'Not configured');
    phoneInput?.addEventListener('input', () => phoneOutput.textContent = phoneInput.value.trim() || 'Not configured');
    rateInput?.addEventListener('input', () => {
        const value = Number(rateInput.value);
        rateOutput.textContent = Number.isFinite(value) && value > 0 ? Math.round(value).toLocaleString('en-US') : '—';
    });
    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) return;
        const previewUrl = URL.createObjectURL(file);
        image.src = previewUrl;
        image.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
    });
});
</script>
@endsection
