@extends('layout.admin')

@section('content')

<div class="container-fluid">

    <div class="card shadow">
        <div class="card-header">
            <h4>Create Purchase</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('purchase.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-control" required>
                        <option value="">Select Supplier</option>

                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->supplier_id }}">
                                {{ $supplier->supplier_name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <div class="mb-3">
                    <label>Product</label>

                    <select name="product_id" class="form-control" required>

                        <option value="">Select Product</option>

                        @foreach($products as $product)

                            <option value="{{ $product->product_id }}">
                                {{ $product->product_name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="mb-3">
                    <label>Quantity</label>

                    <input type="number"
                           name="quantity"
                           class="form-control"
                           required>

                </div>

                <div class="mb-3">
                    <label>Cost Price</label>

                    <input type="number"
                           step="0.01"
                           name="cost_price"
                           class="form-control"
                           required>

                </div>
                <div class="form-group">
                    <label for="purchase_date">Purchase Date</label>

                    <input
                         type="date"
                         name="purchase_date"
                         id="purchase_date"
                         class="form-control"
                         value="{{ old('purchase_date', date('Y-m-d')) }}"
                         required
                     >
                </div>

                <button class="btn btn-primary">
                    Save
                </button>

                <a href="{{ route('purchase.index') }}"
                   class="btn btn-secondary">
                    Back
                </a>

            </form>

        </div>
    </div>

</div>

@endsection