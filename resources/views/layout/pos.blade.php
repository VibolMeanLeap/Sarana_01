
    <!doctype html>
    <html lang="{{ app()->getLocale() === 'km' ? 'km' : 'en' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.locale-bootstrap')
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Point of Sale · {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <style>
            :root{--pos-green:#176b4b;--pos-ink:#243029;--pos-muted:#7b887f;--pos-bg:#f4f7f4;--pos-line:#e5ebe6}
            body{background:var(--pos-bg);color:var(--pos-ink);font-family:Inter,'Segoe UI',sans-serif;font-size:14px}.pos-navbar{min-height:70px;background:#fff;border-bottom:1px solid var(--pos-line)}.pos-brand{font-weight:800;letter-spacing:-.5px;color:var(--pos-ink);text-decoration:none}.pos-mark{width:38px;height:38px;border-radius:12px;background:var(--pos-green);color:#fff;display:grid;place-items:center;font-size:18px}.pos-main{padding:26px 28px;max-width:1800px;margin:auto}.pos-panel{background:#fff;border:1px solid var(--pos-line);border-radius:16px;box-shadow:0 4px 16px rgba(29,57,39,.035)}.product-card{position:relative;border:1px solid var(--pos-line);border-radius:13px;padding:12px;background:#fff;min-height:190px;height:100%;cursor:pointer;transition:.18s;display:flex;flex-direction:column}.product-card:hover{border-color:#81b99a;box-shadow:0 8px 18px rgba(24,81,52,.09);transform:translateY(-2px)}.product-card.is-out{opacity:.55;cursor:not-allowed}.product-image{width:100%;height:102px;object-fit:cover;border-radius:9px;background:#f1f5f2}.product-placeholder{height:102px;border-radius:9px;background:#f1f5f2;color:#8ba897;display:grid;place-items:center;font-size:34px}.product-title{font-weight:700;font-size:13px;margin:10px 0 4px;line-height:1.3}.product-price{font-weight:800;color:var(--pos-green);margin-top:auto}.stock-pill{font-size:10px;border-radius:100px;padding:3px 7px;background:#eaf4ed;color:#347453}.stock-pill.out{background:#fdeceb;color:#b33f39}.category-btn{border:1px solid var(--pos-line);border-radius:11px;background:#fff;padding:10px 13px;color:#66736a;font-weight:700;white-space:nowrap}.category-btn.active,.category-btn:hover{background:#e9f4ee;color:var(--pos-green);border-color:#bcdac8}.search-box{border:1px solid var(--pos-line);border-radius:11px;padding:10px 13px;background:#fff}.search-box:focus{outline:2px solid #176b4b22;border-color:#7db79a}.checkout-panel{position:sticky;top:90px}.checkout-title{font-weight:800;font-size:17px}.cart-scroll{max-height:345px;overflow:auto}.qty-btn{width:25px;height:25px;border:1px solid #cfe1d5;border-radius:7px;background:#f6faf7;color:var(--pos-green);font-weight:800}.qty-btn:hover{background:#e5f2e9}.btn-pos{background:var(--pos-green);border-color:var(--pos-green);color:#fff;font-weight:700;border-radius:10px;padding:11px 14px}.btn-pos:hover{background:#105239;color:#fff}.btn-pos-outline{border:1px solid var(--pos-line);background:#fff;color:#46544b;border-radius:10px;font-weight:700;padding:10px 14px}.mini-label{font-size:11px;color:var(--pos-muted);font-weight:700;text-transform:uppercase;letter-spacing:.7px}.info-note{background:#eff7f1;border:1px solid #d8eade;border-radius:10px;color:#426b53;padding:10px 12px;font-size:12px;line-height:1.45}.delivery-fields{border:1px solid #cfe4d5;background:linear-gradient(180deg,#f8fcf9,#fff);border-radius:13px;padding:14px}.delivery-fee-row{display:flex;justify-content:space-between;align-items:center;color:#526358;font-size:13px;margin:8px 0 14px}.cart-price-tools{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.cart-price-tools .badge{font-size:9px}.btn-pos-price-edit{border:0;background:transparent;color:var(--pos-green);padding:0;font-size:11px;font-weight:700;text-decoration:underline;text-underline-offset:2px}.pos-modal-note{background:#eff7f1;border:1px solid #d8eade;border-radius:10px;color:#426b53;padding:10px 12px;font-size:12px}.discount-preset-btn{padding:6px 10px;font-size:12px}.discount-preset-btn.active{background:#e5f2e9;border-color:#8fbea0;color:#14583d;box-shadow:inset 0 0 0 1px #b8d7c2}.catalogue-refreshing{opacity:.65;pointer-events:none}
.catalog-mode-group{display:inline-flex;gap:4px;padding:4px;border:1px solid #dce9df;border-radius:12px;background:#f0f7f2}
.catalog-mode-btn{border:0;border-radius:9px;background:transparent;color:#557363;padding:8px 11px;font-size:12px;font-weight:800;white-space:nowrap;transition:background .15s ease,color .15s ease}
.catalog-mode-btn:hover{color:#145638;background:#e2f0e6}
.catalog-mode-btn.active{background:var(--pos-green);color:#fff;box-shadow:0 3px 8px rgba(23,107,75,.17)}
.catalog-mode-count{margin-left:4px;padding:2px 6px;border-radius:20px;background:rgba(255,255,255,.2);font-size:9px}
.sales-sold-pill{display:inline-flex;align-items:center;border-radius:20px;padding:3px 7px;background:#eaf5ed;color:#247249;font-size:10px;font-weight:800;white-space:nowrap}
@media(max-width:575px){.catalog-mode-group{width:100%}.catalog-mode-btn{flex:1}.search-box{width:100%;min-width:0!important}}
            @media(max-width:991px){.pos-main{padding:18px}.checkout-panel{position:static}}@media(max-width:575px){.pos-main{padding:12px}.pos-navbar{padding:10px 0}}
        </style>
        <link rel="stylesheet" href="{{ asset('assets/css/form-validation.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/password-toggle.css') }}">
    </head>
    <body data-exchange-rate="{{ (float)($shopSettings?->exchange_rate ?: 4100) }}" data-delivery-fee="{{ number_format((float)($deliveryFee ?? 0), 2, '.', '') }}">
    @php
        $isAdmin = strtolower((string) auth()->user()->role?->role_name) === 'admin';
        $homeRoute = $isAdmin ? route('admin.dashboard') : route('cashier.dashboard');
        $ordersRoute = $isAdmin ? route('admin.orders.index') : route('cashier.orders');
    @endphp
    <nav class="pos-navbar d-flex align-items-center">
        <div class="container-fluid px-3 px-lg-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <a href="{{ $homeRoute }}" class="pos-brand d-flex align-items-center gap-2">
                @if($shopSettings?->logo && file_exists(public_path('uploads/'.$shopSettings->logo)))
                    <span class="pos-mark" style="background:#fff"><img src="{{ asset('uploads/'.$shopSettings->logo) }}" alt="{{ $shopSettings->shop_name }} logo" style="width:38px;height:38px;object-fit:contain;border-radius:9px"></span>
                @else
                    <span class="pos-mark"><i class="bi bi-cup-hot-fill"></i></span>
                @endif
                <span>{{ $shopSettings?->shop_name ?: 'Coffee Corner' }} <span class="d-block fw-normal small text-muted" style="font-size:10px;letter-spacing:.5px">POINT OF SALE</span></span>
            </a>
            <div class="d-flex align-items-center flex-wrap gap-2 gap-md-3">
            @include('partials.pos-language-settings')
                <span class="small text-muted d-none d-sm-inline"><i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name ?? 'Staff' }}</span>
                <a href="{{ $homeRoute }}" class="btn btn-sm btn-pos-outline"><i class="bi bi-grid me-1"></i>Workspace</a>
                <a href="{{ $ordersRoute }}" class="btn btn-sm btn-pos-outline"><i class="bi bi-receipt me-1"></i>Orders</a>
                <form method="POST" action="{{ route('process_logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary rounded-3" type="submit">Sign out</button></form>
            </div>
        </div>
    </nav>
    <main class="pos-main">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><strong>Checkout needs attention:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>

    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content border-0 rounded-4 shadow">
            <form id="checkoutForm" method="POST" action="{{ route('pos.checkout') }}">
                @csrf
                <input type="hidden" name="cart" id="cartPayload">
                <input type="hidden" name="discount" id="discountPayload" value="0">
                <div class="modal-header border-0 pb-0"><div><div class="mini-label">Finalize transaction</div><h2 class="modal-title fs-5 fw-bold" id="checkoutTitle">Complete this order</h2><div class="small text-muted mt-1">Capture the sales channel, fulfilment choice and customer details.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body pt-3">
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-1" style="background:#f2f7f3"><span class="text-muted fw-semibold">Amount to collect</span><strong id="paymentPreviewTotal" class="fs-3" style="color:#176b4b">$0.00</strong></div>
                    <div class="text-end small text-muted mb-1">Approximately <strong id="paymentPreviewKhr">៛0</strong></div>
                    <div class="delivery-fee-row border-bottom pb-2" id="deliveryFeePreviewRow" hidden><span><i class="bi bi-truck me-1"></i>Delivery fee</span><strong id="paymentPreviewDeliveryFee">$0.00</strong></div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="checkoutChannel" class="form-label fw-semibold">Order received via</label>
                            <select class="form-select" name="order_channel" id="checkoutChannel" required>
                                <option value="walk_in">Walk-in / In-store</option>
                                <option value="facebook">Facebook page / Messenger</option>
                                <option value="instagram">Instagram</option>
                                <option value="telegram">Telegram message</option>
                                <option value="phone">Phone call</option>
                                <option value="referral">Friend / Referral</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="checkoutCustomer" class="form-label fw-semibold">Existing customer <span class="text-muted fw-normal">(optional)</span></label>
                            <select class="form-select" name="customer_id" id="checkoutCustomer" data-optional>
                                <option value="">New / walk-in customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->customer_id }}" data-name="{{ $customer->name }}" data-phone="{{ $customer->phone }}">{{ $customer->name }} · {{ $customer->phone }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label for="checkoutOrderType" class="form-label fw-semibold">Fulfilment type</label><select class="form-select" name="order_type_id" id="checkoutOrderType" required>@foreach($orderTypes as $type)<option value="{{ $type->order_type_id }}" data-type="{{ strtolower($type->order_name) }}" @selected(strtolower($type->order_name) === 'dine-in')>{{ $type->order_name }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label for="checkoutPayment" class="form-label fw-semibold">Payment method</label><select class="form-select" name="payment_method_id" id="checkoutPayment" required>@foreach($paymentMethods as $method)<option value="{{ $method->payment_method_id }}" data-method="{{ strtolower($method->payment_name) }}">{{ $method->payment_name }}</option>@endforeach</select></div>
                    </div>

                    <section id="deliveryFields" class="delivery-fields mb-3" hidden>
                        <div class="d-flex align-items-start gap-2 mb-3"><span class="rounded-circle d-grid place-items-center" style="width:36px;height:36px;background:#e4f3e9;color:#176b4b;flex:0 0 auto"><i class="bi bi-truck"></i></span><div><div class="fw-bold">Home delivery details</div><div class="small text-muted">Required for orders taken from Messenger, Telegram, phone or referral.</div></div></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold" for="deliveryCustomerName">Recipient / customer name <span class="text-danger">*</span></label><input type="text" class="form-control" name="customer_name" id="deliveryCustomerName" maxlength="255" placeholder="Who should receive the order"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold" for="deliveryCustomerPhone">Contact phone <span class="text-danger">*</span></label><input type="tel" class="form-control" name="customer_phone" id="deliveryCustomerPhone" maxlength="50" placeholder="Phone number for the driver"></div>
                            <div class="col-12"><label class="form-label fw-semibold" for="deliveryAddress">Delivery address <span class="text-danger">*</span></label><textarea class="form-control" name="delivery_address" id="deliveryAddress" rows="2" maxlength="255" placeholder="House number, street, Sangkat, Khan, city or a nearby landmark"></textarea><div class="form-text">Enter a complete address and landmark so the delivery team can locate the customer.</div></div>
                        </div>
                        <div class="delivery-fee-row mb-0"><span>Configured delivery fee</span><strong id="deliveryFeeLabel">${{ number_format((float)($deliveryFee ?? 0), 2) }}</strong></div>
                    </section>

                    <div class="mb-3"><label for="posCustomerNote" class="form-label fw-semibold">Order note <span class="text-muted fw-normal">(optional)</span></label><textarea class="form-control" name="customer_note" id="posCustomerNote" rows="2" maxlength="500" data-optional placeholder="Special instructions, landmark, or customer request"></textarea></div>
                    <div id="codPaymentNotice" class="alert alert-warning small rounded-3 mt-3 mb-2" role="status" hidden><i class="bi bi-cash-coin me-2"></i><strong>Cash on Delivery:</strong> This sale will be saved as <strong>UNPAID</strong>. At the delivery handoff, the cashier or administrator must enter the amount received and confirm that cash was collected before the order becomes <strong>PAID</strong> and appears in revenue.</div>
                    <div class="info-note"><i class="bi bi-shield-check me-1"></i>Stock is deducted only when the order is saved. Delivery orders are sent to the administrator Telegram group. COD remains unpaid until cash is explicitly confirmed at the delivery handoff. Do not record the sale as paid when the order is first created.</div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-pos-outline" data-bs-dismiss="modal">Back to cart</button><button type="submit" class="btn btn-pos" id="confirmSaleBtn"><i class="bi bi-check2-circle me-1"></i>Save order</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="priceOverrideModal" tabindex="-1" aria-labelledby="priceOverrideTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 rounded-4 shadow">
                <form id="priceOverrideForm">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <div class="mini-label">This order only</div>
                            <h2 class="modal-title fs-5 fw-bold" id="priceOverrideTitle">Adjust unit price</h2>
                            <div class="small text-muted" id="priceOverrideProductName"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="d-flex justify-content-between small text-muted mb-2"><span>Catalogue price</span><strong id="priceOverrideBasePrice">$0.00</strong></div>
                        <label for="priceOverrideValue" class="form-label fw-semibold">New unit price (USD)</label>
                        <input type="number" class="form-control form-control-lg" id="priceOverrideValue" min="0" max="99999999.99" step="0.01" required>
                        <div class="small text-danger mt-2 d-none" id="priceOverrideError" role="alert"></div>
                        <div class="pos-modal-note mt-3"><i class="bi bi-info-circle me-1"></i>This adjustment applies only to the current order. It will not change the regular product price.</div>
                    </div>
                    <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                        <button type="button" class="btn btn-link text-muted text-decoration-none" id="resetProductPriceBtn">Use catalogue price</button>
                        <div class="d-flex gap-2"><button type="button" class="btn btn-pos-outline" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-pos">Apply price</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let cart = [];
            let activeCategory = 'all';
            let activeSalesView = 'all';
            const productGrid = document.getElementById('productGrid');
            const categoryButtons = [...document.querySelectorAll('.category-btn')];
            const salesViewButtons = [...document.querySelectorAll('[data-sales-view]')];
            const searchInput = document.getElementById('searchInput');
            const cartContainer = document.getElementById('cartItems');
            const subtotalEl = document.getElementById('subTotal');
            const discountEl = document.getElementById('discountInput');
            const discountPresetButtons = [...document.querySelectorAll('[data-discount-preset]')];
            const totalEl = document.getElementById('grandTotal');
            const payButton = document.getElementById('payBtn');
            const previewTotal = document.getElementById('paymentPreviewTotal');
            const cartPayload = document.getElementById('cartPayload');
            const discountPayload = document.getElementById('discountPayload');
            const checkoutForm = document.getElementById('checkoutForm');
            const confirmSaleBtn = document.getElementById('confirmSaleBtn');
            const orderTypeSelect = document.getElementById('checkoutOrderType');
            const paymentSelect = document.getElementById('checkoutPayment');
            const customerSelect = document.getElementById('checkoutCustomer');
            const deliveryFields = document.getElementById('deliveryFields');
            const deliveryName = document.getElementById('deliveryCustomerName');
            const deliveryPhone = document.getElementById('deliveryCustomerPhone');
            const deliveryAddress = document.getElementById('deliveryAddress');
            const deliveryFeeRow = document.getElementById('deliveryFeePreviewRow');
            const deliveryFeeValue = document.getElementById('paymentPreviewDeliveryFee');
            const deliveryFee = Number(document.body.dataset.deliveryFee || 0);
            const codPaymentNotice = document.getElementById('codPaymentNotice');
            const money = value => '$' + Number(value).toFixed(2);
            const exchangeRate = Number(document.body.dataset.exchangeRate || 4100);
            const moneyKhr = value => '៛' + Math.round(Number(value) * exchangeRate).toLocaleString('en-US');
            const isDeliverySelected = () => (orderTypeSelect?.selectedOptions[0]?.dataset.type || '').trim() === 'delivery';

            function applyFilters() {
                if (!productGrid) return;
                const query = (searchInput?.value || '').trim().toLowerCase();
                const productItems = [...productGrid.querySelectorAll('.product-item')];
                let matchingProducts = productItems.filter(item => {
                    const matchesCategory = activeCategory === 'all' || item.dataset.category === activeCategory;
                    const matchesSearch = (item.dataset.title || '').toLowerCase().includes(query);
                    return matchesCategory && matchesSearch;
                });

                if (activeSalesView === 'top') {
                    matchingProducts = matchingProducts
                        .filter(item => Number(item.dataset.unitsSold || 0) > 0)
                        .sort((a, b) => Number(b.dataset.unitsSold || 0) - Number(a.dataset.unitsSold || 0)
                            || (a.dataset.title || '').localeCompare(b.dataset.title || ''))
                        .slice(0, 10);
                }

                const visibleProducts = new Set(matchingProducts);
                const orderedProducts = activeSalesView === 'top'
                    ? [...productItems].sort((a, b) => Number(b.dataset.unitsSold || 0) - Number(a.dataset.unitsSold || 0)
                        || (a.dataset.title || '').localeCompare(b.dataset.title || ''))
                    : productItems;

                orderedProducts.forEach(item => {
                    item.hidden = !visibleProducts.has(item);
                    productGrid.appendChild(item);
                });

                let emptyState = productGrid.querySelector('[data-pos-filter-empty]');
                if (productItems.length > 0 && matchingProducts.length === 0) {
                    if (!emptyState) {
                        emptyState = document.createElement('div');
                        emptyState.className = 'col-12 text-center text-muted py-5';
                        emptyState.dataset.posFilterEmpty = 'true';
                        emptyState.innerHTML = '<i class="bi bi-search fs-2 d-block mb-2"></i><strong></strong><div class="small mt-1"></div>';
                    }
                    emptyState.querySelector('strong').textContent = activeSalesView === 'top'
                        ? 'No sales-ranked products found'
                        : 'No matching products';
                    emptyState.querySelector('.small').textContent = activeSalesView === 'top'
                        ? 'Top sellers are ranked using paid, non-cancelled orders.'
                        : 'Try another search or category.';
                    productGrid.appendChild(emptyState);
                } else {
                    emptyState?.remove();
                }
            }

            salesViewButtons.forEach(button => button.addEventListener('click', () => {
                activeSalesView = button.dataset.salesView || 'all';
                salesViewButtons.forEach(item => item.classList.toggle('active', item === button));
                applyFilters();
            }));

            categoryButtons.forEach(button => button.addEventListener('click', () => {
                categoryButtons.forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                activeCategory = button.dataset.filter || 'all';
                applyFilters();
            }));
            searchInput?.addEventListener('input', applyFilters);

            productGrid?.addEventListener('click', event => {
                const element = event.target.closest('.product-item');
                if (!element || !productGrid.contains(element)) return;
                const id = String(element.dataset.id);
                const tracksStock = element.dataset.stockTracked !== '0';
                const stock = tracksStock ? Number(element.dataset.stock || 0) : Number.MAX_SAFE_INTEGER;
                if (tracksStock && stock < 1) return;
                const item = cart.find(row => row.id === id);
                if (item) {
                    if (tracksStock && item.qty >= stock) return;
                    item.qty += 1;
                } else {
                    const basePrice = Number(element.dataset.price || 0);
                    cart.push({ id, title: element.dataset.title || 'Product', price: basePrice, basePrice, priceOverridden: false, qty: 1, stock, tracksStock });
                }
                renderCart();
            });

            function makeButton(label, className, onClick, title) {
                const button = document.createElement('button');
                button.type = 'button'; button.className = className; button.textContent = label;
                if (title) button.title = title;
                button.addEventListener('click', onClick);
                return button;
            }

            function syncCustomerDetails() {
                const option = customerSelect?.selectedOptions[0];
                const hasExistingCustomer = Boolean(customerSelect?.value);
                if (hasExistingCustomer) {
                    deliveryName.value = option?.dataset.name || '';
                    deliveryPhone.value = option?.dataset.phone || '';
                    deliveryName.readOnly = true;
                    deliveryPhone.readOnly = true;
                } else {
                    deliveryName.readOnly = false;
                    deliveryPhone.readOnly = false;
                    if (deliveryName.dataset.filledFromCustomer === '1') deliveryName.value = '';
                    if (deliveryPhone.dataset.filledFromCustomer === '1') deliveryPhone.value = '';
                }
                deliveryName.dataset.filledFromCustomer = hasExistingCustomer ? '1' : '0';
                deliveryPhone.dataset.filledFromCustomer = hasExistingCustomer ? '1' : '0';
            }

            function syncCheckoutOptions() {
                const isDelivery = isDeliverySelected();
                deliveryFields.hidden = !isDelivery;
                const selectedMethod = (paymentSelect?.selectedOptions[0]?.dataset.method || '').toLowerCase();
                const isCod = ['cash on delivery', 'cash-on-delivery', 'cod'].includes(selectedMethod);
                if (codPaymentNotice) codPaymentNotice.hidden = !(isDelivery && isCod);
                deliveryName.required = isDelivery && !customerSelect.value;
                deliveryPhone.required = isDelivery && !customerSelect.value;
                deliveryAddress.required = isDelivery;
                deliveryFeeRow.hidden = !isDelivery;
                const codOption = [...paymentSelect.options].find(option => ['cash on delivery', 'cash-on-delivery', 'cod'].includes((option.dataset.method || '').toLowerCase()));
                if (codOption) {
                    codOption.disabled = !isDelivery;
                    if (!isDelivery && codOption.selected) {
                        const cashOption = [...paymentSelect.options].find(option => (option.dataset.method || '').toLowerCase() === 'cash');
                        if (cashOption) paymentSelect.value = cashOption.value;
                    }
                }
                syncCustomerDetails();
                calculateTotals();
            }

            function renderCart() {
                cartContainer.replaceChildren();
                if (!cart.length) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td'); cell.colSpan = 4; cell.className = 'text-center text-muted py-5';
                    cell.textContent = 'Your cart is empty';
                    row.appendChild(cell); cartContainer.appendChild(row);
                } else {
                    cart.forEach(item => {
                        const row = document.createElement('tr');
                        const removeCell = document.createElement('td');
                        removeCell.appendChild(makeButton('×', 'btn btn-sm p-0 text-danger fw-bold', () => { cart = cart.filter(row => row.id !== item.id); renderCart(); }, 'Remove item'));
                        const nameCell = document.createElement('td');
                        const name = document.createElement('div'); name.className = 'fw-semibold'; name.textContent = item.title;
                        const priceTools = document.createElement('div'); priceTools.className = 'cart-price-tools mt-1';
                        const unit = document.createElement('small'); unit.className = 'text-muted'; unit.textContent = money(item.price) + ' each';
                        priceTools.appendChild(unit);
                        if (item.priceOverridden) {
                            const adjusted = document.createElement('span'); adjusted.className = 'badge rounded-pill text-bg-warning'; adjusted.textContent = 'Adjusted';
                            priceTools.appendChild(adjusted);
                        }
                        priceTools.appendChild(makeButton('Edit price', 'btn-pos-price-edit', () => openPriceOverride(item.id), 'Adjust the price for this order only'));
                        nameCell.append(name, priceTools);
                        const qtyCell = document.createElement('td'); qtyCell.className = 'text-center';
                        const qtyWrap = document.createElement('div'); qtyWrap.className = 'd-inline-flex align-items-center gap-1';
                        qtyWrap.appendChild(makeButton('−', 'qty-btn', () => { item.qty -= 1; if (item.qty < 1) cart = cart.filter(row => row.id !== item.id); renderCart(); }));
                        const qty = document.createElement('span'); qty.className = 'fw-bold px-1'; qty.textContent = String(item.qty); qtyWrap.appendChild(qty);
                        qtyWrap.appendChild(makeButton('+', 'qty-btn', () => { if (!item.tracksStock || item.qty < item.stock) item.qty += 1; renderCart(); }));
                        qtyCell.appendChild(qtyWrap);
                        const totalCell = document.createElement('td'); totalCell.className = 'text-end fw-bold'; totalCell.textContent = money(item.price * item.qty);
                        row.append(removeCell, nameCell, qtyCell, totalCell); cartContainer.appendChild(row);
                    });
                }
                calculateTotals();
            }

            function calculateTotals() {
                const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
                const discountRate = Math.min(100, Math.max(0, Number(discountEl.value || 0)));
                discountEl.value = String(discountRate);
                discountPresetButtons.forEach(button => {
                    const selected = Number(button.dataset.discountPreset) === discountRate;
                    button.classList.toggle('active', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
                const fee = isDeliverySelected() ? deliveryFee : 0;
                const total = (subtotal + fee) * (1 - discountRate / 100);
                subtotalEl.textContent = money(subtotal);
                totalEl.textContent = money(total);
                payButton.textContent = 'Checkout · ' + money(total);
                payButton.disabled = cart.length === 0;
                previewTotal.textContent = money(total);
                deliveryFeeValue.textContent = money(fee);
                const totalKhr = document.getElementById('grandTotalKhr');
                const previewKhr = document.getElementById('paymentPreviewKhr');
                if (totalKhr) totalKhr.textContent = moneyKhr(total);
                if (previewKhr) previewKhr.textContent = moneyKhr(total);
                document.getElementById('cartCount').textContent = cart.reduce((sum, item) => sum + item.qty, 0) + ' item(s)';
            }

            discountEl?.addEventListener('input', calculateTotals);
            discountPresetButtons.forEach(button => button.addEventListener('click', () => {
                discountEl.value = button.dataset.discountPreset || '0';
                calculateTotals();
            }));
            orderTypeSelect?.addEventListener('change', syncCheckoutOptions);
            customerSelect?.addEventListener('change', syncCheckoutOptions);
            paymentSelect?.addEventListener('change', syncCheckoutOptions);
            document.getElementById('cancelOrderBtn')?.addEventListener('click', () => { cart = []; renderCart(); });
            payButton?.addEventListener('click', () => { syncCheckoutOptions(); previewTotal.textContent = totalEl.textContent; });

            checkoutForm?.addEventListener('submit', event => {
                if (!cart.length) {
                    event.preventDefault();
                    return;
                }
                if (isDeliverySelected()) {
                    const missing = !customerSelect.value && (!deliveryName.value.trim() || !deliveryPhone.value.trim());
                    if (missing || !deliveryAddress.value.trim()) {
                        event.preventDefault();
                        deliveryFields.hidden = false;
                        alert('Please enter the recipient name, contact phone and complete delivery address.');
                        return;
                    }
                }
                cartPayload.value = JSON.stringify(cart.map(item => ({
                    product_id: Number(item.id),
                    quantity: item.qty,
                    price_override: Boolean(item.priceOverridden),
                    unit_price: item.priceOverridden ? Number(item.price.toFixed(2)) : null
                })));
                discountPayload.value = discountEl.value;
                confirmSaleBtn.disabled = true;
                confirmSaleBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving order…';
            });

            // Per-order unit price adjustment. The catalogue value remains untouched.
            let editingPriceItemId = null;
            const priceOverrideModalEl = document.getElementById('priceOverrideModal');
            const priceOverrideModal = priceOverrideModalEl ? bootstrap.Modal.getOrCreateInstance(priceOverrideModalEl) : null;
            const priceOverrideForm = document.getElementById('priceOverrideForm');
            const priceOverrideValue = document.getElementById('priceOverrideValue');
            const priceOverrideError = document.getElementById('priceOverrideError');
            const showPriceOverrideError = message => {
                if (!priceOverrideError) return;
                priceOverrideError.textContent = message;
                priceOverrideError.classList.toggle('d-none', !message);
            };
            function openPriceOverride(itemId) {
                const item = cart.find(row => row.id === String(itemId));
                if (!item || !priceOverrideModal) return;
                editingPriceItemId = item.id;
                document.getElementById('priceOverrideProductName').textContent = item.title;
                document.getElementById('priceOverrideBasePrice').textContent = money(item.basePrice);
                priceOverrideValue.value = Number(item.price).toFixed(2);
                showPriceOverrideError('');
                priceOverrideModal.show();
                window.setTimeout(() => priceOverrideValue?.focus(), 150);
            }
            priceOverrideForm?.addEventListener('submit', event => {
                event.preventDefault();
                const item = cart.find(row => row.id === editingPriceItemId);
                const nextPrice = Number(priceOverrideValue?.value);
                if (!item || !Number.isFinite(nextPrice) || nextPrice < 0 || nextPrice > 99999999.99) {
                    showPriceOverrideError('Enter a valid price between $0.00 and $99,999,999.99.');
                    return;
                }
                item.price = Math.round((nextPrice + Number.EPSILON) * 100) / 100;
                item.priceOverridden = Math.abs(item.price - item.basePrice) >= 0.005;
                priceOverrideModal?.hide();
                renderCart();
            });
            document.getElementById('resetProductPriceBtn')?.addEventListener('click', () => {
                const item = cart.find(row => row.id === editingPriceItemId);
                if (!item) return;
                item.price = item.basePrice;
                item.priceOverridden = false;
                priceOverrideModal?.hide();
                renderCart();
            });
            priceOverrideValue?.addEventListener('input', () => showPriceOverrideError(''));

            // Quick-create a catalogue product without leaving the POS screen.
            const quickProductForm = document.getElementById('quickProductForm');
            const quickProductModalEl = document.getElementById('quickProductModal');
            const quickProductModal = quickProductModalEl ? bootstrap.Modal.getOrCreateInstance(quickProductModalEl) : null;
            const quickProductSaveBtn = document.getElementById('quickProductSaveBtn');
            const quickProductErrors = document.getElementById('quickProductErrors');
            const quickProductType = document.getElementById('quickProductType');
            const quickProductQty = document.getElementById('quickProductQty');
            const quickProductQtyWrap = document.getElementById('quickProductQtyWrap');
            const showQuickProductErrors = messages => {
                if (!quickProductErrors) return;
                const items = Array.isArray(messages) ? messages : [messages];
                quickProductErrors.replaceChildren();
                items.filter(Boolean).forEach(message => {
                    const line = document.createElement('div');
                    line.textContent = message;
                    quickProductErrors.appendChild(line);
                });
                quickProductErrors.classList.toggle('d-none', items.filter(Boolean).length === 0);
            };
            const syncQuickProductType = () => {
                const stockProduct = quickProductType?.value === 'stock';
                if (quickProductQtyWrap) quickProductQtyWrap.hidden = !stockProduct;
                if (quickProductQty) {
                    quickProductQty.disabled = !stockProduct;
                    quickProductQty.required = stockProduct;
                    if (!stockProduct) quickProductQty.value = '0';
                }
            };
            quickProductType?.addEventListener('change', syncQuickProductType);
            quickProductModalEl?.addEventListener('shown.bs.modal', () => document.getElementById('quickProductName')?.focus());
            quickProductModalEl?.addEventListener('hidden.bs.modal', () => {
                quickProductForm?.reset();
                showQuickProductErrors([]);
                syncQuickProductType();
            });

            function buildProductCard(product) {
                const column = document.createElement('div');
                column.className = 'col product-item';
                column.dataset.category = String(product.category_id || 'none');
                column.dataset.id = String(product.id);
                column.dataset.title = product.name || 'Product';
                column.dataset.price = String(product.price || 0);
                column.dataset.stock = String(product.qty || 0);
                column.dataset.stockTracked = product.stock_tracked ? '1' : '0';
                column.dataset.unitsSold = String(product.units_sold || 0);

                const isOut = Boolean(product.stock_tracked) && Number(product.qty || 0) < 1;
                const card = document.createElement('div');
                card.className = 'product-card' + (isOut ? ' is-out' : '');

                if (product.image_url) {
                    const image = document.createElement('img');
                    image.className = 'product-image';
                    image.src = product.image_url;
                    image.alt = product.name || 'Product image';
                    image.loading = 'lazy';
                    image.addEventListener('error', () => {
                        const placeholder = document.createElement('div');
                        placeholder.className = 'product-placeholder';
                        const icon = document.createElement('i');
                        icon.className = 'bi bi-cup-hot';
                        placeholder.appendChild(icon);
                        image.replaceWith(placeholder);
                    }, { once: true });
                    card.appendChild(image);
                } else {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'product-placeholder';
                    const icon = document.createElement('i'); icon.className = 'bi bi-cup-hot';
                    placeholder.appendChild(icon);
                    card.appendChild(placeholder);
                }

                const details = document.createElement('div');
                details.className = 'd-flex justify-content-between align-items-start gap-1';
                const title = document.createElement('div');
                title.className = 'product-title';
                title.textContent = product.name || 'Product';
                const stock = document.createElement('span');
                stock.className = 'stock-pill' + (isOut ? ' out' : '');
                stock.textContent = product.stock_tracked ? (isOut ? 'Sold out' : Number(product.qty || 0) + ' left') : 'Non-stock';
                details.append(title, stock);

                const footer = document.createElement('div');
                footer.className = 'd-flex justify-content-between align-items-end gap-2 mt-auto';
                const categoryWrap = document.createElement('span');
                categoryWrap.className = 'd-flex flex-column align-items-start gap-1';
                const category = document.createElement('span');
                category.className = 'small text-muted';
                category.textContent = product.category_name || 'General';
                categoryWrap.appendChild(category);
                const unitsSold = Number(product.units_sold || 0);
                if (unitsSold > 0) {
                    const soldBadge = document.createElement('span');
                    soldBadge.className = 'sales-sold-pill';
                    soldBadge.innerHTML = '<i class="bi bi-graph-up-arrow me-1"></i>' + unitsSold.toLocaleString() + ' sold';
                    categoryWrap.appendChild(soldBadge);
                }
                const priceWrap = document.createElement('span');
                priceWrap.className = 'text-end';
                const price = document.createElement('span');
                price.className = 'product-price d-block';
                price.textContent = money(product.price || 0);
                const priceKhr = document.createElement('small');
                priceKhr.className = 'text-muted';
                priceKhr.textContent = moneyKhr(product.price || 0);
                priceWrap.append(price, priceKhr);
                footer.append(categoryWrap, priceWrap);
                card.append(details, footer);
                column.appendChild(card);
                return column;
            }

            function renderProductCatalog(products) {
                if (!productGrid) return;
                const fragment = document.createDocumentFragment();
                if (!products.length) {
                    const empty = document.createElement('div');
                    empty.className = 'col-12';
                    empty.dataset.posEmptyState = 'true';
                    const message = document.createElement('div');
                    message.className = 'text-center text-muted py-5';
                    message.textContent = 'No active products found. Ask an administrator to add products.';
                    empty.appendChild(message);
                    fragment.appendChild(empty);
                } else {
                    products.forEach(product => fragment.appendChild(buildProductCard(product)));
                }
                productGrid.replaceChildren(fragment);

                // Update stock limits for products already in the cart without changing
                // their selected quantities, prices, or cart rows.
                const productById = new Map(products.map(product => [String(product.id), product]));
                cart.forEach(item => {
                    const latest = productById.get(String(item.id));
                    if (latest) {
                        item.title = latest.name || item.title;
                        item.stock = latest.stock_tracked ? Number(latest.qty || 0) : Number.MAX_SAFE_INTEGER;
                        item.tracksStock = Boolean(latest.stock_tracked);
                        item.basePrice = Number(latest.price || 0);
                        // Keep any deliberate per-order override, but synchronize normal
                        // cart prices to the newly refreshed catalogue price.
                        if (!item.priceOverridden) item.price = item.basePrice;
                    }
                });
                if (cart.length) renderCart();

                const count = document.getElementById('productCount');
                if (count) count.textContent = String(products.length);
                applyFilters();
            }

            function showCatalogNotice(message, type = 'success') {
                const notice = document.createElement('div');
                notice.className = `alert alert-${type} alert-dismissible fade show`;
                notice.setAttribute('role', 'status');
                notice.textContent = message;
                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'btn-close';
                close.dataset.bsDismiss = 'alert';
                close.setAttribute('aria-label', 'Close');
                notice.appendChild(close);
                document.querySelector('.pos-main')?.prepend(notice);
            }

            async function refreshProductCatalog({ silent = false } = {}) {
                const refreshButton = document.getElementById('refreshPosBtn');
                const refreshText = document.getElementById('refreshPosBtnText');
                const originalText = refreshText?.textContent || 'Refresh catalogue';
                const originalIcon = refreshButton?.querySelector('i')?.className || 'bi bi-arrow-clockwise me-1';
                const catalogUrl = refreshButton?.dataset.catalogUrl;
                if (!catalogUrl) {
                    if (!silent) showCatalogNotice('Catalogue refresh URL is missing. Clear Laravel route cache and try again.', 'danger');
                    return false;
                }

                if (refreshButton) refreshButton.disabled = true;
                if (refreshText) refreshText.textContent = 'Refreshing…';
                productGrid?.classList.add('catalogue-refreshing');
                try {
                    const response = await fetch(catalogUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok || !payload.success || !Array.isArray(payload.products)) {
                        throw new Error(payload.message || `Unable to refresh catalogue (HTTP ${response.status}).`);
                    }
                    renderProductCatalog(payload.products);
                    if (!silent) showCatalogNotice(`Catalogue updated: ${payload.products.length} active product(s). Your cart was kept.`, 'success');
                    return true;
                } catch (error) {
                    if (!silent) showCatalogNotice(error.message || 'Could not refresh the product catalogue. Your cart was not changed.', 'danger');
                    return false;
                } finally {
                    productGrid?.classList.remove('catalogue-refreshing');
                    if (refreshButton) refreshButton.disabled = false;
                    if (refreshText) refreshText.textContent = originalText;
                    const icon = refreshButton?.querySelector('i');
                    if (icon) icon.className = originalIcon;
                }
            }

            quickProductForm?.addEventListener('submit', async event => {
                event.preventDefault();
                if (!quickProductForm.reportValidity()) return;
                showQuickProductErrors([]);
                const originalButton = quickProductSaveBtn?.innerHTML;
                if (quickProductSaveBtn) {
                    quickProductSaveBtn.disabled = true;
                    quickProductSaveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving…';
                }
                try {
                    const response = await fetch(quickProductForm.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(quickProductForm)
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const messages = payload.errors ? Object.values(payload.errors).flat() : [payload.message || `Unable to add product (HTTP ${response.status}).`];
                        showQuickProductErrors(messages);
                        return;
                    }
                    quickProductModal?.hide();
                    const refreshed = await refreshProductCatalog({ silent: true });
                    const message = refreshed
                        ? payload.message || 'Product saved and catalogue refreshed. Your cart was kept.'
                        : 'Product saved, but the catalogue could not refresh. Use Refresh catalogue to reload products; your cart was kept.';
                    showCatalogNotice(message, refreshed ? 'success' : 'warning');
                } catch (error) {
                    showQuickProductErrors(['A network error occurred. Please check your connection and try again.']);
                } finally {
                    if (quickProductSaveBtn) {
                        quickProductSaveBtn.disabled = false;
                        quickProductSaveBtn.innerHTML = originalButton || '<i class="bi bi-plus-circle me-1"></i>Save product';
                    }
                }
            });

            document.getElementById('refreshPosBtn')?.addEventListener('click', () => {
                refreshProductCatalog();
            });

            syncQuickProductType();
            syncCheckoutOptions();
            renderCart();
            applyFilters();
        });
    </script>
    <script>window.CoffeeCornerValidationErrors = @json($errors->toArray());</script>
    <script src="{{ asset('assets/js/form-validation.js') }}"></script>
    <script src="{{ asset('assets/js/password-toggle.js') }}"></script>
    </body>
    </html>
