@extends('layout.admin')

@section('page_title', 'Create store profile')
@section('topbar_title', 'Create store profile')

@section('content')
<div class="page-heading"><div><h1>Create store profile</h1><p>Configure the identity shared by the whole application.</p></div><a href="{{ route('setting.list') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-2"></i>Back</a></div>
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('setting.store') }}" method="POST" enctype="multipart/form-data" class="card">
    @csrf
    <div class="card-header"><h2 class="h5 fw-bold mb-0">Store information</h2></div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold" for="shop_name">Shop name *</label><input id="shop_name" type="text" name="shop_name" class="form-control" value="{{ old('shop_name') }}" required maxlength="255"></div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="tel">Telephone</label><input id="tel" type="text" name="tel" class="form-control" value="{{ old('tel') }}" maxlength="50" data-optional></div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="exchange_rate">USD to KHR exchange rate *</label><div class="input-group"><span class="input-group-text">$1 = ៛</span><input id="exchange_rate" type="number" step="0.0001" min="0.0001" name="exchange_rate" class="form-control" value="{{ old('exchange_rate', 4100) }}" required></div></div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="delivery_fee">Home delivery fee (USD) *</label><div class="input-group"><span class="input-group-text">$</span><input id="delivery_fee" type="number" step="0.01" min="0" max="99999999" name="delivery_fee" class="form-control" value="{{ old('delivery_fee', number_format((float) ($deliveryFee ?? 0), 2, '.', '')) }}" required></div><div class="form-text">Applied to new POS and website home-delivery orders. Enter 0 for free delivery.</div>@error('delivery_fee')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label fw-semibold" for="logo">Shop logo</label><input id="logo" type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-optional><div class="form-text">JPG, PNG or WebP, max 3 MB.</div></div>
            <div class="col-12"><label class="form-label fw-semibold" for="address">Shop address</label><textarea id="address" name="address" rows="3" class="form-control" maxlength="2000" data-optional>{{ old('address') }}</textarea></div>
        </div>
    </div>
    <div class="card-footer bg-white p-3 d-flex justify-content-end gap-2"><a href="{{ route('setting.list') }}" class="btn btn-cc-light">Cancel</a><button class="btn btn-cc" type="submit"><i class="bi bi-check2-circle me-2"></i>Save store profile</button></div>
</form>
@endsection
