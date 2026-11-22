@extends('layout.admin')

@section('page_title', 'POS Price & Discount Audit')
@section('topbar_title', 'POS Price & Discount Audit')

@section('content')
    @php
        $money = fn($value) => '$' . number_format((float) $value, 2);
    @endphp
    <style>
        .pos-audit-page {
            max-width: 1580px;
            margin: 0 auto
        }

        .pos-audit-hero {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            padding: 22px 24px;
            color: #fff;
            background: linear-gradient(115deg, #123f2e 0%, #176b4b 58%, #2d8a60 100%);
            box-shadow: 0 12px 28px rgba(23, 107, 75, .13)
        }

        .pos-audit-hero:after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border: 1px solid rgba(255, 255, 255, .13);
            border-radius: 50%;
            right: -65px;
            top: -120px;
            box-shadow: 0 0 0 28px rgba(255, 255, 255, .035), 0 0 0 58px rgba(255, 255, 255, .025)
        }

        .pos-audit-eyebrow {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .68)
        }

        .pos-audit-card {
            height: 100%;
            background: #fff;
            border: 1px solid #e5eee7;
            border-radius: 15px;
            padding: 15px 16px;
            box-shadow: 0 4px 14px rgba(24, 65, 42, .035)
        }

        .pos-audit-kpi-label {
            font-size: 10px;
            color: #748178;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 850
        }

        .pos-audit-kpi-value {
            margin-top: 7px;
            color: #203c2d;
            font-size: 24px;
            line-height: 1.15;
            font-weight: 900;
            letter-spacing: -.5px;
            font-variant-numeric: tabular-nums
        }

        .pos-audit-kpi-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: #eaf4ed;
            color: #176b4b;
            font-size: 16px
        }

        .pos-audit-panel {
            background: #fff;
            border: 1px solid #e5eee7;
            border-radius: 15px;
            box-shadow: 0 4px 14px rgba(24, 65, 42, .035)
        }

        .pos-audit-filter {
            padding: 14px 16px
        }

        .pos-audit-filter label {
            display: block;
            margin: 0 0 5px;
            color: #738077;
            font-size: 10px;
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: .06em
        }

        .pos-audit-filter .form-control,
        .pos-audit-filter .form-select {
            min-height: 38px;
            border-color: #dfe9e2;
            border-radius: 9px;
            font-size: 12px
        }

        .pos-audit-table thead th {
            white-space: nowrap;
            background: #edf6ef;
            color: #315d43;
            border-bottom: 1px solid #dce9df;
            font-size: 10px;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 12px 11px
        }

        .pos-audit-table tbody td {
            padding: 12px 11px;
            border-color: #edf2ee;
            vertical-align: middle;
            font-size: 12px
        }

        .pos-audit-muted {
            font-size: 10px;
            color: #849087
        }

        .pos-audit-value {
            font-weight: 850;
            color: #91DCAF;
            font-variant-numeric: tabular-nums
        }

        .pos-audit-reason {
            max-width: 260px;
            white-space: normal;
            overflow-wrap: anywhere;
            color: #56645a;
            font-size: 11px
        }

        .pos-audit-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 850;
            white-space: nowrap
        }

        .pos-audit-badge.price {
            background: #eaf2ff;
            color: #2456a6
        }

        .pos-audit-badge.discount {
            background: #fff2df;
            color: #9a5a08
        }

        @media(max-width:767px) {
            .pos-audit-hero {
                padding: 18px
            }

            .pos-audit-kpi-value {
                font-size: 21px
            }

            .pos-audit-filter {
                padding: 12px
            }
        }
    </style>

    <div class="pos-audit-page">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div>
                <div class="small text-success fw-bold text-uppercase mb-1" style="letter-spacing:.1em">POS controls · Staff
                    accountability</div>
                <h1 class="h3 fw-bold mb-1">POS Price &amp; Discount Audit</h1>
                <p class="text-muted small mb-0">Review administrator price overrides and discounts recorded during POS
                    checkout.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('admin.pos-price-discount-audit.csv', request()->query()) }}"
                    class="btn btn-outline-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export filtered CSV
                </a>
                <a href="{{ route('admin.pos-price-discount-audit.pdf', request()->query()) }}" target="_blank" rel="noopener"
                    class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                </a>
            </div>
        </div>

        <section class="pos-audit-hero mb-3">
            <div class="position-relative" style="z-index:1;max-width:900px">
                <div class="pos-audit-eyebrow mb-2">Read-only staff audit trail</div>
                <h2 class="h5 fw-bold mb-2">See who changed a sale price or applied a discount.</h2>
                <p class="small text-white-50 mb-0">Each entry is tied to a receipt, staff account, timestamp and the reason
                    entered at checkout.</p>
            </div>
        </section>

        <form method="GET" action="{{ route('admin.pos-price-discount-audit.index') }}" class="mb-3">
            @include('admin.partials.date-filter-panel', [
                'dateFilter' => $filters['date'],
                'dateFromField' => 'start_date',
                'dateToField' => 'end_date',
                'dateFilterTitle' => 'Audit date range',
                'dateFilterHint' => 'Choose today, this week, this month, all time, or a custom period.',
            ])
            <section class="pos-audit-panel pos-audit-filter">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="fw-bold text-success"><i class="bi bi-funnel me-1"></i>Audit filters</div>
                    <a href="{{ route('admin.pos-price-discount-audit.index') }}"
                        class="small text-success fw-bold text-decoration-none">Clear filters</a>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-4 col-xl-4">
                        <label for="posAuditSearch">Search receipt, staff, product or reason</label>
                        <input id="posAuditSearch" type="search" name="search" value="{{ $filters['search'] }}"
                            class="form-control" placeholder="Order #, event #, staff, product or reason">
                    </div>
                    <div class="col-12 col-md-4 col-xl-3">
                        <label for="posAuditStaff">Staff member</label>
                        <select id="posAuditStaff" name="staff_id" class="form-select">
                            <option value="">All staff</option>
                            @foreach ($staffOptions as $staff)
                                <option value="{{ $staff->user_id }}" @selected((string) $filters['staff_id'] === (string) $staff->user_id)>
                                    {{ $staff->name }}{{ strtolower((string) $staff->status) === 'inactive' ? ' · Inactive' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-xl-2">
                        <label for="posAuditEvent">Adjustment type</label>
                        <select id="posAuditEvent" name="event_type" class="form-select">
                            <option value="">Price overrides &amp; discounts</option>
                            <option value="price_override" @selected($filters['event_type'] === 'price_override')>Price overrides</option>
                            <option value="discount" @selected($filters['event_type'] === 'discount')>Discounts applied</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-xl-2">
                        <label for="posAuditPerPage">Rows per page</label>
                        <select id="posAuditPerPage" name="per_page" class="form-select">
                            @foreach ([15, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }} / page
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-1 d-grid">
                        <button type="submit" class="btn btn-success btn-sm"><i
                                class="bi bi-search me-1"></i>Apply</button>
                    </div>
                </div>
            </section>
        </form>

        <div class="row g-2 mb-3">
            <div class="col-6 col-xl-3">
                <div class="pos-audit-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pos-audit-kpi-label">Audit events</div><span class="pos-audit-kpi-icon"><i
                                class="bi bi-journal-text"></i></span>
                    </div>
                    <div class="pos-audit-kpi-value">{{ number_format((int) ($summary->total_events ?? 0)) }}</div>
                    <div class="pos-audit-muted">Price changes and discounts recorded</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="pos-audit-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pos-audit-kpi-label">Affected receipts</div><span class="pos-audit-kpi-icon"><i
                                class="bi bi-receipt"></i></span>
                    </div>
                    <div class="pos-audit-kpi-value">{{ number_format((int) ($summary->affected_receipts ?? 0)) }}</div>
                    <div class="pos-audit-muted">Unique POS orders with adjustments</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="pos-audit-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pos-audit-kpi-label">Price override events</div><span class="pos-audit-kpi-icon"><i
                                class="bi bi-tags"></i></span>
                    </div>
                    <div class="pos-audit-kpi-value">{{ number_format((int) ($summary->price_override_events ?? 0)) }}
                    </div>
                    <div class="pos-audit-muted">Net difference: {{ $money($summary->price_override_net_change ?? 0) }}
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="pos-audit-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pos-audit-kpi-label">Discounts applied</div><span class="pos-audit-kpi-icon"><i
                                class="bi bi-percent"></i></span>
                    </div>
                    <div class="pos-audit-kpi-value">{{ $money($summary->discount_total ?? 0) }}</div>
                    <div class="pos-audit-muted">{{ number_format((int) ($summary->discount_events ?? 0)) }} discount
                        events</div>
                </div>
            </div>
        </div>

        <section class="pos-audit-panel overflow-hidden">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h2 class="h6 fw-bold mb-1 text-success">Adjustment history</h2>
                    <div class="small text-muted">Showing {{ $events->firstItem() ?? 0 }}–{{ $events->lastItem() ?? 0 }}
                        of {{ number_format($events->total()) }} events</div>
                </div>
                <span class="small text-muted"><i class="bi bi-shield-check me-1"></i>Read only · Admin only</span>
            </div>
            <div class="table-responsive">
                <table class="table pos-audit-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Receipt / event</th>
                            <th>Date &amp; time</th>
                            <th>Staff member</th>
                            <th>Adjustment</th>
                            <th>Recorded details</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            @php
                                $payload = $event->payload;
                                $isPrice = $event->is_price_override;
                                $staffName =
                                    $event->actor_label ?:
                                    ($event->actor_id
                                        ? 'Staff #' . $event->actor_id
                                        : 'Unknown staff');
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('admin.orders.index', ['search' => $event->order_id]) }}"
                                        class="fw-bold text-success text-decoration-none">Receipt
                                        #{{ $event->order_id }}</a>
                                    <div class="pos-audit-muted">Event #{{ $event->history_id }}</div>
                                </td>
                                <td class="text-nowrap">
                                    <div class="fw-semibold">
                                        {{ \Carbon\Carbon::parse($event->created_at)->format('d M Y') }}</div>
                                    <div class="pos-audit-muted">
                                        {{ \Carbon\Carbon::parse($event->created_at)->format('H:i:s') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $staffName }}</div>
                                    <div class="pos-audit-muted">
                                        {{ $event->actor_id ? 'Staff ID #' . $event->actor_id : 'Staff account unavailable' }}
                                    </div>
                                </td>
                                <td>
                                    @if ($isPrice)
                                        <span class="pos-audit-badge price"><i class="bi bi-tag"></i>Price override</span>
                                    @else
                                        <span class="pos-audit-badge discount"><i class="bi bi-percent"></i>Discount
                                            applied</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($isPrice)
                                        <div class="fw-semibold">
                                            {{ $payload['product_name'] ?? 'Product #' . ($payload['product_id'] ?? '—') }}
                                        </div>
                                        <div class="pos-audit-muted">Qty
                                            {{ number_format((int) ($payload['quantity'] ?? 0)) }} · Catalogue
                                            {{ $money($payload['catalogue_unit_price'] ?? 0) }}</div>
                                        <div class="pos-audit-value">Charged
                                            {{ $money($payload['charged_unit_price'] ?? 0) }} / unit</div>
                                        @php $priceDelta = ((float) ($payload['charged_unit_price'] ?? 0) - (float) ($payload['catalogue_unit_price'] ?? 0)) * (int) ($payload['quantity'] ?? 0); @endphp
                                        <div class="pos-audit-muted">Total difference: <span
                                                class="{{ $priceDelta < 0 ? 'text-danger' : 'text-success' }} fw-bold">{{ $money($priceDelta) }}</span>
                                        </div>
                                    @else
                                        <div class="fw-semibold">
                                            {{ number_format((float) ($payload['discount_percent'] ?? 0), 2) }}% discount
                                        </div>
                                        <div class="pos-audit-value">{{ $money($payload['discount_amount'] ?? 0) }}
                                            discount value</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="pos-audit-reason">
                                        {{ $event->reason !== '' ? $event->reason : 'No reason recorded' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="bi bi-clipboard2-data"
                                            style="font-size:30px"></i></div>
                                    <div class="fw-bold text-dark">No price overrides or discounts found</div>
                                    <div class="small text-muted">Try another date range or staff filter. New events appear
                                        after an administrator records an adjusted price or discount in POS.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($events->hasPages())
                <div class="p-3 border-top">{{ $events->links() }}</div>
            @endif
        </section>

        <div class="small text-muted mt-3"><i class="bi bi-info-circle me-1"></i>Price override differences compare the
            catalogue unit price with the charged unit price multiplied by quantity. Discount totals are based on the
            discount amount recorded at checkout. Historical transactions created before POS audit logging was installed are
            not reconstructed.</div>
    </div>
@endsection
