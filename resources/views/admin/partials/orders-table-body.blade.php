@forelse($orders as $row)
          @php
          $orderStatus = strtolower((string) ($row->order_status ?: ($row->is_paid ? 'completed' : 'pending')));
          $payment = $row->payments->sortByDesc('id')->first();
          $paymentStatus = $row->is_paid ? 'paid' : strtolower((string) ($payment?->status ?: 'unpaid'));
          $isDeliveryOrder = strtolower(trim((string) ($row->orderType?->order_name ?? ''))) === 'delivery';
          @endphp
          <tr class="order-summary-row" data-order-row="{{ $row->order_id }}" data-current-order-status="{{ $orderStatus }}" tabindex="0" aria-expanded="false"
            title="Click to view order items and delivery management">
            <td>
              <span class="order-id">#{{ $row->order_id }}</span>
              <span class="order-expand-hint"><i class="bi bi-chevron-down"></i> {{ (int) $row->order_items_count }} items</span>
              @if($isDeliveryOrder)
                <span class="order-expand-hint text-success"><i class="bi bi-truck"></i> Delivery controls · {{ strtoupper($row->order_source ?: 'POS') }}</span>
              @endif
            </td>
            <td>
              <strong class="d-block">{{ $row->customer?->name ?: 'Walk-in' }}</strong>
              <span class="small text-muted">{{ $row->customer?->phone ?: 'No phone recorded' }}</span>
            </td>
            <td>{{ $row->cashier?->name ?: 'Website' }}</td>
            <td>
              <strong class="d-block">{{ $row->orderType?->order_name ?: '—' }}</strong>
              <span class="status-pill {{ $row->order_source === 'website' ? 'confirmed' : 'ready' }}">{{ ucfirst($row->order_source ?: 'pos') }}</span>
            </td>
            <td>${{ number_format((float) $row->total_amount, 2) }}</td>
            <td>{{ number_format((float) $row->discount, 2) }}%</td>
            <td class="fw-bold">${{ number_format((float) $row->final_price, 2) }}</td>
            <td>{{ $row->paymentMethod?->payment_name ?: ($payment?->provider === 'aba_payway' ? 'ABA KHQR' : '—') }}
            </td>
            @php
            $paymentShort = ['paid' => 'PAID', 'unpaid' => 'UNPD', 'pending' => 'PEND', 'failed' => 'FAIL', 'declined'
            => 'DECL', 'expired' => 'EXP', 'review_required' => 'REVIEW'][$paymentStatus] ??
            strtoupper(substr($paymentStatus, 0, 5));
            $orderShort = ['pending' => 'PEND', 'confirmed' => 'CONF', 'preparing' => 'PREP', 'ready' => 'READY',
            'out_for_delivery' => 'DISP', 'completed' => 'DONE', 'cancelled' => 'CXL'][$orderStatus] ?? strtoupper(substr($orderStatus, 0, 5));
            @endphp
            <td data-payment-status-cell="{{ $row->order_id }}"><span data-payment-status-label class="status-pill compact {{ $paymentStatus }}"
                title="Payment: {{ str_replace('_', ' ', ucfirst($paymentStatus)) }}"
                aria-label="Payment: {{ str_replace('_', ' ', ucfirst($paymentStatus)) }}">{{ $paymentShort }}</span>
            </td>
            <td data-order-status-cell="{{ $row->order_id }}"><span data-order-status-label
                class="status-pill compact {{ in_array($orderStatus, $orderStatuses, true) ? $orderStatus : 'pending' }}"
                title="Order status: {{ str_replace('_', ' ', ucfirst($orderStatus)) }}"
                aria-label="Order status: {{ str_replace('_', ' ', ucfirst($orderStatus)) }}">{{ $orderShort }}</span>
            </td>
            <td class="text-nowrap" style="min-width:96px;padding-left:10px;padding-right:10px">
              <div style="color:#294233;font-size:11px;font-weight:850;line-height:1.25">{{ $row->order_date?->format('d M Y') ?: '—' }}</div>
              <div style="display:flex;align-items:center;gap:4px;margin-top:4px;color:#839087;font-size:10px;line-height:1.2"><i class="bi bi-clock" aria-hidden="true"></i><span>{{ $row->order_date?->format('h:i A') ?: '—' }}</span></div>
            </td>
            <td class="text-nowrap" data-order-review-cell="{{ $row->order_id }}">
              @if(($row->open_review_flags_count ?? 0) > 0)
                <span class="badge rounded-pill text-bg-warning mb-1"><i class="bi bi-flag-fill me-1"></i>{{ $row->open_review_flags_count }} open</span><br>
              @else
                <span class="badge rounded-pill" style="background:#e8f4eb;color:#24613e">Clear</span><br>
              @endif
              <button type="button" class="btn btn-sm btn-outline-success mt-1" data-open-order-review data-order-id="{{ $row->order_id }}" data-bs-toggle="modal" data-bs-target="#orderReviewFlagModal"><i class="bi bi-flag me-1"></i>Mark</button>
              <a class="btn btn-sm btn-link px-1 mt-1" href="{{ route('admin.orders.receipt', $row->order_id) }}" target="_blank" rel="noopener"><i class="bi bi-receipt me-1"></i>Receipt</a>
            </td>
          </tr>
          <tr class="order-items-detail d-none" id="order-items-{{ $row->order_id }}">
            <td colspan="12">
              <div class="order-items-panel">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                  <div>
                    <strong>Items sold in order #{{ $row->order_id }}</strong>
                    <span class="text-muted small ms-2">{{ $row->orderItems->count() }} line(s)</span>
                  </div>
                  <strong class="text-success">Order total:
                    ${{ number_format((float) ($row->final_price ?? $row->total_amount), 2) }}</strong>
                </div>
                @if($row->orderItems->isNotEmpty())
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0 order-line-table">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Type</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Line total</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($row->orderItems as $line)
                      <tr>
                        <td>{{ $line->product?->product_name ?? ('Deleted product #'.($line->product_id ?? '')) }}</td>
                        <td><span
                            class="badge {{ $line->product && $line->product->isStockTracked() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $line->product ? ($line->product->isStockTracked() ? 'Stock' : 'Non-stock') : 'Archived' }}</span>
                        </td>
                        <td class="text-end">{{ number_format((int) $line->quantity) }}</td>
                        <td class="text-end">${{ number_format((float) $line->price, 2) }}</td>
                        <td class="text-end fw-semibold">
                          ${{ number_format((float) $line->price * (int) $line->quantity, 2) }}</td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
                @else
                <div class="text-muted small py-2">No line items were recorded for this order.</div>
                @endif

                @if($row->reviewFlags->isNotEmpty())
                  <div class="mt-3 p-3 rounded-3" style="background:#f0f7f2;border:1px solid #d8e8dd">
                    <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-shield-exclamation text-success"></i><strong class="small">Order review history</strong></div>
                    @foreach($row->reviewFlags as $flag)
                      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}" style="border-color:#dce9df!important">
                        <div class="flex-grow-1">
                          <div class="d-flex flex-wrap align-items-center gap-2"><strong class="small">{{ \Illuminate\Support\Str::headline($flag->reason) }}</strong><span class="badge {{ $flag->status === 'open' ? 'text-bg-warning' : 'text-bg-success' }}">{{ ucfirst($flag->status) }}</span></div>
                          <div class="small mt-1">{{ $flag->notes }}</div>
                          <div class="small text-muted mt-1">Marked {{ $flag->created_at?->format('d M Y, h:i A') ?: '' }}@if($flag->related_order_id) · Related order: <a href="{{ route('admin.orders.receipt', $flag->related_order_id) }}" target="_blank" rel="noopener">#{{ $flag->related_order_id }}</a>@endif</div>
                        </div>
                        @if($flag->status === 'open')
                          <form method="POST" action="{{ route('admin.orders.flags.resolve', [$row->order_id, $flag->flag_id]) }}" onsubmit="return confirm('Resolve this marker? The audit note will remain in the order history.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-success">Resolve</button>
                          </form>
                        @endif
                      </div>
                    @endforeach
                  </div>
                @endif

                @if ($isDeliveryOrder)
                  @include('admin.partials.delivery-management', ['row' => $row])
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="12" class="text-center text-muted py-5">No orders match these filters.</td>
          </tr>
          @endforelse
