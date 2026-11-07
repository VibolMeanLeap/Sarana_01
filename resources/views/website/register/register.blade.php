@extends('website.component.master')
@section('web_title', 'Register')
@section('content')

<ul class="nav auth-tabs mb-4" id="authTab" role="tablist">
    <li class="nav-item" role="presentation">
        <a href="{{ url('web/login') }}" class="nav-link active" role="tab">Log In</a>
    </li>
</ul>

<div class="">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark mb-1">Create Account</h4>
        <p class="text-muted small">Join us to earn points and enjoy fast checkout</p>
    </div>
    @if ($errors->any())
    <div class="alert alert-danger py-2 small">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('register') }}" method="post">
        @csrf
        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Full Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control form-control-with-icon @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" name="name" placeholder="John Doe" required>
            </div>
            @error('name')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Phone Number</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="text" class="form-control form-control-with-icon @error('phone') is-invalid @enderror"
                    value="{{ old('phone') }}" name="phone" placeholder="012345678" required>
            </div>
            @error('phone')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control form-control-with-icon @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" name="email" placeholder="name@example.com" required>
            </div>
            @error('email')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password"
                    class="form-control form-control-with-icon @error('password') is-invalid @enderror" name="password"
                    placeholder="Min. 8 characters" required>
            </div>
            @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control form-control-with-icon" name="password_confirmation"
                    placeholder="Re-enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-green w-100 rounded-3 mb-3">Create Free Account</button>
    </form>

    <div class="position-relative text-center my-4">
        <hr class="text-muted opacity-25">
        <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 small text-muted"></span>
    </div>
</div>

@endsection