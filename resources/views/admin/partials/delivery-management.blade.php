@php
    /** @var \App\Models\Order $row */
    $deliveryFulfillment = app(\App\Services\OrderFulfillmentService::class);
    $deliveryStatus = strtolower((string) ($row->order_status ?: 'pending'));
    $isCodOrder = $deliveryFulfillment->isCashOnDelivery($row);
    $nextDeliveryStatus = $deliveryFulfillment->nextStatus($row);
    $canAdvanceDelivery = $row->delivery !== null
        && $nextDeliveryStatus !== null
        && ! in_array($deliveryStatus, ['completed', 'cancelled'], true);
    $isCodHandoff = $isCodOrder && ! $row->is_paid && $nextDeliveryStatus === 'completed';
    $amountDue = number_format((float) ($row->final_price ?? $row->total_amount ?? 0), 2, '.', '');

    $deliveryStages = [
        ['key' => 'pending', 'label' => 'Received', 'description' => 'Order received by the store', 'icon' => 'bi-inbox'],
        ['key' => 'confirmed', 'label' => 'Confirmed', 'description' => 'Store accepted the order', 'icon' => 'bi-check2-circle'],
        ['key' => 'preparing', 'label' => 'Preparing', 'description' => 'Team is preparing the items', 'icon' => 'bi-cup-hot'],
        ['key' => 'ready', 'label' => 'Ready', 'description' => 'Packed and ready to dispatch', 'icon' => 'bi-box-seam'],
        ['key' => 'out_for_delivery', 'label' => 'Dispatched', 'description' => 'Driver is on the way', 'icon' => 'bi-truck'],
        ['key' => 'completed', 'label' => 'Delivered', 'description' => 'Customer handoff completed', 'icon' => 'bi-house-check'],
    ];
    $deliverySequence = array_column($deliveryStages, 'key');
    $currentStageIndex = array_search($deliveryStatus, $deliverySequence, true);
    $currentStageIndex = $currentStageIndex === false ? 0 : $currentStageIndex;
    $deliveryProgress = (int) round(($currentStageIndex / (count($deliveryStages) - 1)) * 100);
    $deliveryProgress = $deliveryStatus === 'cancelled' ? 0 : $deliveryProgress;

    $nextActionLabels = [
        'confirmed' => 'Confirm order',
        'preparing' => 'Start preparing',
        'ready' => 'Mark ready for dispatch',
        'out_for_delivery' => 'Dispatch order',
        'completed' => $isCodOrder && ! $row->is_paid ? 'Confirm delivery & cash' : 'Confirm delivery',
    ];

    $paymentMethodLabel = $row->paymentMethod?->payment_name ?: 'Not specified';
    $paymentLabel = $row->is_paid ? 'Paid' : ($isCodOrder ? 'Cash due at handoff' : ($row->payment_status ?: 'Unpaid'));
    $paymentTone = $row->is_paid ? 'success' : ($isCodOrder ? 'warning' : 'secondary');
    $recentDeliveryEvents = ($row->statusHistory ?? collect())->sortByDesc('created_at')->take(8);
    $dialogId = 'cod-handoff-dialog-'.$row->order_id;
    $formId = 'delivery-status-form-'.$row->order_id;
@endphp

