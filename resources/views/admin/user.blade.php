@extends('layout.admin')
@section('content')

<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('auth.create') }}" class="btn btn-primary btn-sm ">New User</a>
  <div class="d-flex justify-content-between align-items-center">
    <form class="d-none d-md-flex ms-4">
        <input class="form-control" type="search" name="search" value="{{ Request::get('search') }}"
            placeholder="Search here...">
        <button type="submit" class="btn btn-primary btn-sm ml-1">Search</button>
    </form>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body d-flex align-items-center">
                <div>
                    <h6 class="text-muted mb-1 small text-uppercase fw-semibold">Total Customers</h6>
                    <h4 class="fw-bold mb-0 text-dark">{{ $totalCustomers }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body d-flex align-items-center">
                <div>
                    <h6 class="text-muted mb-1 small text-uppercase fw-semibold">Total Users</h6>
                    <h4 class="fw-bold mb-0 text-dark">{{ $totalUser }}</h4>
                </div>
            </div>
        </div>
    </div>

</div>
@include("admin.message.message")
<div class="col-lg-12 grid-margin stretch-card">
  
    <div class="card">
        <div class="card-body">
            <h2 class="card-title">Users</h2>
            <table class="table">
                <thead class="text-uppercase">
                    <tr>
                        <th>id</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                   @foreach ($user_key as $user)
                       <tr>
                            <td>{{ $user->user_id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->role->role_name ?? 'No Role Assigned' }}</td>
                            <td>
                                <label class="badge {{ strtolower($user->status) === 'active' ? 'badge-success' : 'badge-danger' }}">
                                    {{ ucfirst($user->status) }}
                                </label>
                            </td>
                            <td>
                              <form action="{{ route('auth.delete', $user->user_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this staff account?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete" class="btn btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                              </form>
                                <a href="{{ route('auth.edit',$user->user_id) }}" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            </td>
                        </tr>
                   @endforeach
                    
                </tbody>
            </table>
           <div class="d-flex justify-content-between mt-4">
               <div class="no-info-pagination">
                {{ $user_key->links() }}
            </div>
            
            <style>
                .no-info-pagination .pagination+p,
                .no-info-pagination p.text-muted {
                    display: none !important;
                }
            </style>
                <div><a href="{{ route('auth.list') }}" class="btn btn-sm btn-success">Refresh</a></div>
            </div>
        </div>
    </div>
</div>
@endsection

