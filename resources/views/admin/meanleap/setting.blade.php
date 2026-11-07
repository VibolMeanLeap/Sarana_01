@extends('layout.admin')

@section('page_title', 'Store settings & team access')
@section('topbar_title', 'Store settings & team access')

@section('content')
<style>
    .settings-hero{background:radial-gradient(circle at 90% 0%,rgba(225,190,125,.2),transparent 28%),linear-gradient(120deg,#102a20,#1c5239);border-radius:24px;color:#fff;padding:28px 30px;position:relative;overflow:hidden}
    .settings-hero:after{content:"";position:absolute;right:-75px;bottom:-150px;width:300px;height:300px;border:1px solid rgba(255,255,255,.18);border-radius:50%;box-shadow:0 0 0 24px rgba(255,255,255,.035),0 0 0 52px rgba(255,255,255,.025)}
    .settings-hero>*{position:relative;z-index:1}
    .settings-stat{border:1px solid #e8eee9;border-radius:17px;background:#fff;padding:17px;height:100%}
    .settings-stat-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:13px;background:#edf7f0;color:#176b4b;font-size:1.15rem}
    .settings-card{border:1px solid #e8eee9;border-radius:19px;background:#fff;box-shadow:0 8px 26px rgba(21,53,35,.045)}
    .settings-card .card-header{background:#fff;border-bottom:1px solid #edf1ed;border-radius:19px 19px 0 0;padding:20px 22px}
    .settings-label{font-size:.77rem;letter-spacing:.04em;color:#53645a;font-weight:750}
    .settings-avatar{width:39px;height:39px;border-radius:12px;display:grid;place-items:center;background:#edf5ef;color:#1b6946;font-weight:800}
    .settings-role{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;border-radius:99px;padding:5px 9px;background:#edf7f0;color:#176b4b}
    .settings-status{font-size:.7rem;font-weight:800;border-radius:99px;padding:5px 9px}
    .settings-status.active{background:#e8f7ed;color:#187447}.settings-status.inactive{background:#fff0ee;color:#b5473d}
    .settings-anchor{scroll-margin-top:100px}
    @media(max-width:575px){.settings-hero{padding:23px 20px}.settings-hero h1{font-size:1.55rem}}
</style>

<div class="settings-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.16em;color:#e8c990"><i class="bi bi-sliders2-vertical me-2"></i>Control centre</div>
            <h1 class="h2 fw-bold mb-2">Settings & team access</h1>
            <p class="mb-0 text-white-50" style="max-width:640px">Manage your shop identity and create secure administrator or cashier accounts from one place.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="#store-profile" class="btn btn-light rounded-pill fw-bold px-3"><i class="bi bi-shop me-2"></i>Store profile</a>
            <a href="#staff-accounts" class="btn rounded-pill fw-bold px-3" style="background:#e9c58c;color:#2d281d"><i class="bi bi-people me-2"></i>Team access</a>
        </div>
    </div>
</div>

@include('admin.message.message')

@if ($errors->any())
    <div class="alert alert-danger border-0 rounded-4 shadow-sm" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please check the form details.</div>
        <ul class="mb-0 ps-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<section id="store-profile" class="settings-anchor mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div><div class="small text-success text-uppercase fw-bold" style="letter-spacing:.12em">Business identity</div><h2 class="h4 fw-bold mb-1">Store profile</h2><p class="text-muted small mb-0">The business information displayed across POS, admin and website.</p></div>
        @if($shop)
            <a href="{{ route('setting.edit', $shop->id) }}" class="btn btn-cc"><i class="bi bi-pencil-square me-2"></i>Edit store profile</a>
        @else
            <a href="{{ route('setting.create') }}" class="btn btn-cc"><i class="bi bi-plus-lg me-2"></i>Create store profile</a>
        @endif
    </div>

    @if($shop)
        <div class="row g-3">
            <div class="col-12 col-xl-4">
                <section class="settings-card h-100 overflow-hidden">
                    <div class="p-4 text-center" style="background:linear-gradient(135deg,#edf7f0,#f8fbf8)">
                        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-4 shadow-sm p-3 mb-3" style="width:100px;height:100px">
                            @if($shop->logo && file_exists(public_path('uploads/'.$shop->logo)))
                                <img src="{{ asset('uploads/'.$shop->logo) }}" alt="{{ $shop->shop_name }} logo" style="max-width:100%;max-height:100%;object-fit:contain">
                            @else
                                <i class="bi bi-cup-hot-fill fs-1" style="color:#176b4b"></i>
                            @endif
                        </div>
                        <div class="small text-uppercase fw-bold text-muted" style="letter-spacing:.1em">Active store identity</div>
                        <h3 class="h5 fw-bold mt-2 mb-1">{{ $shop->shop_name }}</h3>
                        <span class="badge rounded-pill" style="background:#e4f3e9;color:#176b4b">Live application profile</span>
                    </div>
                    <div class="p-4">
                        <div class="d-flex gap-3 mb-3"><i class="bi bi-geo-alt text-success fs-5"></i><div><div class="small text-muted">Store address</div><div class="fw-semibold">{{ $shop->address ?: 'Not configured' }}</div></div></div>
                        <div class="d-flex gap-3 mb-3"><i class="bi bi-telephone text-success fs-5"></i><div><div class="small text-muted">Contact telephone</div><div class="fw-semibold">{{ $shop->tel ?: 'Not configured' }}</div></div></div>
                        <div class="d-flex gap-3"><i class="bi bi-currency-exchange text-success fs-5"></i><div><div class="small text-muted">Currency conversion</div><div class="fw-semibold">$1 USD = ៛{{ number_format((float)$shop->exchange_rate, 0) }} KHR</div></div></div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-8">
                <section class="settings-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between"><div><h3 class="h6 fw-bold mb-1">One profile across your business</h3><p class="small text-muted mb-0">Changes are reflected across the application after refresh.</p></div><i class="bi bi-diagram-3 fs-4 text-success"></i></div>
                    <div class="p-4"><div class="row g-3">
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="fw-bold mb-1"><i class="bi bi-speedometer2 text-success me-2"></i>Admin workspace</div><p class="small text-muted mb-0">Brand identity and management tools.</p></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="fw-bold mb-1"><i class="bi bi-person-badge text-success me-2"></i>Cashier workspace</div><p class="small text-muted mb-0">Staff navigation and daily operations.</p></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="fw-bold mb-1"><i class="bi bi-cash-register text-success me-2"></i>Point of Sale</div><p class="small text-muted mb-0">Shop branding and USD/KHR conversion.</p></div></div>
                        <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="fw-bold mb-1"><i class="bi bi-globe2 text-success me-2"></i>Public website</div><p class="small text-muted mb-0">Customer-facing shop identity and contacts.</p></div></div>
                    </div></div>
                </section>
            </div>
        </div>
    @else
        <section class="settings-card p-4 text-center"><i class="bi bi-shop-window display-5 text-success"></i><h3 class="h5 fw-bold mt-3">Your store profile is not configured yet</h3><p class="text-muted">Create your store identity to show consistent business details throughout the system.</p><a href="{{ route('setting.create') }}" class="btn btn-cc">Set up store profile</a></section>
    @endif
</section>

<section id="staff-accounts" class="settings-anchor">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div><div class="small text-success text-uppercase fw-bold" style="letter-spacing:.12em">People & permissions</div><h2 class="h4 fw-bold mb-1">Staff accounts</h2><p class="text-muted small mb-0">Create staff sign-ins and choose which workspace each person can access.</p></div>
        <a href="{{ route('auth.list') }}" class="btn btn-outline-success rounded-pill px-3"><i class="bi bi-people me-2"></i>Manage all users</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-4"><div class="settings-stat"><div class="d-flex align-items-center justify-content-between"><span class="small text-muted fw-semibold">Total staff accounts</span><span class="settings-stat-icon"><i class="bi bi-people-fill"></i></span></div><div class="display-6 fw-bold mt-2">{{ $staffTotal }}</div><div class="small text-muted">Administrators and cashiers</div></div></div>
        <div class="col-12 col-sm-4"><div class="settings-stat"><div class="d-flex align-items-center justify-content-between"><span class="small text-muted fw-semibold">Active administrators</span><span class="settings-stat-icon"><i class="bi bi-shield-lock-fill"></i></span></div><div class="display-6 fw-bold mt-2">{{ $activeAdmins }}</div><div class="small text-muted">Full management access</div></div></div>
        <div class="col-12 col-sm-4"><div class="settings-stat"><div class="d-flex align-items-center justify-content-between"><span class="small text-muted fw-semibold">Active cashiers</span><span class="settings-stat-icon"><i class="bi bi-person-workspace"></i></span></div><div class="display-6 fw-bold mt-2">{{ $activeCashiers }}</div><div class="small text-muted">POS and order processing</div></div></div>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-12 col-xl-5">
            <section class="settings-card overflow-hidden">
                <div class="card-header"><div class="d-flex align-items-center gap-3"><span class="settings-stat-icon"><i class="bi bi-person-plus-fill"></i></span><div><h3 class="h6 fw-bold mb-1">Create a staff account</h3><p class="small text-muted mb-0">Set up access for a new team member.</p></div></div></div>
                <form method="POST" action="{{ route('setting.staff.store') }}" class="p-4">
                    @csrf
                    <div class="mb-3"><label for="staff_name" class="form-label settings-label">FULL NAME</label><input id="staff_name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="255" placeholder="e.g. Dara Sok" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-3"><label for="staff_role" class="form-label settings-label">ACCOUNT ROLE</label><select id="staff_role" name="role_id" class="form-select @error('role_id') is-invalid @enderror" required><option value="">Choose a workspace</option>@foreach($staffRoles as $role)<option value="{{ $role->role_id }}" @selected((string) old('role_id') === (string) $role->role_id)>{{ strtolower($role->role_name) === 'admin' ? 'Administrator – full management access' : 'Cashier – POS and order processing' }}</option>@endforeach</select>@error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3"><label for="staff_phone" class="form-label settings-label">PHONE</label><input id="staff_phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" maxlength="20" placeholder="010 123 456" required>@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6 mb-3"><label for="staff_username" class="form-label settings-label">LOGIN USERNAME</label><input id="staff_username" name="username" type="text" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" maxlength="200" placeholder="dara.cashier" required>@error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    </div>
                    <div class="mb-3"><label for="staff_email" class="form-label settings-label">EMAIL ADDRESS</label><input id="staff_email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" maxlength="255" placeholder="staff@coffeecorner.com" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3"><label for="staff_password" class="form-label settings-label">TEMPORARY PASSWORD</label><input id="staff_password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" minlength="8" autocomplete="new-password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-md-6 mb-3"><label for="staff_password_confirmation" class="form-label settings-label">CONFIRM PASSWORD</label><input id="staff_password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
                    </div>
                    <div class="mb-4"><label for="staff_status" class="form-label settings-label">ACCOUNT STATUS</label><select id="staff_status" name="status" class="form-select" required><option value="active" @selected(old('status', 'active') === 'active')>Active – can sign in</option><option value="inactive" @selected(old('status') === 'inactive')>Inactive – sign-in blocked</option></select></div>
                    @if($staffRoles->isEmpty())<div class="alert alert-warning small">No admin or cashier roles were found in the <code>role</code> table. Please check your role data before creating accounts.</div>@endif
                    <button type="submit" class="btn btn-cc w-100" @disabled($staffRoles->isEmpty())><i class="bi bi-shield-check me-2"></i>Create staff account</button>
                    <p class="small text-muted text-center mt-3 mb-0"><i class="bi bi-lock-fill me-1"></i>Passwords are securely hashed. Only administrators can create staff accounts.</p>
                </form>
            </section>
        </div>

        <div class="col-12 col-xl-7">
            <section class="settings-card overflow-hidden">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h3 class="h6 fw-bold mb-1">Recently added staff</h3><p class="small text-muted mb-0">Latest administrator and cashier accounts.</p></div><span class="badge rounded-pill text-bg-light">{{ $staffAccounts->count() }} shown</span></div>
                @if($staffAccounts->isEmpty())
                    <div class="text-center p-5"><i class="bi bi-person-badge display-5 text-success"></i><h4 class="h6 fw-bold mt-3">No staff accounts found</h4><p class="text-muted small mb-0">Create the first cashier or administrator using the form.</p></div>
                @else
                    <div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th class="ps-4">TEAM MEMBER</th><th>WORKSPACE</th><th>STATUS</th><th class="text-end pe-4">ACTION</th></tr></thead><tbody>
                        @foreach($staffAccounts as $staff)
                            <tr>
                                <td class="ps-4"><div class="d-flex align-items-center gap-2"><span class="settings-avatar">{{ strtoupper(substr($staff->name, 0, 1)) }}</span><div><div class="fw-bold">{{ $staff->name }}</div><div class="small text-muted">{{ $staff->username }}</div></div></div></td>
                                <td><span class="settings-role">{{ ucfirst($staff->role?->role_name ?? 'Unknown') }}</span></td>
                                <td><span class="settings-status {{ strtolower($staff->status) === 'active' ? 'active' : 'inactive' }}">{{ ucfirst($staff->status) }}</span></td>
                                <td class="text-end pe-4"><a href="{{ route('auth.edit', $staff->user_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill" title="Edit staff account"><i class="bi bi-pencil-square me-1"></i>Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody></table></div>
                @endif
                <div class="border-top p-3 d-flex flex-wrap justify-content-between align-items-center gap-2"><span class="small text-muted">For password changes, role updates and deactivation, open staff management.</span><a href="{{ route('auth.list') }}" class="small fw-bold text-success text-decoration-none">Open staff management <i class="bi bi-arrow-right"></i></a></div>
            </section>
            <div class="alert alert-light border rounded-4 mt-3 small mb-0"><i class="bi bi-info-circle-fill text-success me-2"></i><strong>Role guidance:</strong> Administrators can manage the system and staff. Cashiers can access POS and their order-processing workspace. Create only the level of access each person needs.</div>
        </div>
    </div>
</section>
@endsection