<section class="delivery-admin-panel" data-delivery-panel="{{ $row->order_id }}" aria-label="Delivery management for {{ strtoupper((string) ($row->order_source ?: 'POS')) }} order {{ $row->order_id }}">
    <div class="delivery-panel-heading">
        <div class="d-flex align-items-start gap-3">
            <span class="delivery-panel-icon"><i class="bi bi-truck-front-fill"></i></span>
            <div class="min-w-0 flex-grow-1">
                <div class="delivery-eyebrow">FULFILMENT CONTROL · {{ strtoupper((string) ($row->order_source ?: 'POS')) }} ORDER #{{ $row->order_id }}</div>
                <div class="delivery-panel-title">Delivery progress</div>
                <div class="delivery-panel-subtitle">Update each stage here. The customer's tracking page and activity history stay in sync.</div>
            </div>
            <span class="status-pill {{ $deliveryStatus === 'completed' ? 'completed' : ($deliveryStatus === 'cancelled' ? 'cancelled' : ($deliveryStatus === 'out_for_delivery' ? 'out_for_delivery' : ($deliveryStatus === 'ready' ? 'ready' : 'pending'))) }}" data-panel-status>
                {{ $deliveryStatus === 'completed' ? 'Delivered' : ucfirst(str_replace('_', ' ', $deliveryStatus)) }}
            </span>
        </div>
    </div>

    @if ($deliveryStatus !== 'cancelled')
        <div class="delivery-progress-summary">
            <div class="d-flex align-items-end justify-content-between gap-3 mb-2">
                <div>
                    <div class="delivery-progress-label">DELIVERY JOURNEY</div>
                    <div class="delivery-progress-caption">{{ $deliveryStatus === 'completed' ? 'This order has reached the customer.' : ($deliveryStatus === 'pending' ? 'Your team can start by confirming this order.' : 'You are moving this order through the fulfilment process.') }}</div>
                </div>
                <strong class="delivery-progress-percent" data-progress-percent>{{ $deliveryProgress }}%</strong>
            </div>
            <div class="delivery-progress-track" role="progressbar" aria-valuenow="{{ $deliveryProgress }}" aria-valuemin="0" aria-valuemax="100" aria-label="Delivery progress">
                <span style="width: {{ $deliveryProgress }}%" data-progress-bar></span>
            </div>
        </div>

        <div class="delivery-visual-stepper" aria-label="Delivery stages">
            @foreach ($deliveryStages as $index => $stage)
                @php
                    $stageState = $index < $currentStageIndex ? 'is-done' : ($index === $currentStageIndex ? 'is-current' : 'is-upcoming');
                    if ($deliveryStatus === 'completed') {
                        $stageState = 'is-done';
                    }
                @endphp
                <div class="delivery-visual-step {{ $stageState }}" data-stage="{{ $stage['key'] }}">
                    <span class="delivery-visual-step-icon">
                        @if ($stageState === 'is-done')
                            <i class="bi bi-check-lg"></i>
                        @else
                            <i class="bi {{ $stage['icon'] }}"></i>
                        @endif
                    </span>
                    <span class="delivery-visual-step-label">{{ $stage['label'] }}</span>
                    <span class="delivery-visual-step-description">{{ $stage['description'] }}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-danger py-2 mb-3"><i class="bi bi-x-octagon me-2"></i>This delivery was cancelled. The order timeline below is retained for audit purposes.</div>
    @endif

    <div class="delivery-info-grid">
        <div class="delivery-info-card delivery-info-wide">
            <span class="delivery-info-icon"><i class="bi bi-person-circle"></i></span>
            <div class="min-w-0">
                <span class="delivery-info-label">CUSTOMER</span>
                <strong>{{ $row->customer?->name ?: 'Customer not recorded' }}</strong>
                @if ($row->customer?->phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $row->customer->phone) }}" class="delivery-info-link">{{ $row->customer->phone }}</a>
                @else
                    <span class="delivery-info-muted">No phone number provided</span>
                @endif
            </div>
        </div>
        <div class="delivery-info-card delivery-info-wide">
            <span class="delivery-info-icon"><i class="bi bi-geo-alt-fill"></i></span>
            <div class="min-w-0">
                <span class="delivery-info-label">DELIVERY ADDRESS</span>
                <strong>{{ $row->delivery?->address ?: 'Address not recorded' }}</strong>
                <span class="delivery-info-muted">{{ $row->delivery ? 'Home delivery' : 'Delivery record missing' }}</span>
            </div>
        </div>
        <div class="delivery-info-card">
            <span class="delivery-info-icon"><i class="bi bi-truck"></i></span>
            <div>
                <span class="delivery-info-label">DELIVERY FEE</span>
                <strong>${{ number_format((float) ($row->delivery?->cost ?? 0), 2) }}</strong>
            </div>
        </div>
        <div class="delivery-info-card">
            <span class="delivery-info-icon"><i class="bi bi-receipt"></i></span>
            <div>
                <span class="delivery-info-label">ORDER TOTAL</span>
                <strong>${{ number_format((float) ($row->final_price ?? $row->total_amount ?? 0), 2) }}</strong>
            </div>
        </div>
        <div class="delivery-info-card delivery-info-wide">
            <span class="delivery-info-icon"><i class="bi bi-wallet2"></i></span>
            <div class="min-w-0">
                <span class="delivery-info-label">PAYMENT</span>
                <strong>{{ $paymentMethodLabel }}</strong>
                <span class="delivery-payment-state text-{{ $paymentTone }}">{{ $paymentLabel }}</span>
            </div>
        </div>
    </div>

    @if ($isCodOrder)
        @if ($row->is_paid && $row->cod_collected_at)
            <div class="delivery-cod-receipt">
                <div class="delivery-cod-receipt-icon"><i class="bi bi-patch-check-fill"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <strong>Cash collected and recorded</strong>
                    <div class="small">${{ number_format((float) ($row->cod_collected_amount ?? $amountDue), 2) }} received · {{ optional($row->cod_collected_at)->format('d M Y, h:i A') }}</div>
                    @if ($row->cod_collection_note)
                        <div class="small mt-1">{{ $row->cod_collection_note }}</div>
                    @endif
                </div>
            </div>
        @elseif (! $row->is_paid)
            <div class="delivery-cod-due"><i class="bi bi-cash-coin me-2"></i>Collect <strong>${{ $amountDue }}</strong> from the customer at handoff. The order will only be marked paid after the exact amount is confirmed.</div>
        @endif
    @endif

    <div class="delivery-next-action" data-delivery-feedback-wrap>
        <div class="delivery-next-copy">
            @if ($deliveryStatus === 'completed')
                <strong><i class="bi bi-check-circle-fill text-success me-1"></i>Delivery completed</strong>
                <span>The customer handoff has been recorded.</span>
            @elseif ($deliveryStatus === 'cancelled')
                <strong><i class="bi bi-x-circle-fill text-danger me-1"></i>Order cancelled</strong>
                <span>No further delivery actions are available.</span>
            @elseif (! $row->delivery)
                <strong><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Delivery record missing</strong>
                <span>Create or repair the delivery record before advancing this order.</span>
            @elseif (! $row->is_paid && ! $isCodOrder)
                <strong><i class="bi bi-hourglass-split text-warning me-1"></i>Payment verification required</strong>
                <span>Fulfilment is locked until online payment is verified.</span>
            @elseif ($nextDeliveryStatus)
                <strong>Next step: {{ str_replace('_', ' ', ucfirst($nextDeliveryStatus)) }}</strong>
                <span>{{ $nextActionLabels[$nextDeliveryStatus] ?? 'Advance delivery' }} · one step at a time</span>
            @else
                <strong>Waiting for next action</strong>
                <span>Refresh the order if the status was updated elsewhere.</span>
            @endif
        </div>

        @if ($canAdvanceDelivery)
            <form id="{{ $formId }}" method="POST" action="{{ route('admin.orders.status', $row->order_id) }}" class="delivery-ajax-form" data-ajax-status-form data-order-id="{{ $row->order_id }}">
                @csrf
                <input type="hidden" name="order_status" value="{{ $nextDeliveryStatus }}">
                @if ($isCodHandoff)
                    <button type="button" class="btn btn-success delivery-next-button" data-open-cod-dialog="{{ $dialogId }}">
                        <i class="bi bi-cash-coin me-2"></i>Confirm delivery & collect cash
                    </button>
                @else
                    <button type="submit" class="btn btn-success delivery-next-button" data-status-submit>
                        <i class="bi {{ $nextDeliveryStatus === 'out_for_delivery' ? 'bi-truck' : ($nextDeliveryStatus === 'completed' ? 'bi-house-check' : 'bi-arrow-right-circle') }} me-2"></i>
                        {{ $nextActionLabels[$nextDeliveryStatus] ?? 'Update delivery' }}
                    </button>
                @endif
            </form>
        @endif
        <div class="delivery-feedback" data-delivery-feedback role="status" aria-live="polite" hidden></div>
    </div>

    @if ($isCodHandoff && $canAdvanceDelivery)
        <dialog class="cod-settlement-dialog" id="{{ $dialogId }}" aria-labelledby="{{ $dialogId }}-title" aria-describedby="{{ $dialogId }}-description">
            <div class="cod-settlement-shell">
                <div class="cod-settlement-header">
                    <div class="cod-settlement-brand">
                        <span class="cod-settlement-icon"><i class="bi bi-wallet2"></i></span>
                        <div>
                            <span class="cod-settlement-eyebrow">COFFEE CORNER AURA · FINAL HANDOFF</span>
                            <h3 id="{{ $dialogId }}-title">Complete COD delivery</h3>
                            <p id="{{ $dialogId }}-description">Confirm the customer has received the order and paid in cash.</p>
                        </div>
                    </div>
                    <button type="button" class="cod-settlement-close" data-close-cod-dialog aria-label="Close payment confirmation">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="cod-settlement-content">
                    <div class="cod-settlement-total">
                        <div class="cod-settlement-total-copy">
                            <span>AMOUNT TO COLLECT</span>
                            <strong>${{ $amountDue }}</strong>
                            <small>Order #{{ $row->order_id }} · Cash on Delivery</small>
                        </div>
                        <span class="cod-settlement-cash-icon"><i class="bi bi-cash-coin"></i></span>
                    </div>

                    <div class="cod-settlement-hint">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Enter the exact amount applied to this order. If the customer pays with a larger note, return the correct change before confirming.</span>
                    </div>

                    <div class="cod-settlement-fields">
                        <div class="cod-settlement-field">
                            <label for="cod-amount-{{ $row->order_id }}">Cash received for this order <span>USD</span></label>
                            <div class="cod-settlement-input-wrap">
                                <span>$</span>
                                <input
                                    id="cod-amount-{{ $row->order_id }}"
                                    form="{{ $formId }}"
                                    data-cod-amount
                                    type="number"
                                    name="cod_collected_amount"
                                    min="0"
                                    max="99999999"
                                    step="0.01"
                                    value="{{ old('cod_collected_amount', $amountDue) }}"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    required
                                >
                            </div>
                            <small>Must match the order total of ${{ $amountDue }} exactly.</small>
                        </div>

                        <div class="cod-settlement-field">
                            <label for="cod-note-{{ $row->order_id }}">Collection note <span>OPTIONAL</span></label>
                            <input
                                id="cod-note-{{ $row->order_id }}"
                                form="{{ $formId }}"
                                type="text"
                                name="cod_collection_note"
                                maxlength="500"
                                value="{{ old('cod_collection_note') }}"
                                placeholder="e.g. Received by delivery staff"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <label class="cod-settlement-confirm" for="cod-confirm-{{ $row->order_id }}">
                        <input
                            id="cod-confirm-{{ $row->order_id }}"
                            form="{{ $formId }}"
                            type="checkbox"
                            name="cod_collected_confirmed"
                            value="1"
                            required
                        >
                        <span class="cod-settlement-confirm-mark"><i class="bi bi-check-lg"></i></span>
                        <span class="cod-settlement-confirm-copy">
                            <strong>I confirm cash was collected</strong>
                            <small>The order was handed to the customer and the full amount above was received.</small>
                        </span>
                    </label>

                    <div class="cod-settlement-feedback" data-cod-dialog-feedback role="alert" aria-live="polite" hidden></div>
                </div>

                <div class="cod-settlement-footer">
                    <div class="cod-settlement-secure"><i class="bi bi-shield-check"></i> This payment action is recorded in order history.</div>
                    <div class="cod-settlement-actions">
                        <button type="button" class="cod-settlement-cancel" data-close-cod-dialog>Not yet</button>
                        <button type="submit" form="{{ $formId }}" class="cod-settlement-submit" data-status-submit>
                            <i class="bi bi-check-circle-fill"></i>
                            Confirm & complete delivery
                        </button>
                    </div>
                </div>
            </div>
        </dialog>
    @endif

    <div class="delivery-activity">
        <div class="delivery-activity-heading">
            <div>
                <div class="delivery-eyebrow">AUDIT TRAIL</div>
                <strong>Delivery activity</strong>
            </div>
            <span class="delivery-activity-count">{{ $recentDeliveryEvents->count() }} events</span>
        </div>
        @if ($recentDeliveryEvents->isNotEmpty())
            <ol class="delivery-activity-list">
                @foreach ($recentDeliveryEvents as $event)
                    @php
                        $activityTitle = match ($event->event_type) {
                            'payment_collected' => 'COD payment collected',
                            'payment_verified' => 'Payment verified',
                            'telegram_notified' => 'Store notification sent',
                            'order_created' => 'Order received',
                            default => $event->to_status
                                ? 'Status changed to '.str_replace('_', ' ', $event->to_status)
                                : ucfirst(str_replace('_', ' ', $event->event_type)),
                        };
                    @endphp
                    <li class="delivery-activity-item">
                        <span class="delivery-activity-dot"><i class="bi {{ $event->event_type === 'payment_collected' ? 'bi-cash-coin' : 'bi-clock-history' }}"></i></span>
                        <div class="min-w-0 flex-grow-1">
                            <strong>{{ ucfirst($activityTitle) }}</strong>
                            <span>{{ $event->actor_label ?: ucfirst(str_replace('_', ' ', $event->actor_type ?: 'system')) }} · {{ optional($event->created_at)->format('d M Y, h:i A') ?: 'Time unavailable' }}</span>
                            @if ($event->note)
                                <small>{{ $event->note }}</small>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="delivery-activity-empty">No activity has been recorded for this order yet.</p>
        @endif
    </div>
</section>
