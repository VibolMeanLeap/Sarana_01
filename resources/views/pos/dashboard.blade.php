@extends('layout.pos')

@section('content')
@php($exchangeRate = (float)($shopSettings?->exchange_rate ?: 4100))
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div><div class="mini-label mb-2">Sales terminal</div><h1 class="h3 fw-bold mb-1">Create an order</h1><p class="text-muted mb-0">Build a cart, choose the customer and payment method, then save the sale.</p></div>
    @if(strtolower((string) auth()->user()->role?->role_name) === 'admin')
        <a href="{{ route('admin.product') }}" class="btn btn-pos-outline"><i class="bi bi-plus-lg me-1"></i>Manage products</a>
    @else
        <a href="{{ route('cashier.dashboard') }}" class="btn btn-pos-outline"><i class="bi bi-grid me-1"></i>Cashier workspace</a>
    @endif
</div>
<div class="row g-3">
    <div class="col-12 col-xl-8">
        <section class="pos-panel p-3 p-lg-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div><h2 class="h6 fw-bold mb-1">Product catalogue</h2><div class="small text-muted">{{ $products->count() }} active items available · live stock</div></div>
                <input type="search" id="searchInput" class="search-box" style="min-width:230px" placeholder="Search products…" aria-label="Search products">
            </div>
            <div class="d-flex gap-2 overflow-auto pb-3 mb-2">
                <button class="category-btn active" type="button" data-filter="all"><i class="bi bi-grid me-1"></i>All items</button>
                @foreach($categories as $category)
                    <button class="category-btn" type="button" data-filter="{{ $category->category_id }}">{{ $category->category_name }}</button>
                @endforeach
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3" id="productGrid">
                @forelse($products as $product)
                    @php($hasImage = $product->image && file_exists(public_path('images/'.$product->image)))
                    <div class="col product-item" data-category="{{ $product->category_id ?? 'none' }}" data-id="{{ $product->product_id }}" data-title="{{ $product->product_name }}" data-price="{{ (float)$product->price }}" data-stock="{{ (int)$product->qty }}" data-stock-tracked="{{ $product->isStockTracked() ? '1' : '0' }}">
                        <div class="product-card {{ $product->isStockTracked() && (int)$product->qty < 1 ? 'is-out' : '' }}">
                            @if($hasImage)
                                <img class="product-image" src="{{ asset('images/'.$product->image) }}" alt="{{ $product->product_name }}">
                            @else
                                <div class="product-placeholder"><i class="bi bi-cup-hot"></i></div>
                            @endif
                            <div class="d-flex justify-content-between align-items-start gap-1"><div class="product-title">{{ $product->product_name }}</div><span class="stock-pill {{ $product->isStockTracked() && (int)$product->qty < 1 ? 'out' : '' }}">{{ $product->isStockTracked() ? ((int)$product->qty < 1 ? 'Sold out' : (int)$product->qty.' left') : 'Non-stock' }}</span></div>
                            <div class="d-flex justify-content-between align-items-end gap-2 mt-auto"><span class="small text-muted">{{ $product->category->category_name ?? 'General' }}</span><span class="text-end"><span class="product-price d-block">${{ number_format((float)$product->price, 2) }}</span><small class="text-muted">៛{{ number_format((float)$product->price * $exchangeRate, 0) }}</small></span></div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><div class="text-center text-muted py-5"><i class="bi bi-cup-hot fs-1 d-block mb-2"></i>No active products found. Ask an administrator to add products.</div></div>
                @endforelse
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-4">
        <section class="pos-panel p-3 p-lg-4 checkout-panel">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><div class="mini-label">Current ticket</div><h2 class="checkout-title mb-0">Order summary</h2></div><span class="badge rounded-pill text-bg-light" id="cartCount">0 item(s)</span></div>
            <div class="table-responsive cart-scroll"><table class="table table-sm align-middle mb-0"><thead><tr><th style="width:7%"></th><th>Item</th><th class="text-center">Qty</th><th class="text-end">Total</th></tr></thead><tbody id="cartItems"><tr><td colspan="4" class="text-center text-muted py-5">Your cart is empty</td></tr></tbody></table></div>
            <div class="border-top mt-3 pt-3">
                <div class="d-flex justify-content-between align-items-center mb-3"><label for="discountInput" class="text-muted">Discount (%)</label><input type="number" id="discountInput" class="form-control form-control-sm text-end" value="0" min="0" max="100" step="1" style="width:82px"></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><strong id="subTotal">$0.00</strong></div>
                <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top"><span class="fw-bold fs-6">Amount due</span><strong class="fs-4" style="color:var(--pos-green)" id="grandTotal">$0.00</strong></div>
                <div class="d-flex justify-content-between align-items-center small text-muted mt-1"><span>KHR equivalent</span><strong id="grandTotalKhr">៛0</strong></div>
                <div class="small text-muted text-end mt-1">Rate: $1 USD = ៛{{ number_format($exchangeRate, 0) }} KHR</div>
            </div>
            <div class="row g-2 mt-3"><div class="col-5"><button type="button" class="btn btn-pos-outline w-100" id="cancelOrderBtn"><i class="bi bi-trash3 me-1"></i>Clear</button></div><div class="col-7"><button type="button" class="btn btn-pos w-100" id="payBtn" data-bs-toggle="modal" data-bs-target="#checkoutModal" disabled>Checkout · $0.00</button></div></div>
            <div class="info-note mt-3"><i class="bi bi-info-circle me-1"></i>Confirmed sales are saved as orders and line items. Inventory is deducted automatically and recorded in stock history.</div>
        </section>
    </div>
</div>
@endsection
