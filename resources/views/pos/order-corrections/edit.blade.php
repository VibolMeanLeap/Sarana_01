@extends('layout.pos')

@section('content')
<style>
    .correction-wrap{max-width:1500px;margin:0 auto}
    .correction-hero{background:linear-gradient(125deg,#14583d,#237a55);color:#fff;border-radius:20px;padding:22px;box-shadow:0 12px 30px rgba(20,88,61,.14)}
    .correction-card{background:#fff;border:1px solid #e0eae2;border-radius:16px;box-shadow:0 4px 15px rgba(22,67,43,.04)}
    .correction-soft{background:#edf5ef;border:1px solid #d7e7db;border-radius:15px}
    .correction-soft h2{color:#245b3e}
    .correction-table thead th{background:#edf5ef;color:#315f45;font-size:11px;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;border-bottom:1px solid #d8e7dc}
    .correction-table td{vertical-align:middle}
    .correction-btn{background:#176b4b;border-color:#176b4b;color:#fff;border-radius:10px;font-weight:700}
    .correction-btn:hover{background:#105239;border-color:#105239;color:#fff}
    .correction-pill{display:inline-flex;align-items:center;border-radius:100px;padding:5px 9px;font-size:11px;font-weight:800}
    .correction-pill.good{background:#e5f3e9;color:#176b4b}
    .correction-pill.pending{background:#fff4dc;color:#956311}
    .audit-value{background:#f4f8f5;border:1px solid #e2ece4;border-radius:10px;padding:10px 12px}
    .audit-value small{display:block;color:#6f7d73;font-size:11px}
    .audit-value strong{font-size:18px;color:#245b3e}
    .history-item{border-left:3px solid #74aa88;padding:0 0 18px 16px;margin-left:6px}
    .history-item:last-child{padding-bottom:0}

    /* Compact correction workspace: preserve the current visual language while reducing unused space. */
    .pos-main:has(.correction-wrap){padding:12px 18px 20px!important;max-width:1900px}
    .correction-wrap{max-width:1740px;font-size:13px}
    .correction-wrap .mb-4{margin-bottom:.7rem!important}
    .correction-wrap .mb-3{margin-bottom:.45rem!important}
    .correction-wrap .p-lg-4{padding:1rem!important}
    .correction-wrap .correction-card.p-3{padding:.8rem!important}
    .correction-wrap .correction-soft.p-2{padding:.6rem!important}
    .correction-wrap .correction-hero{padding:12px 16px;border-radius:14px;box-shadow:0 5px 16px rgba(20,88,61,.10)}
    .correction-wrap .correction-hero h2{font-size:1.05rem!important}
    .correction-wrap .correction-hero .display-6{font-size:1.8rem!important;line-height:1.1}
    .correction-wrap .correction-card{border-radius:13px;box-shadow:0 2px 10px rgba(22,67,43,.035)}
    .correction-wrap .correction-soft{border-radius:11px}
    .correction-wrap .form-label{margin-bottom:.25rem}
    .correction-wrap .form-control,.correction-wrap .form-select{min-height:35px;padding:.38rem .65rem;font-size:13px;border-radius:8px}
    .correction-wrap textarea.form-control{min-height:36px;resize:vertical}
    .correction-wrap .table>:not(caption)>*>*{padding:.42rem .5rem}
    .correction-wrap .correction-table thead th{font-size:10px}
    .correction-wrap .correction-table tbody td{padding:.32rem .42rem}
    .correction-wrap .correction-table .form-control,.correction-wrap .correction-table .form-select{min-height:32px;padding:.28rem .45rem}
    .correction-wrap .correction-pill{padding:4px 8px;font-size:10px}
    .correction-wrap .audit-value{padding:7px 9px;border-radius:8px}
    .correction-wrap .audit-value strong{font-size:15px}
    .correction-wrap .history-item{padding-bottom:10px;padding-left:12px}
    .correction-wrap .btn-pos-outline,.correction-wrap .correction-btn{padding:7px 10px;border-radius:8px;font-size:12px}
    .correction-wrap .btn-sm{font-size:11px}
    .correction-wrap .correction-history{border:1px solid #e0eae2;background:#fff;border-radius:13px;box-shadow:0 2px 10px rgba(22,67,43,.035);padding:0 14px}
    .correction-wrap .correction-history>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 2px;font-weight:800;color:#245b3e}
    .correction-wrap .correction-history>summary::-webkit-details-marker{display:none}
    .correction-wrap .correction-history>summary:after{content:'+';font-size:18px;line-height:1;color:#176b4b}
    .correction-wrap .correction-history[open]>summary:after{content:'−'}
    .correction-wrap .correction-history-body{border-top:1px solid #e7eee8;padding:12px 0 4px}
    .correction-wrap .correction-compact-note{padding:7px 10px;margin:8px 0 0;border-radius:8px;font-size:11px;line-height:1.35}
    @media(max-width:767px){.pos-main:has(.correction-wrap){padding:10px!important}.correction-wrap .correction-hero .text-end{text-align:left!important}.correction-wrap .correction-hero .display-6{font-size:1.5rem!important}}
</style>

<div class="correction-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="mini-label mb-0">POS</span>
            <h1 class="h5 fw-bold mb-0">Correct order #{{ $order->order_id }}</h1>
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <a href="{{ $returnTo }}" class="btn btn-sm btn-pos-outline"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
            <a href="{{ route('admin.orders.receipt', $order->order_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-pos-outline"><i class="bi bi-receipt me-1"></i>View current receipt</a>
            <a href="{{ route('pos.corrections.index') }}" class="btn btn-sm btn-pos-outline"><i class="bi bi-search me-1"></i>Correction search</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 rounded-3">
            <div class="fw-bold mb-1">Please review these details:</div>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="correction-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-white-50 text-uppercase fw-bold mb-1">Paid · Completed · Cash POS sale</div>
                <h2 class="h6 fw-bold mb-1">{{ $order->customer?->name ?: 'Walk-in customer' }}</h2>
                <div class="text-white-50 small">{{ $order->customer?->phone ?: 'No customer phone' }} · {{ $order->orderType?->order_name ?: 'POS' }} · Cashier: {{ $order->cashier?->name ?: '—' }}</div>
            </div>
            <div class="text-end">
                <div class="small text-white-50">Current recorded total</div>
                <div class="display-6 fw-bold">${{ number_format((float) $order->final_price, 2) }}</div>
            </div>
        </div>
    </div>

    @if($pendingAdjustment)
        <div class="alert alert-warning border-0 rounded-4 p-3 p-lg-4 mb-4">
            <div class="d-flex gap-3">
                <i class="bi bi-cash-coin fs-3"></i>
                <div class="flex-grow-1">
                    <h2 class="h6 fw-bold mb-1">Cash adjustment needs confirmation</h2>
                    @if((float) $pendingAdjustment->difference_amount > 0)
                        <p class="mb-2">The corrected total is higher. Collect an additional <strong>${{ number_format(abs((float) $pendingAdjustment->difference_amount), 2) }}</strong> before marking this adjustment as settled.</p>
                        @php($requiredAdjustmentType = 'collected')
                    @else
                        <p class="mb-2">The corrected total is lower. Refund <strong>${{ number_format(abs((float) $pendingAdjustment->difference_amount), 2) }}</strong> before marking this adjustment as settled.</p>
                        @php($requiredAdjustmentType = 'refunded')
                    @endif
                    <div class="small mb-3">Correction #{{ $pendingAdjustment->correction_id }} · Original ${{ number_format((float) $pendingAdjustment->original_total, 2) }} → corrected ${{ number_format((float) $pendingAdjustment->corrected_total, 2) }}</div>
                    <form method="POST" action="{{ route('pos.corrections.adjustments.settle', [$order->order_id, $pendingAdjustment->correction_id]) }}" class="row g-2">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <input type="hidden" name="adjustment_type" value="{{ $requiredAdjustmentType }}">
                        <div class="col-12 col-lg-8">
                            <label class="form-label small fw-semibold">Cash collection / refund confirmation note</label>
                            <input type="text" class="form-control" name="payment_adjustment_note" minlength="5" maxlength="1000" required placeholder="e.g. Additional cash received at counter / refund handed to customer">
                        </div>
                        <div class="col-12 col-lg-4 d-flex align-items-end">
                            <button type="submit" class="btn correction-btn w-100" onclick="return confirm('Confirm that the cash difference has actually been {{ $requiredAdjustmentType }}?')">
                                <i class="bi bi-check-circle me-1"></i>Confirm {{ $requiredAdjustmentType }}
                            </button>
                        </div>
                    </form>
                    <div class="small text-muted mt-2">This records the administrator's confirmation only. It does not take payment or send a refund automatically.</div>
                </div>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('pos.corrections.update', $order->order_id) }}" id="orderCorrectionForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_to" value="{{ $returnTo }}">

            <div class="correction-card p-3 mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Correct sale details</h2>
                        <div class="small text-muted">Products, quantities, prices, customer and discount.</div>
                    </div>
                    <span class="correction-pill good"><i class="bi bi-shield-lock me-1"></i>Original snapshot will be saved</span>
                </div>

                <div class="correction-soft p-2 mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-lg-8">
                            <label for="customer_id" class="form-label fw-semibold small">Customer</label>
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">Walk-in / no linked customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->customer_id }}" @selected((string) old('customer_id', $order->customer_id) === (string) $customer->customer_id)>
                                        {{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label for="discount" class="form-label fw-semibold small">Discount (%)</label>
                            <input type="number" name="discount" id="discount" class="form-control" min="0" max="100" step="5" value="{{ old('discount', $order->discount) }}" required>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <h3 class="h6 fw-bold mb-1">Products on corrected receipt</h3>
                        
                    </div>
                    <button type="button" class="btn btn-sm btn-pos-outline" id="addCorrectionItem"><i class="bi bi-plus-circle me-1"></i>Add product line</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm correction-table align-middle mb-2">
                        <thead>
                            <tr>
                                <th style="min-width:220px">Product</th>
                                <th style="width:105px">Quantity</th>
                                <th style="width:135px">Unit price (USD)</th>
                                <th class="text-end" style="width:115px">Line total</th>
                                <th style="width:48px"></th>
                            </tr>
                        </thead>
                        <tbody id="correctionItemsBody">
                            @foreach($initialItems as $index => $item)
                                <tr class="correction-item-row">
                                    <td>
                                        <select class="form-select correction-product" name="items[{{ $index }}][product_id]" required data-selected="{{ $item['product_id'] ?? '' }}">
                                            @foreach($products as $product)
                                                <option value="{{ $product->product_id }}" data-price="{{ (float) $product->price }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->product_id)>
                                                    {{ $product->product_name }}{{ $product->status !== 'Active' ? ' (Inactive · existing order)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control correction-quantity" name="items[{{ $index }}][quantity]" min="1" max="1000" step="1" value="{{ $item['quantity'] ?? 1 }}" required></td>
                                    <td><input type="number" class="form-control correction-price" name="items[{{ $index }}][unit_price]" min="0" max="99999999.99" step="0.01" value="{{ $item['unit_price'] ?? $item['price'] ?? 0 }}" required></td>
                                    <td class="text-end fw-bold correction-line-total">$0.00</td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-correction-item" aria-label="Remove product line"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end">
                    <div class="correction-soft p-2" style="min-width:min(100%,300px)">
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">New subtotal</span><strong id="correctionSubtotal">$0.00</strong></div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Discount</span><strong id="correctionDiscount">$0.00</strong></div>
                        <div class="d-flex justify-content-between border-top pt-2"><span class="fw-bold">Corrected total</span><strong class="fs-5 text-success" id="correctionFinalTotal">$0.00</strong></div>
                        <div class="small text-muted mt-1">Current recorded total: ${{ number_format((float) $order->final_price, 2) }}</div>
                        <div id="correctionDifference" class="small fw-bold mt-2"></div>
                    </div>
                </div>
            </div>

            <div class="correction-card p-3 mb-3">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <h2 class="h6 fw-bold mb-0">Correction reason and confirmation</h2>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="reason" class="form-label small fw-semibold">Reason <span class="text-danger">*</span></label>
                        <select name="reason" id="reason" class="form-select" required>
                            <option value="">Choose a reason</option>
                            <option value="wrong_product" @selected(old('reason') === 'wrong_product')>Wrong product</option>
                            <option value="wrong_quantity" @selected(old('reason') === 'wrong_quantity')>Wrong quantity</option>
                            <option value="wrong_customer" @selected(old('reason') === 'wrong_customer')>Wrong customer</option>
                            <option value="wrong_price_discount" @selected(old('reason') === 'wrong_price_discount')>Wrong price / discount</option>
                            <option value="other" @selected(old('reason') === 'other')>Other correction</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="notes" class="form-label small fw-semibold">Explain what was wrong and what was corrected <span class="text-danger">*</span></label>
                        <textarea name="notes" id="notes" rows="1" minlength="5" maxlength="2000" class="form-control" required placeholder="What was wrong and what was corrected?">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="confirmation" class="form-label small fw-semibold">Type CORRECT to confirm <span class="text-danger">*</span></label>
                        <input type="text" id="confirmation" name="confirmation" class="form-control text-uppercase" autocomplete="off" required pattern="CORRECT" placeholder="CORRECT">
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 pt-2 border-top">
                    <span class="text-muted small" title="Saving updates the receipt and inventory. Previous values remain in correction history. Any payment difference must be confirmed separately."><i class="bi bi-info-circle text-success"></i></span>
                    <div class="d-flex gap-2">
                        <a href="{{ $returnTo }}" class="btn btn-pos-outline">Cancel</a>
                        <button type="submit" class="btn correction-btn" id="saveCorrectionBtn"><i class="bi bi-shield-check me-1"></i>Save order correction</button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <details class="correction-history">
        <summary>
            <span><i class="bi bi-clock-history me-2"></i>Correction history <span class="correction-pill good ms-1">{{ $history->count() }}</span></span>
        </summary>
        <div class="correction-history-body">
        @forelse($history as $correction)
            <div class="history-item">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="fw-bold">Correction #{{ $correction->correction_id }} · {{ str_replace('_', ' ', ucfirst($correction->reason)) }}</div>
                    @if($correction->payment_adjustment_status === 'pending')
                        <span class="correction-pill pending">Cash adjustment pending</span>
                    @elseif($correction->payment_adjustment_status === 'settled')
                        <span class="correction-pill good">Cash adjustment confirmed</span>
                    @else
                        <span class="correction-pill good">No cash difference</span>
                    @endif
                </div>
                <div class="small text-muted mt-1">{{ $correction->created_at?->format('d M Y · h:i A') }} · By {{ $correction->createdBy?->name ?: 'Administrator' }}</div>
                <p class="small mb-1 mt-1">{{ $correction->notes }}</p>
                <div class="row g-2 mb-1">
                    <div class="col-6 col-md-3"><div class="audit-value"><small>Original total</small><strong>${{ number_format((float) $correction->original_total, 2) }}</strong></div></div>
                    <div class="col-6 col-md-3"><div class="audit-value"><small>Corrected total</small><strong>${{ number_format((float) $correction->corrected_total, 2) }}</strong></div></div>
                    <div class="col-6 col-md-3"><div class="audit-value"><small>Difference</small><strong>{{ (float) $correction->difference_amount > 0 ? '+' : '' }}${{ number_format((float) $correction->difference_amount, 2) }}</strong></div></div>
                    <div class="col-6 col-md-3"><div class="audit-value"><small>Adjustment</small><strong class="fs-6">{{ ucfirst(str_replace('_', ' ', $correction->payment_adjustment_status)) }}</strong></div></div>
                </div>
                @if($correction->payment_adjustment_status === 'settled')
                    <div class="small text-muted">Marked {{ $correction->payment_adjustment_type }} by {{ $correction->adjustedBy?->name ?: 'Administrator' }} on {{ $correction->payment_adjusted_at?->format('d M Y · h:i A') }}. Note: {{ $correction->payment_adjustment_note }}</div>
                @endif
                <details class="mt-2">
                    <summary class="small fw-bold text-success">View original and corrected item details</summary>
                    <div class="row g-3 mt-1">
                        <div class="col-12 col-xl-6">
                            <div class="correction-soft p-3 h-100">
                                <div class="fw-bold mb-2">Before correction</div>
                                <div class="small text-muted mb-2">
                                    {{ data_get($correction->before_snapshot, 'customer.name', 'Walk-in customer') }}
                                    · {{ data_get($correction->before_snapshot, 'customer.phone', 'No phone') }}
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-2">
                                        <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th></tr></thead>
                                        <tbody>
                                            @foreach(data_get($correction->before_snapshot, 'items', []) as $line)
                                                <tr>
                                                    <td>{{ $line['product_name'] ?? 'Product' }}</td>
                                                    <td class="text-end">{{ $line['quantity'] ?? 0 }}</td>
                                                    <td class="text-end">${{ number_format((float) ($line['unit_price'] ?? 0), 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="small text-muted">Discount: {{ number_format((float) data_get($correction->before_snapshot, 'discount_percent', 0), 2) }}%</div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-6">
                            <div class="correction-soft p-3 h-100">
                                <div class="fw-bold mb-2">After correction</div>
                                <div class="small text-muted mb-2">
                                    {{ data_get($correction->after_snapshot, 'customer.name', 'Walk-in customer') }}
                                    · {{ data_get($correction->after_snapshot, 'customer.phone', 'No phone') }}
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-2">
                                        <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th></tr></thead>
                                        <tbody>
                                            @foreach(data_get($correction->after_snapshot, 'items', []) as $line)
                                                <tr>
                                                    <td>{{ $line['product_name'] ?? 'Product' }}</td>
                                                    <td class="text-end">{{ $line['quantity'] ?? 0 }}</td>
                                                    <td class="text-end">${{ number_format((float) ($line['unit_price'] ?? 0), 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="small text-muted">Discount: {{ number_format((float) data_get($correction->after_snapshot, 'discount_percent', 0), 2) }}%</div>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        @empty
            <div class="text-center text-muted py-4">
                <i class="bi bi-journal-check fs-2 d-block text-success mb-2"></i>
                No corrections have been made to this order.
            </div>
        @endforelse
        </div>
    </details>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('correctionItemsBody');
    if (!body) return;

    const products = @json($productOptions);
    const currentTotal = {{ json_encode((float) $order->final_price) }};
    const discountInput = document.getElementById('discount');
    const saveButton = document.getElementById('saveCorrectionBtn');
    let rowIndex = body.querySelectorAll('tr').length;

    const money = value => '$' + (Number(value || 0)).toFixed(2);

    function refreshTotals() {
        let subtotal = 0;
        body.querySelectorAll('tr').forEach(row => {
            const quantity = Math.max(0, Number(row.querySelector('.correction-quantity')?.value || 0));
            const price = Math.max(0, Number(row.querySelector('.correction-price')?.value || 0));
            const line = quantity * price;
            subtotal += line;
            const lineCell = row.querySelector('.correction-line-total');
            if (lineCell) lineCell.textContent = money(line);
        });

        const discountPercent = Math.min(100, Math.max(0, Number(discountInput?.value || 0)));
        const discountAmount = subtotal * discountPercent / 100;
        const finalTotal = Math.max(0, subtotal - discountAmount);
        const difference = finalTotal - currentTotal;

        document.getElementById('correctionSubtotal').textContent = money(subtotal);
        document.getElementById('correctionDiscount').textContent = money(discountAmount);
        document.getElementById('correctionFinalTotal').textContent = money(finalTotal);

        const differenceEl = document.getElementById('correctionDifference');
        if (Math.abs(difference) < 0.01) {
            differenceEl.textContent = 'No additional collection or refund expected.';
            differenceEl.className = 'small fw-bold mt-2 text-success';
        } else if (difference > 0) {
            differenceEl.textContent = 'Additional cash to collect: ' + money(difference);
            differenceEl.className = 'small fw-bold mt-2 text-warning';
        } else {
            differenceEl.textContent = 'Cash refund to give: ' + money(Math.abs(difference));
            differenceEl.className = 'small fw-bold mt-2 text-danger';
        }
    }

    function addRow(item = {}) {
        const index = rowIndex++;
        const row = document.createElement('tr');
        row.className = 'correction-item-row';
        row.innerHTML = `
            <td><select class="form-select correction-product" name="items[${index}][product_id]" required></select></td>
            <td><input type="number" class="form-control correction-quantity" name="items[${index}][quantity]" min="1" max="1000" step="1" value="${Number(item.quantity || 1)}" required></td>
            <td><input type="number" class="form-control correction-price" name="items[${index}][unit_price]" min="0" max="99999999.99" step="0.01" value="${Number(item.unit_price ?? item.price ?? 0).toFixed(2)}" required></td>
            <td class="text-end fw-bold correction-line-total">$0.00</td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-correction-item" aria-label="Remove product line"><i class="bi bi-trash"></i></button></td>
        `;

        const select = row.querySelector('.correction-product');
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.dataset.price = product.price;
            option.textContent = product.name + (product.status !== 'Active' ? ' (Inactive · existing order)' : '');
            select.appendChild(option);
        });

        if (item.product_id) {
            select.value = String(item.product_id);
        } else if (products.length) {
            select.value = String(products[0].id);
            row.querySelector('.correction-price').value = Number(products[0].price).toFixed(2);
        }

        select.addEventListener('change', () => {
            const selected = select.selectedOptions[0];
            if (selected) row.querySelector('.correction-price').value = Number(selected.dataset.price || 0).toFixed(2);
            refreshTotals();
        });

        row.querySelectorAll('input').forEach(input => input.addEventListener('input', refreshTotals));
        row.querySelector('.remove-correction-item').addEventListener('click', () => {
            row.remove();
            refreshTotals();
        });

        body.appendChild(row);
        refreshTotals();
    }

    document.getElementById('addCorrectionItem')?.addEventListener('click', () => addRow());
    discountInput?.addEventListener('input', refreshTotals);
    body.querySelectorAll('input').forEach(input => input.addEventListener('input', refreshTotals));
    body.querySelectorAll('.correction-product').forEach(select => select.addEventListener('change', () => {
        const selected = select.selectedOptions[0];
        const row = select.closest('tr');
        if (selected && row) row.querySelector('.correction-price').value = Number(selected.dataset.price || 0).toFixed(2);
        refreshTotals();
    }));
    body.querySelectorAll('.remove-correction-item').forEach(button => button.addEventListener('click', () => {
        button.closest('tr')?.remove();
        refreshTotals();
    }));
    document.getElementById('orderCorrectionForm')?.addEventListener('submit', event => {
        if (!body.querySelector('tr')) {
            event.preventDefault();
            alert('Add at least one product line before saving the correction.');
            return;
        }
        const confirmation = document.getElementById('confirmation');
        confirmation.value = confirmation.value.trim().toUpperCase();
        if (confirmation.value !== 'CORRECT') {
            event.preventDefault();
            alert('Type CORRECT to confirm this update.');
            confirmation.focus();
            return;
        }
        if (!confirm('Save this completed-order correction? The receipt and inventory will be updated, and the previous values will remain in audit history.')) {
            event.preventDefault();
            return;
        }
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving correction…';
        }
    });

    refreshTotals();
});
</script>
@endsection
