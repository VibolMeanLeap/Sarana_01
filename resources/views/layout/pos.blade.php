<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Point of Sale · {{ $shopSettings?->shop_name ?: 'Coffee Corner' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{--pos-green:#176b4b;--pos-ink:#243029;--pos-muted:#7b887f;--pos-bg:#f4f7f4;--pos-line:#e5ebe6}
        body{background:var(--pos-bg);color:var(--pos-ink);font-family:Inter,'Segoe UI',sans-serif;font-size:14px}.pos-navbar{min-height:70px;background:#fff;border-bottom:1px solid var(--pos-line)}.pos-brand{font-weight:800;letter-spacing:-.5px;color:var(--pos-ink);text-decoration:none}.pos-mark{width:38px;height:38px;border-radius:12px;background:var(--pos-green);color:#fff;display:grid;place-items:center;font-size:18px}.pos-main{padding:26px 28px;max-width:1800px;margin:auto}.pos-panel{background:#fff;border:1px solid var(--pos-line);border-radius:16px;box-shadow:0 4px 16px rgba(29,57,39,.035)}.product-card{position:relative;border:1px solid var(--pos-line);border-radius:13px;padding:12px;background:#fff;min-height:190px;height:100%;cursor:pointer;transition:.18s;display:flex;flex-direction:column}.product-card:hover{border-color:#81b99a;box-shadow:0 8px 18px rgba(24,81,52,.09);transform:translateY(-2px)}.product-card.is-out{opacity:.55;cursor:not-allowed}.product-image{width:100%;height:102px;object-fit:cover;border-radius:9px;background:#f1f5f2}.product-placeholder{height:102px;border-radius:9px;background:#f1f5f2;color:#8ba897;display:grid;place-items:center;font-size:34px}.product-title{font-weight:700;font-size:13px;margin:10px 0 4px;line-height:1.3}.product-price{font-weight:800;color:var(--pos-green);margin-top:auto}.stock-pill{font-size:10px;border-radius:100px;padding:3px 7px;background:#eaf4ed;color:#347453}.stock-pill.out{background:#fdeceb;color:#b33f39}.category-btn{border:1px solid var(--pos-line);border-radius:11px;background:#fff;padding:10px 13px;color:#66736a;font-weight:700;white-space:nowrap}.category-btn.active,.category-btn:hover{background:#e9f4ee;color:var(--pos-green);border-color:#bcdac8}.search-box{border:1px solid var(--pos-line);border-radius:11px;padding:10px 13px;background:#fff}.search-box:focus{outline:2px solid #176b4b22;border-color:#7db79a}.checkout-panel{position:sticky;top:90px}.checkout-title{font-weight:800;font-size:17px}.cart-scroll{max-height:345px;overflow:auto}.qty-btn{width:25px;height:25px;border:1px solid #cfe1d5;border-radius:7px;background:#f6faf7;color:var(--pos-green);font-weight:800}.qty-btn:hover{background:#e5f2e9}.btn-pos{background:var(--pos-green);border-color:var(--pos-green);color:#fff;font-weight:700;border-radius:10px;padding:11px 14px}.btn-pos:hover{background:#105239;color:#fff}.btn-pos-outline{border:1px solid var(--pos-line);background:#fff;color:#46544b;border-radius:10px;font-weight:700;padding:10px 14px}.mini-label{font-size:11px;color:var(--pos-muted);font-weight:700;text-transform:uppercase;letter-spacing:.7px}.info-note{background:#eff7f1;border:1px solid #d8eade;border-radius:10px;color:#426b53;padding:10px 12px;font-size:12px;line-height:1.45}.delivery-fields{border:1px solid #cfe4d5;background:linear-gradient(180deg,#f8fcf9,#fff);border-radius:13px;padding:14px}.delivery-fee-row{display:flex;justify-content:space-between;align-items:center;color:#526358;font-size:13px;margin:8px 0 14px}
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        let cart = [];
        let activeCategory = 'all';
        const productItems = [...document.querySelectorAll('.product-item')];
        const categoryButtons = [...document.querySelectorAll('.category-btn')];
        const searchInput = document.getElementById('searchInput');
        const cartContainer = document.getElementById('cartItems');
        const subtotalEl = document.getElementById('subTotal');
        const discountEl = document.getElementById('discountInput');
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
            const query = (searchInput?.value || '').trim().toLowerCase();
            productItems.forEach(item => {
                const matchesCategory = activeCategory === 'all' || item.dataset.category === activeCategory;
                const matchesSearch = (item.dataset.title || '').toLowerCase().includes(query);
                item.hidden = !(matchesCategory && matchesSearch);
            });
        }

        categoryButtons.forEach(button => button.addEventListener('click', () => {
            categoryButtons.forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            activeCategory = button.dataset.filter || 'all';
            applyFilters();
        }));
        searchInput?.addEventListener('input', applyFilters);

        productItems.forEach(element => element.addEventListener('click', () => {
            const id = String(element.dataset.id);
            const tracksStock = element.dataset.stockTracked !== '0';
            const stock = tracksStock ? Number(element.dataset.stock || 0) : Number.MAX_SAFE_INTEGER;
            if (tracksStock && stock < 1) return;
            const item = cart.find(row => row.id === id);
            if (item) {
                if (tracksStock && item.qty >= stock) return;
                item.qty += 1;
            } else {
                cart.push({ id, title: element.dataset.title || 'Product', price: Number(element.dataset.price || 0), qty: 1, stock, tracksStock });
            }
            renderCart();
        }));

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
                    const unit = document.createElement('small'); unit.className = 'text-muted'; unit.textContent = money(item.price) + ' each';
                    nameCell.append(name, unit);
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
            cartPayload.value = JSON.stringify(cart.map(item => ({ product_id: Number(item.id), quantity: item.qty })));
            discountPayload.value = discountEl.value;
            confirmSaleBtn.disabled = true;
            confirmSaleBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving order…';
        });

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
