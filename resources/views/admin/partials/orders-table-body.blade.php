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
            <td>{{ $row->customer?->name ?: 'Walk-in' }}</td>
            <td>{{ $row->customer?->phone ?: '—' }}</td>
            <td>{{ $row->cashier?->name ?: 'Website' }}</td>
            <td>{{ $row->orderType?->order_name ?: '—' }}</td>
            <td><span
                class="status-pill {{ $row->order_source === 'website' ? 'confirmed' : 'ready' }}">{{ ucfirst($row->order_source ?: 'pos') }}</span>
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
            <td>{{ $row->order_date?->format('d M Y, h:i A') ?: '—' }}</td>
          </tr>
          <tr class="order-items-detail d-none" id="order-items-{{ $row->order_id }}">
            <td colspan="13">
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

                @if ($isDeliveryOrder)
                  @include('admin.partials.delivery-management', ['row' => $row])
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="13" class="text-center text-muted py-5">No orders match these filters.</td>
          </tr>
          @endforelse
