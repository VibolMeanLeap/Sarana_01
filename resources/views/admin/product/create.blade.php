@extends('layout.admin')

@section('content')

<style>
    .prod-page-bg {
        background: #f3f4f6;
        min-height: 100vh;
        padding: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .prod-form-wrap {
        width: 100%;
        max-width: 760px;
    }

    .prod-form-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .prod-form-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .btn-back-list {
        background: #fff;
        border: 1px solid #e5e7eb;
        color: #374151;
        padding: 9px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .15s ease;
    }
    .btn-back-list:hover {
        background: #f9fafb;
        color: #111827;
    }

    .prod-card {
        background: #fff;
        border-radius: 14px;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 24px;
    }

    .prod-form-alert {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        border-radius: 10px;
        padding: 14px 16px;
        font-size: 13.5px;
        margin-bottom: 20px;
    }
    .prod-form-alert ul {
        margin: 0;
        padding-left: 18px;
    }

    .prod-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .prod-form-group {
        margin-bottom: 18px;
    }
    .prod-form-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .prod-form-control {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        color: #111827;
        transition: border-color .15s ease, box-shadow .15s ease;
        background: #fff;
    }
    .prod-form-control:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    textarea.prod-form-control {
        resize: vertical;
        min-height: 100px;
    }

    .prod-form-actions {
        display: flex;
        gap: 10px;
        margin-top: 24px;
        justify-content: center;
    }

    .btn-cancel {
        background: #fff;
        border: 1px solid #e5e7eb;
        color: #374151;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-cancel:hover {
        background: #f9fafb;
        color: #111827;
    }

    .btn-save {
        background: #2563eb;
        border: none;
        color: #fff;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background .15s ease;
    }
    .btn-save:hover {
        background: #1d4ed8;
    }
    .prod-topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.btn-search-dark{
    background:#212529;
    color:#fff;
    border:1px solid #212529;
    padding:8px 18px;
    border-radius:0 8px 8px 0;
    font-size:14px;
    transition:.2s;
}

.btn-search-dark:hover{
    background:#000;
    border-color:#000;
    color:#fff;
}

.prod-topbar .form-control{
    width:250px;
    border-radius:8px 0 0 8px;
}

.btn-add-product{
    margin-left:12px;
}

    @media (max-width: 640px) {
        .prod-form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="prod-page-bg">

    <div class="prod-form-wrap">

        <div class="prod-form-topbar">
            <h4 class="prod-form-title">Create New Product</h4>
            <a href="{{ route('products.index') }}" class="btn-back-list">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="prod-card">

            @if ($errors->any())
                <div class="prod-form-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="prod-form-group">
                    <label for="product_name" class="prod-form-label">Product Name</label>
                    <input type="text" name="product_name" id="product_name" class="prod-form-control" placeholder="Enter product name" value="{{ old('product_name') }}" required>
                </div>

                <div class="prod-form-row">
                    <div class="prod-form-group">
                        <label for="category_id" class="prod-form-label">Category</label>
                        <select name="category_id" id="category_id" class="prod-form-control" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->category_id }}" @selected(old('category_id') == $category->category_id)>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="prod-form-group">
                        <label for="supplier_id" class="prod-form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="prod-form-control">
                        <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>
                                {{ $supplier->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="prod-form-row">
                    <div class="prod-form-group">
                        <label for="price" class="prod-form-label">Selling Price (USD)</label>
                        <input type="number" step="0.01" min="0" name="price" id="price" class="prod-form-control" placeholder="0.00" value="{{ old('price') }}" required>
                    </div>

                    <div class="prod-form-group">
                        <label for="cost" class="prod-form-label">Unit Cost (USD)</label>
                        <input type="number" step="0.01" min="0" name="cost" id="cost" class="prod-form-control" placeholder="0.00" value="{{ old('cost') }}" required>
                    </div>
                </div>

                <div class="prod-form-group">
                    <label for="qty" class="prod-form-label">Opening Stock Quantity</label>
                    <input type="number" min="0" name="qty" id="qty" class="prod-form-control" placeholder="0" value="{{ old('qty', 0) }}" required>
                </div>

                <div class="prod-form-row">
                    <div class="prod-form-group">
                        <label for="product_type" class="prod-form-label">Product Type</label>
                        <input type="text" name="product_type" id="product_type" class="prod-form-control" placeholder="e.g. Electronics" value="{{ old('product_type') }}">
                    </div>

                    <div class="prod-form-group">
                        <label for="status" class="prod-form-label">Status</label>
                        <select name="status" id="status" class="prod-form-control">
                            <option value="Active" @selected(old('status') == 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status') == 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="prod-form-group">
                    <label for="image" class="prod-form-label">Product Image</label>
                    <input type="file" name="image" id="image" class="prod-form-control" accept="image/*">
                </div>

                <div class="prod-form-group">
                    <label for="description" class="prod-form-label">Description</label>
                    <textarea name="description" id="description" class="prod-form-control" rows="4" placeholder="Enter description">{{ old('description') }}</textarea>
                </div>

                <div class="prod-form-actions">
                    <a href="{{ route('products.index') }}" class="btn-cancel">
                        Cancel
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Save Product
                    </button>
                </div>
            </form>

        </div>

    </div>

</div>

@endsection