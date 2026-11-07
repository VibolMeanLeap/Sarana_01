@extends('layout.admin')
@section('content')
<div class="mb-3 d-flex justify-content-between align-items-center">
    <a href="{{ route('supplier.create') }}" class="btn btn-primary btn-sm ">New Supplier</a>
    <div class="d-flex justify-content-between align-items-center">
        <form class="d-none d-md-flex ms-4">
            <input class="form-control" type="search" name="search" value="{{ Request::get('search') }}"
                placeholder="Search here...">
            <button type="submit" class="btn btn-primary btn-sm ml-1">Search</button>
        </form>
    </div>
</div>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body d-flex align-items-center">

                <div>
                    <h6 class="text-muted mb-1 small text-uppercase fw-semibold">Total Suppliers</h6>
                    <h4 class="fw-bold mb-0 text-dark">{{ $totalSupplier }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
@include("admin.message.message")
@if(session('error'))
    <div class="alert alert-warning">{{ session('error') }}</div>
@endif
<div class="col-lg-12 grid-margin stretch-card">

    <div class="card">
        <div class="card-body">
            <h2 class="card-title">Suppliers</h2>
            <table class="table">
                <thead class="text-uppercase">
                    <tr>
                        <th>id</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($supp_key as $supp)
                    <tr>
                        <td>{{ $supp->supplier_id }}</td>
                        <td>{{ $supp->name }}</td>
                        <td>{{ $supp->email }}</td>
                        <td>{{ $supp->phone }}</td>
                        <td>{{ $supp->address }}</td>
                        
                        <td>
                           
                        
                        
                            <a href="{{ route('supplier.edit',$supp->supplier_id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            <form action="{{ route('supplier.delete', $supp->supplier_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
            <div class="d-flex justify-content-between mt-4">
                <div class="no-info-pagination">
                    {{ $supp_key->links() }}
                </div>

                <style>
                    .no-info-pagination .pagination+p,
                    .no-info-pagination p.text-muted {
                        display: none !important;
                    }
                </style>
                <div><a href="{{ route('supplier.list') }}" class="btn btn-sm btn-success">Refresh</a></div>
            </div>
        </div>
    </div>
</div>
@endsection