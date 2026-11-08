@extends('layout.admin')

@section('page_title', 'Edit staff account')
@section('topbar_title', 'Edit staff account')

@section('content')
<div class="page-heading">
    <div><div class="small text-success text-uppercase fw-bold mb-2" style="letter-spacing:.12em">Settings & staff management</div><h1>Edit staff account</h1><p>Update the team member's profile, role, password or sign-in status.</p></div>
    <a href="{{ route('setting.list') }}#staff-accounts" class="btn btn-cc-light"><i class="bi bi-arrow-left me-2"></i>Back to settings</a>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <section class="card">
            <div class="card-header"><div class="d-flex align-items-center gap-3"><span class="settings-stat-icon"><i class="bi bi-person-gear"></i></span><div><h2 class="h5 fw-bold mb-1">{{ $staff->name }}</h2><p class="small text-muted mb-0">Account #{{ $staff->user_id }} · {{ ucfirst($staff->role?->role_name ?? 'Staff') }}</p></div></div></div>
            <form method="POST" action="{{ route('setting.staff.update', $staff->user_id) }}" data-validation-form>
                @csrf @method('PUT')
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold" for="name">Full name <span class="text-danger">*</span></label><input id="name" name="name" type="text" class="form-control" value="{{ old('name', $staff->name) }}" minlength="2" maxlength="255" required>@error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold" for="role_id">Account role <span class="text-danger">*</span></label><select id="role_id" name="role_id" class="form-select" required>@foreach($staffRoles as $role)<option value="{{ $role->role_id }}" @selected((string)old('role_id', $staff->role_id)===(string)$role->role_id)>{{ ucfirst($role->role_name) }}</option>@endforeach</select>@error('role_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold" for="phone">Phone <span class="text-danger">*</span></label><input id="phone" name="phone" type="tel" class="form-control" value="{{ old('phone', $staff->phone) }}" maxlength="20" required>@error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold" for="username">Username <span class="text-danger">*</span></label><input id="username" name="username" type="text" class="form-control" value="{{ old('username', $staff->username) }}" minlength="3" maxlength="200" required>@error('username')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-12"><label class="form-label fw-semibold" for="email">Email <span class="text-danger">*</span></label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $staff->email) }}" maxlength="255" required>@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold" for="password">New password</label><input id="password" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" data-optional><div class="form-text">Leave blank to keep the current password.</div>@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6"><label class="form-label fw-semibold" for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" autocomplete="new-password" data-optional data-required-when-filled="password"><div class="form-text">Required only when entering a new password.</div></div>
                        <div class="col-md-6"><label class="form-label fw-semibold" for="status">Account status <span class="text-danger">*</span></label><select id="status" name="status" class="form-select" required><option value="active" @selected(old('status', $staff->status)==='active')>Active – can sign in</option><option value="inactive" @selected(old('status', $staff->status)==='inactive')>Inactive – sign-in blocked</option></select>@error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center gap-2"><span class="small text-muted"><i class="bi bi-shield-lock me-1"></i>Only administrators can manage staff.</span><div class="d-flex gap-2"><a href="{{ route('setting.list') }}#staff-accounts" class="btn btn-cc-light">Cancel</a><button type="submit" class="btn btn-cc"><i class="bi bi-check2-circle me-1"></i>Save changes</button></div></div>
            </form>
        </section>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card"><div class="card-body p-4"><div class="settings-stat-icon mb-3"><i class="bi bi-info-circle"></i></div><h2 class="h6 fw-bold">Account access</h2><p class="small text-muted">Administrator accounts can access the management workspace. Cashier accounts can access POS and their order-processing workspace. Set the role according to the employee's duties.</p><hr><div class="small text-muted">Created</div><div class="fw-semibold mb-3">{{ $staff->created_at?->format('d M Y, h:i A') ?? '—' }}</div><div class="small text-muted">Current status</div><span class="settings-status {{ strtolower($staff->status)==='active'?'active':'inactive' }}">{{ ucfirst($staff->status) }}</span></div></div>
    </div>
</div>
@endsection
