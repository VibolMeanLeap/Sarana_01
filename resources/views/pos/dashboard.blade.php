@extends('layout.pos')

@section('content')
@php($exchangeRate = (float)($shopSettings?->exchange_rate ?: 4100))
<div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
    <div><div class="mini-label mb-2">Sales terminal</div><h1 class="h3 fw-bold mb-1">Create an order</h1><p class="text-muted mb-0">Build a cart, choose the customer and payment method, then save the sale.</p></div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-pos" data-bs-toggle="modal" data-bs-target="#quickProductModal">
            <i class="bi bi-plus-circle me-1"></i>Add product
        </button>
        <button type="button" class="btn btn-pos-outline" id="refreshPosBtn" data-catalog-url="{{ route('pos.catalog.products') }}" title="Refresh products and stock only; keep the current cart">
            <i class="bi bi-arrow-clockwise me-1"></i><span id="refreshPosBtnText">Refresh catalogue</span>
        </button>
        @if(strtolower((string) auth()->user()->role?->role_name) === 'admin')
            <a href="{{ route('admin.product') }}" class="btn btn-pos-outline"><i class="bi bi-box-seam me-1"></i>Manage products</a>
        @else
            <a href="{{ route('cashier.dashboard') }}" class="btn btn-pos-outline"><i class="bi bi-grid me-1"></i>Cashier workspace</a>
        @endif
    </div>
</div>
<div class="row g-3">
    <div class="col-12 col-xl-8">
        <section class="pos-panel p-3 p-lg-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h6 fw-bold mb-1">Product catalogue</h2>
                    <div class="small text-muted"><span id="productCount">{{ $products->count() }}</span> active items available · live stock</div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="catalog-mode-group" role="group" aria-label="Product sales view">
                        <button type="button" class="catalog-mode-btn active" data-sales-view="all"><i class="bi bi-grid me-1"></i>All products</button>
                        <button type="button" class="catalog-mode-btn" data-sales-view="top"><i class="bi bi-trophy me-1"></i>Top sellers <span class="catalog-mode-count">Top 10</span></button>
                    </div>
                    <input type="search" id="searchInput" class="search-box" style="min-width:210px" placeholder="Search products…" aria-label="Search products">
                </div>
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
                    <div class="col product-item" data-category="{{ $product->category_id ?? 'none' }}" data-id="{{ $product->product_id }}" data-title="{{ $product->product_name }}" data-price="{{ (float)$product->price }}" data-stock="{{ (int)$product->qty }}" data-stock-tracked="{{ $product->isStockTracked() ? '1' : '0' }}" data-units-sold="{{ (int) ($product->units_sold ?? 0) }}">
                        <div class="product-card {{ $product->isStockTracked() && (int)$product->qty < 1 ? 'is-out' : '' }}">
                            @if($hasImage)
                                <img class="product-image" src="{{ asset('images/'.$product->image) }}" alt="{{ $product->product_name }}">
                            @else
                                <div class="product-placeholder"><i class="bi bi-cup-hot"></i></div>
                            @endif
                            <div class="d-flex justify-content-between align-items-start gap-1"><div class="product-title">{{ $product->product_name }}</div><span class="stock-pill {{ $product->isStockTracked() && (int)$product->qty < 1 ? 'out' : '' }}">{{ $product->isStockTracked() ? ((int)$product->qty < 1 ? 'Sold out' : (int)$product->qty.' left') : 'Non-stock' }}</span></div>
                            <div class="d-flex justify-content-between align-items-end gap-2 mt-auto">
                                <span class="d-flex flex-column align-items-start gap-1">
                                    <span class="small text-muted">{{ $product->category->category_name ?? 'General' }}</span>
                                    @if((int) ($product->units_sold ?? 0) > 0)
                                        <span class="sales-sold-pill"><i class="bi bi-graph-up-arrow me-1"></i>{{ number_format((int) $product->units_sold) }} sold</span>
                                    @endif
                                </span>
                                <span class="text-end"><span class="product-price d-block">${{ number_format((float)$product->price, 2) }}</span><small class="text-muted">៛{{ number_format((float)$product->price * $exchangeRate, 0) }}</small></span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12" data-pos-empty-state><div class="text-center text-muted py-5"><i class="bi bi-cup-hot fs-1 d-block mb-2"></i>No active products found. Ask an administrator to add products.</div></div>
                @endforelse
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-4">
        <section class="pos-panel p-3 p-lg-4 checkout-panel">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><div class="mini-label">Current ticket</div><h2 class="checkout-title mb-0">Order summary</h2></div><span class="badge rounded-pill text-bg-light" id="cartCount">0 item(s)</span></div>
            <div class="table-responsive cart-scroll"><table class="table table-sm align-middle mb-0"><thead><tr><th style="width:7%"></th><th>Item</th><th class="text-center">Qty</th><th class="text-end">Total</th></tr></thead><tbody id="cartItems"><tr><td colspan="4" class="text-center text-muted py-5">Your cart is empty</td></tr></tbody></table></div>
            <div class="border-top mt-3 pt-3">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="discountInput" class="text-muted mb-0">Discount (%)</label>
                        <input type="number" id="discountInput" class="form-control form-control-sm text-end" value="0" min="0" max="100" step="5" style="width:82px" inputmode="numeric" aria-describedby="discountStepHelp">
                    </div>
                    <div class="d-flex flex-wrap gap-2" role="group" aria-label="Discount presets">
                        <button type="button" class="btn btn-sm btn-pos-outline discount-preset-btn active" data-discount-preset="0">No discount</button>
                        <button type="button" class="btn btn-sm btn-pos-outline discount-preset-btn" data-discount-preset="10">10%</button>
                        <button type="button" class="btn btn-sm btn-pos-outline discount-preset-btn" data-discount-preset="20">20%</button>
                        <button type="button" class="btn btn-sm btn-pos-outline discount-preset-btn" data-discount-preset="30">30%</button>
                    </div>
                    <small id="discountStepHelp" class="text-muted d-block mt-1">Use the arrows to adjust by 5% per step.</small>
                </div>
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

