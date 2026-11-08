@extends('layout.admin')

@section('page_title', 'Edit customer account')
@section('topbar_title', 'Edit customer account')

@section('content')
<div class="page-heading">
    <div>
        <div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Customer management</div>
        <h1>Edit customer</h1>
        <p>Update customer contact details and website access. Order history remains attached to this customer.</p>
    </div>
    <a href="{{ route('auth.list') }}" class="btn btn-cc-light"><i class="bi bi-arrow-left me-2"></i>Back to customers</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger border-0 rounded-4" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please review the highlighted fields.</div>
        <div class="small">{{ $errors->first() }}</div>
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:46px;height:46px;background:#eaf4ed;color:#176b4b"><i class="bi bi-person-gear fs-4"></i></span>
                    <div><h2 class="h5 fw-bold mb-1">Customer profile</h2><div class="small text-muted">Customer account #{{ $customerAccount->user_id }}</div></div>
                </div>
            </div>
            <form method="POST" action="{{ route('customer.accounts.update', $customerAccount->user_id) }}" novalidate>
                @csrf @method('PUT')
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="customer_name" class="form-label fw-semibold">Full name <span class="text-danger">*</span></label>
                            <input id="customer_name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customerAccount->name) }}" minlength="2" maxlength="255" autocomplete="name" required>
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="customer_phone" class="form-label fw-semibold">Phone number <span class="text-danger">*</span></label>
                            <input id="customer_phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customerAccount->phone) }}" maxlength="20" autocomplete="tel" required>
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="customer_email" class="form-label fw-semibold">Email address <span class="text-danger">*</span></label>
                            <input id="customer_email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customerAccount->email) }}" maxlength="255" autocomplete="email" required>
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="customer_status" class="form-label fw-semibold">Website account access <span class="text-danger">*</span></label>
                            <select id="customer_status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" @selected(old('status', $customerAccount->status) === 'active')>Active – customer can sign in</option>
                                <option value="inactive" @selected(old('status', $customerAccount->status) !== 'active')>Inactive – sign-in blocked</option>
                            </select>
                            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 px-4 py-3 d-flex justify-content-end gap-2">
                    <a href="{{ route('auth.list') }}" class="btn btn-light border rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-success rounded-pill px-4"><i class="bi bi-check2-circle me-2"></i>Save customer</button>
                </div>
            </form>
        </section>
        <div class="alert alert-light border rounded-4 mt-3 small"><i class="bi bi-shield-check text-success me-2"></i>This screen only edits accounts with the Customer role. Administrator and Cashier accounts can only be managed from Settings.</div>
    </div>
</div>
@endsection
