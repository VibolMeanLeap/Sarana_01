@extends('layout.admin')
@section('content')
@include("admin.message.message")
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body d-flex align-items-center">
                    
                    <div>
                        <h6 class="text-muted mb-1 small text-uppercase fw-semibold">Total Customers</h6>
                        <h4 class="fw-bold mb-0 text-dark">{{ $totalCustomers }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- តារាងបង្ហាញបញ្ជីឈ្មោះ Customers -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold text-dark mb-0">Customer List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $customer->customer_id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center me-2"
                                        style="width: 35px; height: 35px;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <span class="fw-semibold">{{ $customer->name }}</span>
                                </div>
                            </td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->user?->email ?? 'N/A' }}</td>
                            <td>
                                @php
                                $status = strtolower($customer->user?->status ?? 'inactive');
                                @endphp
                                <label class="badge {{ $status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                    {{ ucfirst($customer->user?->status ?? 'inactive') }}
                                </label>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No customers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Links -->
        <div class="card-footer bg-white border-0 py-3">
            {{ $customers->links() }}
        </div>
    </div>

</div>
@endsection