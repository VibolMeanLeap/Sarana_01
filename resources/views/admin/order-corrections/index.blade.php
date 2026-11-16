@extends('layout.admin')

@section('page_title', 'Order Correction Audit')
@section('topbar_title', 'Order correction audit')

@section('content')
    @php
        $reasonLabels = [
            'wrong_product' => 'Wrong product',
            'wrong_quantity' => 'Wrong quantity',
            'wrong_customer' => 'Wrong customer',
            'wrong_price_discount' => 'Wrong price / discount',
            'other' => 'Other correction',
        ];
        $money = fn($value) => '$' . number_format((float) $value, 2);
    @endphp

    <style>
        .correction-audit-page {
            max-width: 1550px;
            margin: 0 auto
        }

        .correction-audit-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(118deg, #123f2e 0%, #176b4b 58%, #2d8a60 100%);
            border-radius: 20px;
            padding: 24px 26px;
            color: #fff;
            box-shadow: 0 14px 32px rgba(20, 88, 61, .14)
        }

        .correction-audit-hero:after {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 50%;
            right: -68px;
            top: -130px;
            box-shadow: 0 0 0 28px rgba(255, 255, 255, .035), 0 0 0 58px rgba(255, 255, 255, .025)
        }

        .audit-eyebrow {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .65)
        }

        .audit-panel {
            background: #fbfefc;
            border: 1px solid #dce9df;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(24, 65, 42, .035)
        }

        .audit-filter-panel {
            padding: 15px 17px
        }

        .audit-kpi {
            height: 100%;
            background: #fbfefc;
            border: 1px solid #dce9df;
            border-radius: 15px;
            padding: 16px 17px;
            box-shadow: 0 4px 14px rgba(24, 65, 42, .03)
        }

        .audit-kpi-label {
            font-size: 10px;
            color: #718078;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 850
        }

        .audit-kpi-value {
            font-size: 25px;
            line-height: 1.2;
            font-weight: 900;
            letter-spacing: -.7px;
            color: #203c2d;
            margin-top: 6px;
            font-variant-numeric: tabular-nums
        }

        .audit-kpi-icon {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            background: #e7f3ea;
            color: #176b4b;
            font-size: 16px
        }

        .audit-kpi-note {
            font-size: 10px;
            color: #829087;
            margin-top: 5px;
            line-height: 1.4
        }

        .audit-table thead th {
            white-space: nowrap;
            background: #edf6ef;
            color: #315d43;
            border-bottom: 1px solid #d8e7dc;
            font-size: 10px;
            letter-spacing: .07em;
            text-transform: uppercase;
            padding: 13px 12px
        }

        .audit-table td {
            padding: 13px 12px;
            border-bottom: 1px solid #edf2ee;
            vertical-align: middle
        }

        .audit-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 10px;
            font-weight: 850;
            white-space: nowrap
        }

        .audit-pill.success {
            background: #e7f5eb;
            color: #176b4b
        }

        .audit-pill.warning {
            background: #fff4df;
            color: #936313
        }

        .audit-pill.danger {
            background: #fdecea;
            color: #a33e35
        }

        .audit-pill.neutral {
            background: #eef2ef;
            color: #59675e
        }

        .audit-pill.info {
            background: #eaf2fb;
            color: #315d91
        }

        .audit-person {
            font-size: 12px;
            font-weight: 800;
            color: #30483a
        }

        .audit-sub {
            font-size: 10px;
            color: #839087;
            margin-top: 3px
        }

        .audit-amount {
            font-variant-numeric: tabular-nums;
            font-weight: 850;
            white-space: nowrap
        }

        .audit-detail {
            background: #f5faf6;
            border: 1px solid #dfece2;
            border-radius: 13px;
            padding: 14px;
            margin: 6px 0 2px
        }

        .audit-detail-title {
            font-size: 11px;
            font-weight: 900;
            color: #2b5a3f;
            text-transform: uppercase;
            letter-spacing: .06em
        }

        .audit-snapshot {
            height: 100%;
            background: #fff;
            border: 1px solid #e1ebe4;
            border-radius: 11px;
            padding: 12px
        }

        .audit-snapshot table {
            font-size: 11px
        }

        .audit-snapshot th {
            color: #839087;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .05em
        }

        .audit-snapshot td {
            padding: 6px 3px;
            border-bottom: 1px solid #eff3f0
        }

        .audit-btn {
            border-radius: 9px;
            font-weight: 800;
            font-size: 11px
        }

        .audit-empty {
            padding: 55px 20px;
            text-align: center;
            color: #829087
        }

        .audit-filter-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #718078;
            font-weight: 850;
            margin-bottom: 5px
        }

        @media(max-width:767px) {
            .correction-audit-hero {
                padding: 20px
            }

            .audit-kpi-value {
                font-size: 22px
            }

            .audit-table td {
                padding: 11px 9px
            }
        }
    </style>

    <div class="correction-audit-page">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <div class="small text-success fw-bold text-uppercase mb-1" style="letter-spacing:.1em">Finance · Receipt
                    history</div>
                <h1 class="h3 fw-bold mb-1">Order Correction Audit</h1>
                <p class="text-muted mb-0">Track every corrected POS receipt, the staff member who changed it, and confirmed
                    cash differences.</p>
            </div>
            {{-- <a href="{{ route('pos.corrections.index') }}" class="btn btn-cc"><i class="bi bi-pencil-square me-1"></i>Open POS correction workspace</a> --}}
        </div>

        <div class="correction-audit-hero mb-3">
            <div class="position-relative" style="z-index:1;max-width:850px">
                <div class="audit-eyebrow mb-2">Protected, append-only correction records</div>
                <h2 class="h4 fw-bold mb-2">Know what changed, who changed it, and what cash was confirmed.</h2>
                <p class="mb-0 text-white-50 small">This report reads the existing POS correction history. Original and
                    corrected receipt snapshots remain available for review.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.order-corrections.index') }}" class="mb-3">
            @include('admin.partials.date-filter-panel', [
                'dateFilter' => $filters['date'],
                'dateFilterTitle' => 'Correction date',
                'dateFilterHint' => 'Filter by the date the receipt correction was recorded.',
                'dateFromField' => 'start_date',
                'dateToField' => 'end_date',
            ])
            <section class="audit-panel audit-filter-panel">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="audit-kpi-icon"><i class="bi bi-funnel"></i></span>
                        <div>
                            <div class="fw-bold" style="color:#294c37">Correction filters</div>
                            <div class="small text-muted">Narrow results by staff, cash adjustment, receipt or notes.</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.order-corrections.index') }}"
                        class="small fw-bold text-decoration-none text-success">Clear all filters</a>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5 col-xl-4">
                        <label class="audit-filter-label" for="correctionSearch">Search receipts / staff</label>
                        <input id="correctionSearch" type="search" name="search" value="{{ $filters['search'] }}"
                            class="form-control" placeholder="Order #, correction #, customer, staff or notes">
                    </div>
                    <div class="col-12 col-md-4 col-xl-3">
                        <label class="audit-filter-label" for="correctionStaff">Corrected by</label>
                        <select id="correctionStaff" name="staff_id" class="form-select">
                            <option value="">All staff</option>
                            @foreach ($staffOptions as $staff)
                                <option value="{{ $staff->user_id }}" @selected((string) $filters['staff_id'] === (string) $staff->user_id)>
                                    {{ $staff->name }}{{ strtolower((string) $staff->status) === 'inactive' ? ' · Inactive' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-xl-2">
                        <label class="audit-filter-label" for="adjustmentStatus">Cash adjustment</label>
                        <select id="adjustmentStatus" name="adjustment_status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="not_required" @selected($filters['adjustment_status'] === 'not_required')>No adjustment</option>
                            <option value="pending" @selected($filters['adjustment_status'] === 'pending')>Pending</option>
                            <option value="settled" @selected($filters['adjustment_status'] === 'settled')>Confirmed</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2">
                        <label class="audit-filter-label" for="adjustmentType">Cash action</label>
                        <select id="adjustmentType" name="adjustment_type" class="form-select">
                            <option value="">Collected or refunded</option>
                            <option value="collected" @selected($filters['adjustment_type'] === 'collected')>Cash collected</option>
                            <option value="refunded" @selected($filters['adjustment_type'] === 'refunded')>Cash refunded</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 col-xl-1 d-grid">
                        <button type="submit" class="btn btn-cc audit-btn"><i class="bi bi-search me-1"></i>Apply</button>
                    </div>
                </div>
            </section>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Correction events</div><span class="audit-kpi-icon"><i
                                class="bi bi-journal-text"></i></span>
                    </div>
                    <div class="audit-kpi-value">{{ number_format($summary['correction_events']) }}</div>
                    <div class="audit-kpi-note">Every saved correction record</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Receipts affected</div><span class="audit-kpi-icon"><i
                                class="bi bi-receipt"></i></span>
                    </div>
                    <div class="audit-kpi-value">{{ number_format($summary['affected_receipts']) }}</div>
                    <div class="audit-kpi-note">Unique order numbers in this result</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Corrected receipt value</div><span class="audit-kpi-icon"><i
                                class="bi bi-cash-stack"></i></span>
                    </div>
                    <div class="audit-kpi-value">{{ $money($summary['corrected_value']) }}</div>
                    <div class="audit-kpi-note">Sum of corrected totals across correction events</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Extra cash confirmed</div><span class="audit-kpi-icon"><i
                                class="bi bi-wallet2"></i></span>
                    </div>
                    <div class="audit-kpi-value" style="color:#176b4b">{{ $money($summary['cash_collected']) }}</div>
                    <div class="audit-kpi-note">Staff-confirmed additional collections</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Original receipt value</div><span class="audit-kpi-icon"><i
                                class="bi bi-receipt-cutoff"></i></span>
                    </div>
                    <div class="audit-kpi-value">{{ $money($summary['original_value']) }}</div>
                    <div class="audit-kpi-note">Sum before correction, per event</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Refunds confirmed</div><span class="audit-kpi-icon"><i
                                class="bi bi-arrow-counterclockwise"></i></span>
                    </div>
                    <div class="audit-kpi-value" style="color:#a33e35">{{ $money($summary['cash_refunded']) }}</div>
                    <div class="audit-kpi-note">Staff-confirmed refunds</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Pending collection</div><span class="audit-kpi-icon"><i
                                class="bi bi-hourglass-split"></i></span>
                    </div>
                    <div class="audit-kpi-value">{{ $money($summary['pending_collection']) }}</div>
                    <div class="audit-kpi-note">Additional cash not yet confirmed</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="audit-kpi">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="audit-kpi-label">Pending refund</div><span class="audit-kpi-icon"><i
                                class="bi bi-exclamation-circle"></i></span>
                    </div>
                    <div class="audit-kpi-value">{{ $money($summary['pending_refund']) }}</div>
                    <div class="audit-kpi-note">Refunds not yet confirmed</div>
                </div>
            </div>
        </div>

        <div class="alert alert-info border-0 rounded-3 small mb-3">
            <i class="bi bi-info-circle-fill me-1"></i>
            <strong>Cash reporting note:</strong> “Extra cash confirmed” and “Refunds confirmed” come from the existing
            administrator confirmation fields in the correction workflow. The workflow records who confirmed the action and
            their note; it does not independently verify a cash drawer, bank or payment-terminal transaction.
        </div>

        <section class="audit-panel overflow-hidden">
            <div class="p-3 p-lg-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h2 class="h6 fw-bold mb-1" style="color:#294c37">Corrected receipt history</h2>
                    <div class="small text-muted">Showing
                        {{ $corrections->firstItem() ?? 0 }}–{{ $corrections->lastItem() ?? 0 }} of
                        {{ number_format($corrections->total()) }} correction records</div>
                </div>
                <a href="{{ route('admin.order-corrections.csv', request()->query()) }}"
                    class="btn btn-outline-success audit-btn"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export
                    filtered CSV</a>
            </div>

            <div class="table-responsive">
                <table class="table audit-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Receipt / correction</th>
                            <th>Changed at</th>
                            <th>Corrected by</th>
                            <th>Original cashier</th>
                            <th class="text-end">Before → after</th>
                            <th>Cash adjustment</th>
                            <th>Confirmed by</th>
                            <th class="text-end pe-3">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($corrections as $correction)
                            @php
                                $difference = (float) $correction->difference_amount;
                                $adjustmentStatus = (string) $correction->payment_adjustment_status;
                                $adjustmentType = (string) $correction->payment_adjustment_type;
                                $customerName =
                                    $correction->order?->customer?->name ?:
                                    data_get($correction->before_snapshot, 'customer.name', 'Walk-in customer');
                                $correctedBy =
                                    $correction->createdBy?->name ?:
                                    ($correction->created_by
                                        ? 'Staff #' . $correction->created_by . ' (account unavailable)'
                                        : 'Unknown staff');
                                $reasonLabel =
                                    $reasonLabels[$correction->reason] ??
                                    ucwords(str_replace('_', ' ', (string) $correction->reason));
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">Receipt #{{ $correction->order_id }}</div>
                                    <div class="audit-sub">Correction #{{ $correction->correction_id }} ·
                                        {{ $customerName }}</div>
                                    @if ($correction->order)
                                        <a class="audit-sub text-success text-decoration-none fw-bold"
                                            href="{{ route('admin.orders.receipt', $correction->order_id) }}"
                                            target="_blank" rel="noopener">Open current receipt <i
                                                class="bi bi-box-arrow-up-right"></i></a>
                                    @endif
                                </td>
                                <td>
                                    <div class="audit-person">{{ optional($correction->created_at)->format('d M Y') }}
                                    </div>
                                    <div class="audit-sub">{{ optional($correction->created_at)->format('h:i A') }}</div>
                                </td>
                                <td>
                                    <div class="audit-person">{{ $correctedBy }}</div>
                                    <div class="audit-sub">
                                        {{ $correction->createdBy?->role?->role_name ? ucfirst($correction->createdBy->role->role_name) : 'Correction author' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="audit-person">{{ $correction->order?->cashier?->name ?: 'Not recorded' }}
                                    </div>
                                    <div class="audit-sub">Original sale operator</div>
                                </td>
                                <td class="text-end">
                                    <div class="audit-sub">{{ $money($correction->original_total) }}</div>
                                    <div class="audit-amount text-dark">{{ $money($correction->corrected_total) }}</div>
                                    @if (abs($difference) < 0.01)
                                        <span class="audit-pill neutral">No difference</span>
                                    @elseif($difference > 0)
                                        <span class="audit-pill warning">+{{ $money($difference) }}</span>
                                    @else
                                        <span class="audit-pill danger">−{{ $money(abs($difference)) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($adjustmentStatus === 'not_required')
                                        <span class="audit-pill success">No cash adjustment</span>
                                    @elseif($adjustmentStatus === 'pending' && $difference > 0)
                                        <span class="audit-pill warning">Collect {{ $money($difference) }}</span>
                                    @elseif($adjustmentStatus === 'pending' && $difference < 0)
                                        <span class="audit-pill danger">Refund {{ $money(abs($difference)) }}</span>
                                    @elseif($adjustmentStatus === 'settled' && $adjustmentType === 'collected')
                                        <span class="audit-pill success"><i class="bi bi-check-circle"></i>Collected
                                            {{ $money($difference) }}</span>
                                    @elseif($adjustmentStatus === 'settled' && $adjustmentType === 'refunded')
                                        <span class="audit-pill info"><i class="bi bi-check-circle"></i>Refunded
                                            {{ $money(abs($difference)) }}</span>
                                    @else
                                        <span
                                            class="audit-pill neutral">{{ ucfirst(str_replace('_', ' ', $adjustmentStatus)) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($correction->payment_adjusted_by)
                                        <div class="audit-person">
                                            {{ $correction->adjustedBy?->name ?: 'Staff #' . $correction->payment_adjusted_by }}
                                        </div>
                                        <div class="audit-sub">
                                            {{ optional($correction->payment_adjusted_at)->format('d M Y · h:i A') }}</div>
                                    @else
                                        <span class="audit-sub">Not confirmed</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <a href="#correction-detail-{{ $correction->correction_id }}"
                                        class="btn btn-sm btn-outline-success audit-btn" data-bs-toggle="collapse"
                                        role="button" aria-expanded="false"
                                        aria-controls="correction-detail-{{ $correction->correction_id }}">Compare <i
                                            class="bi bi-chevron-down ms-1"></i></a>
                                </td>
                            </tr>
                            <tr class="border-0">
                                <td colspan="8" class="p-0 border-0">
                                    <div class="collapse" id="correction-detail-{{ $correction->correction_id }}">
                                        <div class="audit-detail mx-3 mb-3">
                                            <div
                                                class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                                                <div>
                                                    <div class="audit-detail-title">Correction
                                                        #{{ $correction->correction_id }} · {{ $reasonLabel }}</div>
                                                    <div class="small text-muted mt-1">{{ $correction->notes }}</div>
                                                </div>
                                                <span class="small text-muted">Recorded
                                                    {{ optional($correction->created_at)->format('d M Y · h:i A') }}</span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-12 col-xl-6">
                                                    <div class="audit-snapshot">
                                                        <div class="audit-detail-title mb-2">Original receipt</div>
                                                        <div class="small text-muted mb-2">
                                                            {{ data_get($correction->before_snapshot, 'customer.name', 'Walk-in customer') }}
                                                            · Discount
                                                            {{ number_format((float) data_get($correction->before_snapshot, 'discount_percent', 0), 2) }}%
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm mb-2">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Product</th>
                                                                        <th class="text-end">Qty</th>
                                                                        <th class="text-end">Unit price</th>
                                                                        <th class="text-end">Line total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach (data_get($correction->before_snapshot, 'items', []) as $line)
                                                                        <tr>
                                                                            <td>{{ $line['product_name'] ?? 'Product' }}
                                                                            </td>
                                                                            <td class="text-end">
                                                                                {{ (int) ($line['quantity'] ?? 0) }}</td>
                                                                            <td class="text-end">
                                                                                {{ $money($line['unit_price'] ?? 0) }}</td>
                                                                            <td class="text-end">
                                                                                {{ $money(((float) ($line['quantity'] ?? 0)) * ((float) ($line['unit_price'] ?? 0))) }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="d-flex justify-content-between small"><span
                                                                class="text-muted">Original
                                                                total</span><strong>{{ $money($correction->original_total) }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-xl-6">
                                                    <div class="audit-snapshot">
                                                        <div class="audit-detail-title mb-2">Corrected receipt</div>
                                                        <div class="small text-muted mb-2">
                                                            {{ data_get($correction->after_snapshot, 'customer.name', 'Walk-in customer') }}
                                                            · Discount
                                                            {{ number_format((float) data_get($correction->after_snapshot, 'discount_percent', 0), 2) }}%
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm mb-2">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Product</th>
                                                                        <th class="text-end">Qty</th>
                                                                        <th class="text-end">Unit price</th>
                                                                        <th class="text-end">Line total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach (data_get($correction->after_snapshot, 'items', []) as $line)
                                                                        <tr>
                                                                            <td>{{ $line['product_name'] ?? 'Product' }}
                                                                            </td>
                                                                            <td class="text-end">
                                                                                {{ (int) ($line['quantity'] ?? 0) }}</td>
                                                                            <td class="text-end">
                                                                                {{ $money($line['unit_price'] ?? 0) }}</td>
                                                                            <td class="text-end">
                                                                                {{ $money(((float) ($line['quantity'] ?? 0)) * ((float) ($line['unit_price'] ?? 0))) }}
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="d-flex justify-content-between small"><span
                                                                class="text-muted">Corrected
                                                                total</span><strong>{{ $money($correction->corrected_total) }}</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex flex-wrap gap-3 align-items-center small">
                                                        <span><strong>Correction by:</strong> {{ $correctedBy }}</span>
                                                        <span><strong>Reason:</strong> {{ $reasonLabel }}</span>
                                                        @if ($correction->payment_adjustment_note)
                                                            <span><strong>Cash note:</strong>
                                                                {{ $correction->payment_adjustment_note }}</span>
                                                        @endif
                                                        @if ($correction->payment_adjusted_by)
                                                            <span><strong>Confirmed by:</strong>
                                                                {{ $correction->adjustedBy?->name ?: 'Staff #' . $correction->payment_adjusted_by }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="audit-empty"><i
                                            class="bi bi-journal-x fs-1 d-block mb-2 text-success"></i>
                                        <div class="fw-bold text-dark">No corrected receipts found</div>
                                        <div class="small">Try All time or clear the staff and cash adjustment filters.
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($corrections->hasPages())
                <div class="p-3 border-top">{{ $corrections->links() }}</div>
            @endif
        </section>
    </div>
@endsection
