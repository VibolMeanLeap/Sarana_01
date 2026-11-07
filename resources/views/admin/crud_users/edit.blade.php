@extends('layout.admin')
@section('content')
<div class="d-flex justify-content-center align-items-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="card-title">Update Users</div>
                <hr>
               <form action="{{ route('auth.update', $user->user_id) }}" method="POST" >
                @csrf
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control form-control-rounded" id="name" name="name"
                        value="{{ $user->name ?? 'null' }}" placeholder="Enter Your Name">
                    <p class="invalid-feedback m-0"></p>
                </div>
            
                <div class="form-group">
                    <label for="role_id">Choose Role</label>
                    <select id="role_id" name="role_id" class="form-control">
                        @foreach ($role as $r)
                        <option value="{{ $r->role_id }}" {{ $user->role_id == $r->role_id ? 'selected' : '' }}>
                            {{ $r->role_name }}
                        </option>
                        @endforeach
                    </select>
                    <p class="invalid-feedback m-0"></p>
                </div>
            
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control form-control-rounded" id="phone" name="phone"
                        value="{{ $user->phone ?? 'null' }}" placeholder="Enter Your Mobile Number">
                    <p class="invalid-feedback m-0"></p>
                </div>
            
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control form-control-rounded" id="username" name="username"
                        value="{{ $user->username ?? 'null' }}" placeholder="Enter username">
                    <p class="invalid-feedback m-0"></p>
                </div>
            
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control form-control-rounded" id="email" name="email"
                        value="{{ $user->email ?? 'null' }}" placeholder="Enter Your Email">
                    <p class="invalid-feedback m-0"></p>
                </div>
            
                <div class="form-group">
                    <label for="password">Password</label>
                    <!-- Best practice: Keep password field blank during profile updates -->
                    <input type="password" class="form-control form-control-rounded" id="password" name="password"
                        placeholder="Leave blank to keep current password">
                    <p class="invalid-feedback m-0"></p>
                </div>
            
                <div class="form-group py-2">
                    <div class="icheck-material-white">
                        <input type="hidden" name="status" value="inactive">
                        <input type="checkbox" id="status" name="status" value="active" {{ $user->status === 'active' ? 'checked' : ''}} />
                        <label for="status">Status (Active)</label>
                    </div>
                    <p class="invalid-feedback m-0"></p>
                </div>
            
                <div class="">
                    <a href="{{ route('auth.list') }}" class="btn btn-sm btn-light">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection