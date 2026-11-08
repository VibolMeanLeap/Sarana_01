@extends('layout.admin')

@section('content')
<div class="container py-5">
    <div class="card border-0 shadow-sm rounded-4 mx-auto" style="max-width:680px">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px;background:#edf7f0;color:#176b4b">
                <i class="bi bi-receipt-cutoff fs-2"></i>
            </div>
            <h1 class="h4 fw-bold">Order items are created through checkout</h1>
            <p class="text-muted mb-4">To keep stock, order totals and payment history accurate, items must be added through the POS or website cart. Manual order-item creation is disabled.</p>
            <a href="{{ route('admin.older_items') }}" class="btn btn-success rounded-pill px-4">Back to order items</a>
        </div>
    </div>
</div>
@endsection
