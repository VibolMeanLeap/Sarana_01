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

    .prod-current-image {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        margin-bottom: 10px;
        display: block;
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

    @media (max-width: 640px) {
        .prod-form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="prod-page-bg">

    <div class="prod-form-wrap">

        <div class="prod-form-topbar">
            <h4 class="prod-form-title">Edit Product</h4>
            <a href="{{ route('products.index') }}" class="btn-back-list">
                <i class="fa fa-arrow-left"></i> Back
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

            <form action="{{ route('products.update', $product->product_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="prod-form-group">
                    <label for="product_name" class="prod-form-label">Product Name</label>
                    <input type="text" name="product_name" id="product_name" class="prod-form-control" placeholder="Enter product name" value="{{ old('product_name', $product->product_name) }}" required>
                </div>

                <div class="prod-form-row">
                    <div class="prod-form-group">
                        <label for="category_id" class="prod-form-label">Category</label>
                        <select name="category_id" id="category_id" class="prod-form-control" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->category_id }}" @selected(old('category_id', $product->category_id) == $category->category_id)>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="prod-form-group">
                        <label for="supplier_id" class="prod-form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="prod-form-control" data-optional>
                        <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->supplier_id)>
                                {{ $supplier->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="prod-form-row">
                    <div class="prod-form-group">
                        <label for="price" class="prod-form-label">Selling Price (USD)</label>
                        <input type="number" step="0.01" min="0" name="price" id="price" class="prod-form-control" placeholder="0.00" value="{{ old('price', $product->price) }}" required>
                    </div>

                    <div class="prod-form-group">
                        <label for="cost" class="prod-form-label">Unit Cost (USD)</label>
                        <input type="number" step="0.01" min="0" name="cost" id="cost" class="prod-form-control" placeholder="0.00" value="{{ old('cost', $product->cost) }}" required>
                    </div>
                </div>

                <div class="prod-form-group">
                    <label for="qty" class="prod-form-label" id="qty-label">Stock Quantity</label>
                    <input type="number" min="0" name="qty" id="qty" class="prod-form-control" placeholder="0" value="{{ old('qty', $product->qty) }}" required>
                </div>

                <div class="prod-form-row">
                    <div class="prod-form-group">
                        <label for="product_type" class="prod-form-label">Product Type</label>
                        <select name="product_type" id="product_type" class="prod-form-control" required>
                            <option value="stock" @selected(old('product_type', in_array(strtolower((string) $product->product_type), ['non-stock', 'non stock', 'nonstock', 'service', 'digital', 'untracked'], true) ? 'non-stock' : 'stock') === 'stock')>Stock (inventory tracked)</option>
                            <option value="non-stock" @selected(old('product_type', in_array(strtolower((string) $product->product_type), ['non-stock', 'non stock', 'nonstock', 'service', 'digital', 'untracked'], true) ? 'non-stock' : 'stock') === 'non-stock')>Non-stock (no inventory)</option>
                        </select>
                        <small class="text-muted d-block mt-1">Stock products reduce quantity when sold. Non-stock products such as services do not affect inventory.</small>
                        @error('product_type')<small class="text-danger d-block">{{ $message }}</small>@enderror
                    </div>

                    <div class="prod-form-group">
                        <label for="status" class="prod-form-label">Status</label>
                        <select name="status" id="status" class="prod-form-control">
                            <option value="Active" @selected(old('status', $product->status) == 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status', $product->status) == 'Inactive')>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="prod-form-group">
                    <label for="image" class="prod-form-label">Product Image</label>

                    @if($product->image)
                    <img src="{{ asset('images/' . $product->image) }}"alt="{{ $product->product_name }}"class="prod-current-image">
                    @endif

                    <input type="file" name="image" id="image" class="prod-form-control"accept="image/*" data-optional>
                </div>

                <div class="prod-form-group">
                    <label for="description" class="prod-form-label">Description</label>
                    <textarea name="description" id="description" class="prod-form-control" rows="4" placeholder="Enter description" data-optional>{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="prod-form-actions">
                    <a href="{{ route('products.index') }}" class="btn-cancel">
                        Cancel
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Update Product
                    </button>
                </div>
            </form>

        </div>

    </div>

</div>


@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('product_type');
    const quantityInput = document.getElementById('qty');
    if (!typeSelect || !quantityInput) return;
    const syncInventoryType = () => {
        const nonStock = typeSelect.value === 'non-stock';
        if (nonStock) quantityInput.value = '0';
        quantityInput.readOnly = nonStock;
        quantityInput.setAttribute('aria-readonly', nonStock ? 'true' : 'false');
        quantityInput.classList.toggle('bg-light', nonStock);
        const quantityLabel = document.getElementById('qty-label');
        if (quantityLabel) quantityLabel.textContent = nonStock ? 'Inventory Quantity (not used)' : 'Stock Quantity';
    };
    typeSelect.addEventListener('change', syncInventoryType);
    syncInventoryType();
});
</script>
@endsection

@endsection