<div class="modal fade" id="quickProductModal" tabindex="-1" aria-labelledby="quickProductTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <form id="quickProductForm" action="{{ route('pos.products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="mini-label">Quick catalogue update</div>
                        <h2 class="modal-title fs-5 fw-bold" id="quickProductTitle">Add product to POS</h2>
                        <p class="small text-muted mb-0 mt-1">The new active product appears in this POS screen immediately.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-danger py-2 small d-none" id="quickProductErrors" role="alert"></div>
                    <div class="mb-3">
                        <label for="quickProductName" class="form-label fw-semibold">Product name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quickProductName" name="product_name" maxlength="255" required placeholder="e.g. Iced Americano">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="quickProductCategory" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="quickProductCategory" name="category_id" required>
                                <option value="">Choose a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->category_id }}">{{ $category->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="quickProductSupplier" class="form-label fw-semibold">Supplier</label>
                            <select class="form-select" id="quickProductSupplier" name="supplier_id">
                                <option value="">No supplier / choose later</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="quickProductPrice" class="form-label fw-semibold">Selling price (USD) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quickProductPrice" name="price" min="0" max="99999999.99" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label for="quickProductCost" class="form-label fw-semibold">Unit cost (USD) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quickProductCost" name="cost" min="0" max="99999999.99" step="0.01" required value="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="quickProductType" class="form-label fw-semibold">Product type <span class="text-danger">*</span></label>
                            <select class="form-select" id="quickProductType" name="product_type" required>
                                <option value="stock">Stock (inventory tracked)</option>
                                <option value="non-stock">Non-stock (no inventory)</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="quickProductQtyWrap">
                            <label for="quickProductQty" class="form-label fw-semibold">Opening stock quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quickProductQty" name="qty" min="0" max="2147483647" step="1" value="0">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="quickProductStatus" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="quickProductStatus" name="status" required>
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="quickProductImage" class="form-label fw-semibold">Product image</label>
                            <input type="file" class="form-control" id="quickProductImage" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                            <small class="form-text text-muted">JPG, PNG, GIF or WEBP · maximum 4 MB</small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="quickProductDescription" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="quickProductDescription" name="description" rows="3" maxlength="2000" placeholder="Enter product description"></textarea>
                    </div>
                    <div class="info-note mt-3"><i class="bi bi-shield-check me-1"></i>These fields match the Product tab. Opening stock is recorded in the inventory ledger. Inactive products are saved but hidden from the POS catalogue.</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-pos-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pos" id="quickProductSaveBtn"><i class="bi bi-plus-circle me-1"></i>Save product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
