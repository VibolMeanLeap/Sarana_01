@extends('layout.admin')

@section('content')

<style>
    .oi-page-bg { background: #f3f4f6; min-height: 100vh; padding: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .oi-form-wrap { width: 100%; max-width: 680px; }
    .oi-form-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap; }
    .oi-form-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0; }
    .btn-back-list { background: #fff; border: 1px solid #e5e7eb; color: #374151; padding: 9px 16px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-back-list:hover { background: #f9fafb; color: #111827; }
    .oi-card { background: #fff; border-radius: 14px; border: none; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 24px; }
    .oi-form-alert { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: 14px 16px; font-size: 13.5px; margin-bottom: 20px; }
    .oi-form-alert ul { margin: 0; padding-left: 18px; }
    .oi-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .oi-form-group { margin-bottom: 18px; }
    .oi-form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    .oi-form-control { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #111827; background: #fff; }
    .oi-form-control:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .oi-form-actions { display: flex; gap: 10px; margin-top: 24px; justify-content: center; }
    .btn-cancel { background: #fff; border: 1px solid #e5e7eb; color: #374151; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; }
    .btn-cancel:hover { background: #f9fafb; color: #111827; }
    .btn-save { background: #2563eb; border: none; color: #fff; padding: 10px 18px; border-radius: 8px; font-weight: 500; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; }
    .btn-save:hover { background: #1d4ed8; }
    @media (max-width: 640px) { .oi-form-row { grid-template-columns: 1fr; } }
</style>

<div class="oi-page-bg">
    <div class="oi-form-wrap">

        <div class="oi-form-topbar">
            <h4 class="oi-form-title">Edit Order Item</h4>
            <a href="{{ route('order_items.index') }}" class="btn-back-list">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="oi-card">

            @if ($errors->any())
                <div class="oi-form-alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="orderItemForm" action="{{ route('order_items.update', $orderItem->order_item_id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="oi-form-group">
                    <label for="order_id" class="oi-form-label">Order</label>
                    <select name="order_id" id="order_id" class="oi-form-control">
                        <option value="">-- Select Order --</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->order_id }}" @selected(old('order_id', $orderItem->order_id) == $order->order_id)>#{{ $order->order_id }} - {{ $order->customer->name ?? 'N/A' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="oi-form-group">
                    <label for="product_id" class="oi-form-label">Product</label>
                    <select name="product_id" id="product_id" class="oi-form-control">
                        <option value=""> Select Product </option>
                        @foreach($products as $product)
                            <option value="{{ $product->product_id }}"
                                data-stock="{{ $product->qty }}"
                                data-name="{{ $product->product_name }}"
                                @selected(old('product_id', $orderItem->product_id) == $product->product_id)>{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="oi-form-row">
                    <div class="oi-form-group">
                        <label for="quantity" class="oi-form-label">Quantity</label>
                        <input type="number" min="1" name="quantity" id="quantity" class="oi-form-control" placeholder="1" value="{{ old('quantity', $orderItem->quantity) }}">
                    </div>

                    <div class="oi-form-group">
                        <label for="price" class="oi-form-label">Price</label>
                        <input type="number" step="0.01" min="0" name="price" id="price" class="oi-form-control" placeholder="0.00" value="{{ old('price', $orderItem->price) }}">
                    </div>
                </div>

                <div class="oi-form-actions">
                    <a href="{{ route('order_items.index') }}" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Update Order Item
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const productSel = document.getElementById('product_id');
    const qtyInput   = document.getElementById('quantity');

    // Old data (add back to available stock if same product)
    const oldProductId = {{ (int) $orderItem->product_id }};
    const oldQty       = {{ (int) $orderItem->quantity }};

    function getAvailable(opt) {
        let available = parseInt(opt.dataset.stock);
        if (parseInt(opt.value) === oldProductId) {
            available += oldQty;
        }
        return available;
    }

    // ============ Check stock before Update (over stock -> show alert, do not submit) ============
    document.getElementById('orderItemForm').addEventListener('submit', function (e) {
        const opt = productSel.options[productSel.selectedIndex];
        if (!opt || !opt.value) return;

        const available = getAvailable(opt);
        const qty       = parseInt(qtyInput.value);

        if (qty > available) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Unable to proceed!',
                text: 'Insufficient stock! You can only sell up to ' + available + ' of ' + opt.dataset.name + '.',
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
                title: 'Unable to proceed!',
                text: @json(session('error')),
                confirmButtonText: 'OK',
                confirmButtonColor: '#2563eb'
            });
        });
    @endif
</script>

@endsection