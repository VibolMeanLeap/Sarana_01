<!doctype html>
<html lang="en">
@php
    $storeName = trim((string) ($shopSettings?->shop_name ?? '')) ?: config('app.name', 'Store');
    $storeLogo = filled($shopSettings?->logo)
        ? asset('uploads/' . ltrim((string) $shopSettings->logo, '/'))
        : null;
    $storeAddress = trim((string) ($shopSettings?->address ?? ''));
    $storePhone = trim((string) ($shopSettings?->tel ?? ''));
    $storeExchangeRate = is_numeric($shopSettings?->exchange_rate) ? (float) $shopSettings->exchange_rate : null;
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ $storeName }}</title>
    <style>
        :root {
            color-scheme: light;
            --brand: #176b4b;
            --brand-dark: #104c36;
            --ink: #223129;
            --muted: #68776e;
            --line: #dfe8e1;
            --soft: #f4f8f5;
            --paper: #ffffff;
            --danger: #a63737;
            --warning: #8b5b11;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2ef; color: var(--ink); font: 13px/1.5 Arial, Helvetica, sans-serif; }
        .toolbar { max-width: 1120px; margin: 18px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar a, .toolbar button { border-radius: 8px; padding: 9px 14px; font-weight: 700; font-size: 12px; text-decoration: none; cursor: pointer; }
        .toolbar a { border: 1px solid var(--line); color: #42564a; background: #fff; }
        .toolbar button { border: 1px solid var(--brand); color: #fff; background: var(--brand); }
        .report-shell { max-width: 1120px; margin: 16px auto 30px; padding: 30px; background: var(--paper); border: 1px solid #e1e9e3; border-radius: 14px; box-shadow: 0 8px 26px rgba(27,55,38,.06); }
        .report-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; border-bottom: 2px solid var(--brand); padding-bottom: 18px; margin-bottom: 18px; }
        .store-identity { display: flex; align-items: flex-start; gap: 14px; min-width: 0; }
        .store-logo { width: 62px; height: 62px; object-fit: contain; border: 1px solid var(--line); border-radius: 10px; flex: 0 0 auto; }
        .brand { color: var(--brand); font-size: 12px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .store-contact { color: var(--muted); font-size: 10px; margin-top: 3px; }
        h1 { margin: 7px 0 3px; font-size: 25px; line-height: 1.2; letter-spacing: -.02em; }
        .subtitle { color: var(--muted); font-size: 11px; }
        .report-meta { text-align: right; color: var(--muted); font-size: 10px; white-space: nowrap; }
        .filter-box { border: 1px solid var(--line); background: #fbfdfb; border-radius: 9px; padding: 11px 13px; margin-bottom: 16px; }
        .filter-label { color: var(--brand); text-transform: uppercase; letter-spacing: .08em; font-size: 9px; font-weight: 800; margin-bottom: 3px; }
        .filter-text { color: #42564a; font-size: 11px; overflow-wrap: anywhere; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 9px; margin: 0 0 20px; }
        .summary-card { padding: 11px 12px; border: 1px solid var(--line); border-radius: 9px; background: var(--soft); min-width: 0; }
        .summary-card span { display: block; color: var(--muted); font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .summary-card strong { display: block; margin-top: 4px; color: var(--ink); font-size: 16px; overflow-wrap: anywhere; }
        .section-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 20px 0 10px; padding-bottom: 7px; border-bottom: 1px solid var(--line); }
        .section-title h2 { margin: 0; font-size: 14px; color: var(--brand-dark); }
        .section-title span { color: var(--muted); font-size: 10px; }
        .audit-card { border: 1px solid #dce6df; border-radius: 10px; margin: 0 0 14px; overflow: hidden; background: #fff; break-inside: avoid; page-break-inside: avoid; }
        .audit-card-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 11px 14px; background: #f4f8f5; border-bottom: 1px solid var(--line); }
        .record-id { font-size: 13px; font-weight: 800; color: var(--brand-dark); }
        .record-sub { color: var(--muted); font-size: 10px; margin-top: 2px; }
        .status-pill { display: inline-block; border: 1px solid #d8e6dc; background: #edf6f0; color: #276447; padding: 4px 8px; border-radius: 20px; font-size: 9px; font-weight: 800; white-space: nowrap; text-transform: uppercase; }
        .status-pill.pending { color: var(--warning); border-color: #f0dfbe; background: #fff8e9; }
        .status-pill.refund { color: var(--danger); border-color: #f1d7d7; background: #fff3f3; }
        .card-body { padding: 13px 14px 14px; }
        .meta-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px 14px; margin-bottom: 12px; }
        .meta-item { min-width: 0; }
        .meta-item span { display: block; font-size: 9px; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
        .meta-item strong { display: block; color: var(--ink); font-size: 11px; margin-top: 2px; overflow-wrap: anywhere; }
        .reason-box { background: #fbfcfb; border-left: 3px solid #92b7a0; padding: 8px 10px; margin: 9px 0 13px; font-size: 10px; color: #425349; overflow-wrap: anywhere; }
        .reason-box strong { color: var(--brand-dark); }
        .comparison-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .comparison-panel { border: 1px solid var(--line); border-radius: 8px; overflow: hidden; min-width: 0; }
        .comparison-head { padding: 7px 9px; font-size: 10px; font-weight: 800; background: #f5f8f6; border-bottom: 1px solid var(--line); }
        .comparison-panel.after .comparison-head { color: var(--brand); background: #eef7f1; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .items-table th { color: var(--muted); background: #fcfdfc; font-size: 8px; text-transform: uppercase; letter-spacing: .03em; text-align: left; padding: 6px 7px; border-bottom: 1px solid #e9efea; }
        .items-table td { padding: 6px 7px; border-bottom: 1px solid #eef2ef; vertical-align: top; overflow-wrap: anywhere; }
        .items-table tr:last-child td { border-bottom: 0; }
        .items-table .num { text-align: right; white-space: nowrap; }
        .items-empty { padding: 12px; color: var(--muted); font-size: 10px; text-align: center; }
        .total-strip { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 8px 9px; border-top: 1px solid var(--line); background: #fbfdfb; font-size: 10px; }
        .total-strip strong { font-size: 13px; }
        .financial-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 9px; margin-top: 12px; }
        .financial-card { border: 1px solid var(--line); border-radius: 8px; padding: 9px 10px; min-width: 0; }
        .financial-card span { display: block; color: var(--muted); font-size: 9px; }
        .financial-card strong { display: block; margin-top: 3px; font-size: 13px; overflow-wrap: anywhere; }
        .financial-card.emphasis { background: #f0f7f2; border-color: #cee1d3; }
        .financial-card.negative strong { color: var(--danger); }
        .adjustment-row { display: flex; flex-wrap: wrap; gap: 8px 18px; padding-top: 10px; margin-top: 10px; border-top: 1px dashed #dfe8e1; color: var(--muted); font-size: 9px; }
        .adjustment-row strong { color: var(--ink); }
        .event-card .card-body { padding: 13px 15px; }
        .event-type { display: inline-block; border-radius: 20px; padding: 4px 9px; color: #235f40; background: #eaf5ed; border: 1px solid #d6e8dc; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; }
        .event-type.price { color: #705119; background: #fff7e8; border-color: #f0dfbb; }
        .product-title { font-size: 14px; font-weight: 800; color: var(--ink); margin: 0 0 3px; }
        .product-sub { color: var(--muted); font-size: 10px; }
        .event-detail-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 9px; margin-top: 13px; }
        .event-detail { border: 1px solid var(--line); background: #fbfdfb; border-radius: 8px; padding: 9px 10px; min-width: 0; }
        .event-detail span { display: block; font-size: 9px; color: var(--muted); }
        .event-detail strong { display: block; font-size: 13px; margin-top: 3px; overflow-wrap: anywhere; }
        .event-detail.highlight { border-color: #cfe2d4; background: #f0f7f2; }
        .event-detail.highlight strong { color: var(--brand-dark); }
        .event-reason { margin-top: 11px; padding: 9px 10px; border-radius: 7px; background: #fafcfb; color: #526158; font-size: 10px; overflow-wrap: anywhere; }
        .event-reason strong { color: var(--brand-dark); }
        .empty { border: 1px dashed #cfdcd2; border-radius: 10px; padding: 28px; text-align: center; color: var(--muted); font-size: 12px; }
        .report-foot { display: flex; justify-content: space-between; gap: 10px; margin-top: 19px; padding-top: 10px; border-top: 1px solid var(--line); color: #849188; font-size: 9px; }
        @media (max-width: 760px) {
            .report-shell { margin: 10px; padding: 16px; }
            .report-head { display: block; }
            .report-meta { text-align: left; margin-top: 12px; }
            .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .meta-grid, .event-detail-grid, .financial-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .comparison-grid { grid-template-columns: 1fr; }
        }
        @page { size: A4 portrait; margin: 12mm; }
        @media print {
            body { background: #fff; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none !important; }
            .report-shell { max-width: none; margin: 0; padding: 0; border: 0; border-radius: 0; box-shadow: none; }
            .report-head { break-after: avoid; }
            .summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px; }
            .summary-card { padding: 7px 8px; }
            .summary-card strong { font-size: 13px; }
            .audit-card { margin-bottom: 10px; }
            .audit-card-head, .comparison-head, .summary-card, .financial-card, .event-detail { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .items-table th, .items-table td { padding: 5px 6px; }
            .report-foot { margin-top: 12px; }
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="javascript:window.close()">Close report</a>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <main class="report-shell">
        <header class="report-head">
            <div class="store-identity">
                @if ($storeLogo)
                    <img class="store-logo" src="{{ $storeLogo }}" alt="{{ $storeName }} logo">
                @endif
                <div>
                    <div class="brand">{{ $storeName }}</div>
                    @if ($storeAddress)<div class="store-contact">{{ $storeAddress }}</div>@endif
                    @if ($storePhone)<div class="store-contact">Tel: {{ $storePhone }}</div>@endif
                    <h1>{{ $title }}</h1>
                    <div class="subtitle">{{ number_format($totalRows) }} audit record(s) · Detailed activity report</div>
                </div>
            </div>
            <div class="report-meta">
                Generated<br><strong>{{ $generatedAt->format('d M Y, h:i A') }}</strong><br>
                Currency: USD unless stated
                @if ($storeExchangeRate && $storeExchangeRate > 0)
                    <br>1 USD = {{ number_format($storeExchangeRate, 2) }} KHR
                @endif
            </div>
        </header>

        <section class="filter-box">
            <div class="filter-label">Report filters</div>
            <div class="filter-text">{{ $filterSummary ?: 'All available records' }}</div>
        </section>

        <section class="summary-grid">
            @foreach (($summaryItems ?? []) as $label => $value)
                <div class="summary-card">
                    <span>{{ $label }}</span>
                    <strong>{{ $value }}</strong>
                </div>
            @endforeach
        </section>

        @if ($reportType === 'corrections')
            <div class="section-title">
                <h2>Receipt correction history</h2>
                <span>Original receipt compared with corrected receipt</span>
            </div>

            @forelse ($records as $record)
                @php
                    $difference = (float) $record['difference'];
                    $adjustmentLabel = match ($record['adjustment_status']) {
                        'settled' => 'Cash adjustment settled',
                        'pending' => 'Cash adjustment pending',
                        default => 'No cash adjustment required',
                    };
                    $adjustmentClass = $record['adjustment_status'] === 'pending'
                        ? 'pending'
                        : (($record['adjustment_type'] ?? '') === 'refunded' ? 'refund' : '');
                    $cashActionLabel = match ($record['adjustment_type']) {
                        'collected' => 'Cash collected',
                        'refunded' => 'Cash refunded',
                        default => 'Cash adjustment',
                    };
                @endphp
                <article class="audit-card">
                    <div class="audit-card-head">
                        <div>
                            <div class="record-id">Correction #{{ $record['id'] }} <span style="color:#87948b;font-weight:400">·</span> Order #{{ $record['order_id'] }}</div>
                            <div class="record-sub">{{ $record['created_at'] }}</div>
                        </div>
                        <span class="status-pill {{ $adjustmentClass }}">{{ $adjustmentLabel }}</span>
                    </div>
                    <div class="card-body">
                        <div class="meta-grid">
                            <div class="meta-item"><span>Customer before</span><strong>{{ $record['customer_before'] }}</strong></div>
                            <div class="meta-item"><span>Customer after</span><strong>{{ $record['customer_after'] }}</strong></div>
                            <div class="meta-item"><span>Original cashier</span><strong>{{ $record['cashier'] }}</strong></div>
                            <div class="meta-item"><span>Corrected by</span><strong>{{ $record['corrected_by'] }}</strong></div>
                            <div class="meta-item"><span>Correction date</span><strong>{{ $record['created_at'] }}</strong></div>
                        </div>

                        <div class="reason-box">
                            <strong>Reason:</strong> {{ $record['reason'] }}
                            @if ($record['notes'] !== '')<br><strong>Staff notes:</strong> {{ $record['notes'] }}@endif
                        </div>

                        <div class="comparison-grid">
                            @foreach ([['key' => 'before_items', 'title' => 'Original receipt', 'total' => $record['original_total'], 'class' => 'before'], ['key' => 'after_items', 'title' => 'Corrected receipt', 'total' => $record['corrected_total'], 'class' => 'after']] as $panel)
                                <section class="comparison-panel {{ $panel['class'] }}">
                                    <div class="comparison-head">{{ $panel['title'] }}</div>
                                    @if (count($record[$panel['key']]) > 0)
                                        <table class="items-table">
                                            <thead><tr><th>Product</th><th class="num">Qty</th><th class="num">Unit</th><th class="num">Discount</th><th class="num">Line total</th></tr></thead>
                                            <tbody>
                                                @foreach ($record[$panel['key']] as $item)
                                                    <tr>
                                                        <td>{{ $item['product_name'] }}</td>
                                                        <td class="num">{{ number_format($item['quantity']) }}</td>
                                                        <td class="num">${{ number_format($item['unit_price'], 2) }}</td>
                                                        <td class="num">${{ number_format($item['discount_amount'], 2) }}</td>
                                                        <td class="num"><strong>${{ number_format($item['line_total'], 2) }}</strong></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="items-empty">No item snapshot was recorded.</div>
                                    @endif
                                    <div class="total-strip"><span>Receipt total</span><strong>${{ number_format($panel['total'], 2) }}</strong></div>
                                </section>
                            @endforeach
                        </div>

                        <div class="financial-grid">
                            <div class="financial-card"><span>Original total</span><strong>${{ number_format($record['original_total'], 2) }}</strong></div>
                            <div class="financial-card"><span>Corrected total</span><strong>${{ number_format($record['corrected_total'], 2) }}</strong></div>
                            <div class="financial-card emphasis {{ $difference < 0 ? 'negative' : '' }}"><span>Difference</span><strong>{{ $difference > 0 ? '+' : ($difference < 0 ? '−' : '') }}${{ number_format(abs($difference), 2) }}</strong></div>
                            <div class="financial-card"><span>Confirmed amount</span><strong>${{ number_format($record['confirmed_amount'], 2) }}</strong></div>
                        </div>

                        @if ($record['adjustment_status'] !== 'not_required' || $record['confirmed_by'] !== '—')
                            <div class="adjustment-row">
                                <span><strong>{{ $cashActionLabel }}:</strong> {{ $record['adjustment_status'] }}</span>
                                <span><strong>Confirmed by:</strong> {{ $record['confirmed_by'] }}</span>
                                <span><strong>Confirmed at:</strong> {{ $record['confirmed_at'] }}</span>
                                @if ($record['adjustment_note'] !== '')<span><strong>Adjustment note:</strong> {{ $record['adjustment_note'] }}</span>@endif
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty">No receipt corrections match the selected filters.</div>
            @endforelse
        @else
            <div class="section-title">
                <h2>POS price and discount activity</h2>
                <span>One card per recorded staff action</span>
            </div>

            @forelse ($records as $record)
                <article class="audit-card event-card">
                    <div class="audit-card-head">
                        <div>
                            <div class="record-id">Audit event #{{ $record['id'] }} <span style="color:#87948b;font-weight:400">·</span> Order #{{ $record['order_id'] }}</div>
                            <div class="record-sub">{{ $record['created_at'] }} · Staff: {{ $record['staff'] }}</div>
                        </div>
                        <span class="event-type {{ $record['is_price'] ? 'price' : '' }}">{{ $record['type'] }}</span>
                    </div>
                    <div class="card-body">
                        <div class="product-title">{{ $record['product_name'] }}</div>
                        <div class="product-sub">Product ID: {{ $record['product_id'] }}@if ($record['quantity'] !== null) · Quantity affected: {{ number_format($record['quantity']) }}@endif</div>

                        @if ($record['is_price'])
                            <div class="event-detail-grid">
                                <div class="event-detail"><span>Catalogue unit price</span><strong>${{ number_format($record['catalogue_price'], 2) }}</strong></div>
                                <div class="event-detail"><span>Charged unit price</span><strong>${{ number_format($record['charged_price'], 2) }}</strong></div>
                                <div class="event-detail"><span>Difference per unit</span><strong>{{ $record['unit_difference'] > 0 ? '+' : ($record['unit_difference'] < 0 ? '−' : '') }}${{ number_format(abs($record['unit_difference']), 2) }}</strong></div>
                                <div class="event-detail highlight"><span>Total price impact (quantity × difference)</span><strong>{{ $record['total_difference'] > 0 ? '+' : ($record['total_difference'] < 0 ? '−' : '') }}${{ number_format(abs($record['total_difference']), 2) }}</strong></div>
                            </div>
                        @else
                            <div class="event-detail-grid">
                                <div class="event-detail"><span>Discount rate</span><strong>{{ number_format($record['discount_percent'], 2) }}%</strong></div>
                                <div class="event-detail highlight"><span>Discount amount</span><strong>−${{ number_format($record['discount_amount'], 2) }}</strong></div>
                                <div class="event-detail"><span>Order reference</span><strong>#{{ $record['order_id'] }}</strong></div>
                                <div class="event-detail"><span>Staff member</span><strong>{{ $record['staff'] }}</strong></div>
                            </div>
                        @endif

                        <div class="event-reason"><strong>Reason recorded:</strong> {{ $record['reason'] }}</div>
                    </div>
                </article>
            @empty
                <div class="empty">No POS price or discount events match the selected filters.</div>
            @endforelse
        @endif

        <footer class="report-foot">
            <span>{{ $storeName }} · Confidential audit report</span>
            <span>Generated {{ $generatedAt->format('Y-m-d H:i:s') }} · {{ number_format($totalRows) }} record(s)</span>
        </footer>
    </main>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
