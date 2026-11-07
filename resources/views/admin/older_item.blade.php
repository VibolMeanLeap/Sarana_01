@extends('layout.admin')

@section('content')

<style>
    .oi-page-bg { background: #f3f4f6; min-height: 100vh; padding: 24px; }
    .oi-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
    .oi-page-title { font-size: 20px; font-weight: 700; color: #111827; margin: 0; }
    .btn-add-oi { background: #2563eb; border: none; color: #fff; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-add-oi:hover { background: #1d4ed8; color: #fff; }
    .oi-card { background: #fff; border-radius: 14px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 24px; }
    .oi-search-form {
        display: flex;
        justify-content: flex-end;
        gap: 0;
        margin-bottom: 18px;
    }
    .oi-search-form .form-control {
        border-radius: 8px 0 0 8px;
        border: 1px solid #e5e7eb;
        font-size: 14px;
        flex: 0 0 auto;
        width: 260px;
        max-width: 260px;
    }
    .oi-search-form .btn-search {
        border-radius: 0 8px 8px 0;
        background: #2563eb;
        border: 1px solid #2563eb;
        color: #fff;
        font-size: 14px;
        padding: 6px 20px;
        flex: 0 0 auto;
    }
    .oi-search-form .btn-search:hover { background: #1d4ed8; }
    .oi-table { width: 100%; border-collapse: collapse; }
    .oi-table thead th { text-align: left; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
    .oi-table tbody td { padding: 12px; font-size: 14px; color: #111827; border-bottom: 1px solid #f1f2f4; vertical-align: middle; }
    .oi-table tbody tr:hover { background: #f9fafb; }

    /* Icon-style action buttons (matching Order List design) */
    .oi-action-group { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
    .btn-icon-edit,
    .btn-icon-delete {
        background: transparent;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        padding: 0;
    }
    .btn-icon-edit { color: #d97706; }
    .btn-icon-edit:hover { background: #fef3c7; color: #b45309; }
    .btn-icon-delete { color: #dc2626; }
    .btn-icon-delete:hover { background: #fee2e2; color: #b91c1c; }

    .oi-pagination nav p { display: none !important; }
    .oi-pagination { display: flex; justify-content: flex-end; margin-top: 18px; }
    .oi-pagination .page-link { border-radius: 8px !important; border: 1px solid #e5e7eb; color: #374151; font-size: 13px; min-width: 32px; text-align: center; }
    .oi-pagination .page-item.active .page-link { background: #2563eb; border-color: #2563eb; color: #fff; }
</style>

<div class="oi-page-bg">

    <div class="oi-topbar">
        <h4 class="oi-page-title">Order Item List</h4>
        <a href="{{ route('order_items.create') }}" class="btn-add-oi">
            <i class="fa fa-plus"></i> Add New Order Item
        </a>
    </div>

    <div class="oi-card">

        <form action="{{ route('admin.older_items') }}" method="GET" class="oi-search-form">
            @if($orderId) <input type="hidden" name="order_id" value="{{ $orderId }}"> @endif
            <input type="text" name="search" class="form-control" placeholder="Search order ID or product..." value="{{ request('search') }}">
            <button class="btn-search" type="submit">Search</button>
        </form>
        @if($orderId)
            <div class="alert alert-info py-2 small d-flex justify-content-between align-items-center">
                Showing items for order #{{ $orderId }}.
                <a href="{{ route('admin.older_items') }}" class="alert-link">Show all order items</a>
            </div>
        @endif

        <div class="table-responsive">
            <table class="oi-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                        <th style="width:90px; text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orderItems as $row)
                        <tr>
                            <td>{{ $row->order_item_id }}</td>
                            <td>#{{ $row->order_id }}</td>
                            <td>{{ $row->product->product_name ?? '-' }}</td>
                            <td>{{ $row->quantity ?? '-' }}</td>
                            <td>${{ $row->price !== null ? number_format($row->price, 2) : '-' }}</td>
                            <td>${{ ($row->price !== null && $row->quantity !== null) ? number_format($row->price * $row->quantity, 2) : '-' }}</td>
                            <td>
                                <div class="oi-action-group">
                                    <a href="{{ route('order_items.edit', $row->order_item_id) }}" class="btn-icon-edit" title="Edit order item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>

                                    <form action="{{ route('order_items.destroy', $row->order_item_id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-icon-delete btn-delete" title="Delete order item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No order items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="oi-pagination">
            {{ $orderItems->links('pagination::simple-bootstrap-5') }}
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('success'))
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
                didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); } });
            Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
        @endif

        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function () {
                const form = this.closest('.delete-form');
                Swal.fire({ title: 'Are you sure?', text: "Do you want to delete this order item?", icon: 'delete', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Yes, delete it!' })
                    .then((result) => { if (result.isConfirmed) form.submit(); });
            });
        });
    });
</script>

@endsection