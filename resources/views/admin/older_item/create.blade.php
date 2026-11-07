@extends('layout.admin')

@section('content')

<style>
    .prod-page-bg { background: #f3f4f6; min-height: 100vh; padding: 24px; display: flex; flex-direction: column; align-items: center; }
    .prod-page-title { font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 20px; width: 100%; max-width: 560px; }
    .prod-card { background: #fff; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 24px; width: 100%; max-width: 560px; margin: 0 auto; }
    .form-label { font-size: 13.5px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
    .form-group { margin-bottom: 16px; }
    .form-select, .form-control {
        width: 100%; border: 1px solid #e5e7eb; border-radius: 8px;
        height: 42px; padding: 0 12px; font-size: 14px;
    }
    .btn-save {
        background: #2563eb; color: #fff; border: none; border-radius: 8px;
        padding: 10px 24px; font-size: 14px; font-weight: 600; cursor: pointer;
    }
    .btn-save:hover { background: #1d4ed8; }
    .btn-cancel {
        background: #f3f4f6; color: #374151; border: none; border-radius: 8px;
        padding: 10px 24px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block;
    }
    .btn-cancel:hover { background: #e5e7eb; }
    .text-danger { color: #dc2626; font-size: 12.5px; margin-top: 4px; display: block; }
</style>

<div class="prod-page-bg">
    <h4 class="prod-page-title">Add Order Item</h4>

    <div class="prod-card">
        <form id="orderItemForm" action="{{ route('order_items.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label">Order</label>
                <select name="order_id" class="form-select" required>
                    <option value="">Select Order</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->order_id }}" {{ old('order_id') == $order->order_id ? 'selected' : '' }}>
                            #{{ $order->order_id }} — {{ $order->customer->name ?? 'Guest' }}
                        </option>
                    @endforeach
                </select>
                @error('order_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Product</label>
                <select name="product_id" id="product_id" class="form-select" required>
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->product_id }}"
                            data-price="{{ $product->price }}"
                            data-stock="{{ $product->qty }}"
                            data-name="{{ $product->product_name }}"
                            {{ old('product_id') == $product->product_id ? 'selected' : '' }}>
                            {{ $product->product_name }} (${{ number_format($product->price, 2) }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="{{ old('quantity', 1) }}" required>
                @error('quantity') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Price</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" value="{{ old('price') }}" required>
                @error('price') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn-save">Save</button>
                <a href="{{ route('order_items.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const productSel = document.getElementById('product_id');
    const qtyInput   = document.getElementById('quantity');
    const priceInput = document.getElementById('price');

    // Auto-fill price when a product is selected
    function onProductChange() {
        const opt = productSel.options[productSel.selectedIndex];
        if (opt && opt.value && opt.dataset.price) {
            priceInput.value = opt.dataset.price;
        }
    }
    productSel.addEventListener('change', onProductChange);
    onProductChange();

    // ============ Check stock before Save (over stock -> show alert, do not submit) ============
    document.getElementById('orderItemForm').addEventListener('submit', function (e) {
        const opt = productSel.options[productSel.selectedIndex];
        if (!opt || !opt.value) return;

        const stock = parseInt(opt.dataset.stock);
        const qty   = parseInt(qtyInput.value);

        if (qty > stock) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                text: 'Insufficient stock! Only ' + stock + ' of ' + opt.dataset.name + ' left.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#2563eb'
            });
        }
    });

    // ============ Show error from Controller ============
    @if(session('error'))
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                text: @json(session('error')),
                confirmButtonText: 'OK',
                confirmButtonColor: '#2563eb'
            });
        });
    @endif
</script>

@endsection