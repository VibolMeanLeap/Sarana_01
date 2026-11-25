@php
    $dateFilter = $dateFilter ?? ['preset' => 'today', 'from' => today()->toDateString(), 'to' => today()->toDateString()];
    $dateFromField = $dateFromField ?? 'start_date';
    $dateToField = $dateToField ?? 'end_date';
    $dateFilterTitle = $dateFilterTitle ?? 'Date range';
    $dateFilterHint = $dateFilterHint ?? 'Choose a quick period or set an exact range.';
    $datePreset = $dateFilter['preset'] ?? 'today';
    $dateFromValue = $dateFilter['from'] ?? '';
    $dateToValue = $dateFilter['to'] ?? '';
@endphp

@once
    @push('styles')
        <style>
            .admin-date-filter-panel {
                display: flex;
                flex-direction: column;
                gap: 12px;
                padding: 14px 16px;
                margin: 0 0 12px;
                border: 1px solid #dce9df;
                border-radius: 15px;
                background: linear-gradient(115deg, #f5faf6 0%, #fff 72%);
                box-shadow: 0 4px 14px rgba(24, 65, 42, .035)
            }

            /* The date panel and the secondary filter card are their own surfaces.
                       Remove the legacy global GET-form wrapper around pages using this component. */
            .content-area form[method="GET"]:has(.admin-date-filter-panel),
            .content-area form[method="get"]:has(.admin-date-filter-panel) {
                padding: 0 !important;
                margin-bottom: 16px !important;
                border: 0 !important;
                border-radius: 0 !important;
                background: transparent !important;
                box-shadow: none !important
            }

            .content-area form[method="GET"]:has(.admin-date-filter-panel)::before,
            .content-area form[method="get"]:has(.admin-date-filter-panel)::before {
                display: none !important;
                content: none !important
            }

            .admin-date-filter-head {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap
            }

            .admin-date-filter-title {
                display: flex;
                align-items: center;
                gap: 9px;
                margin: 0;
                color: #91DCAF;
                font-size: 13px;
                font-weight: 850
            }

            .admin-date-filter-title-icon {
                width: 31px;
                height: 31px;
                border-radius: 10px;
                display: grid;
                place-items: center;
                background: #e5f2e8;
                color: #176b4b;
                font-size: 15px
            }

            .admin-date-filter-hint {
                margin: 2px 0 0 40px;
                color: #7b8980;
                font-size: 11px
            }

            .admin-date-filter-controls {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                flex-wrap: wrap
            }

            .admin-date-presets {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap
            }

            .admin-date-preset {
                border: 1px solid #d9e6dc;
                background: #fff;
                color: #536b5a;
                border-radius: 999px;
                padding: 7px 12px;
                font-size: 11px;
                font-weight: 800;
                line-height: 1.15;
                transition: all .15s ease
            }

            .admin-date-preset:hover {
                border-color: #91bea0;
                background: #eef7f0;
                color: #176b4b
            }

            .admin-date-preset.is-active {
                border-color: #176b4b;
                background: #176b4b;
                color: #fff;
                box-shadow: 0 3px 8px rgba(23, 107, 75, .14)
            }

            .admin-date-custom {
                display: flex;
                align-items: end;
                gap: 7px;
                flex-wrap: wrap
            }

            .admin-date-field label {
                display: block;
                margin: 0 0 4px;
                color: #7b8980;
                font-size: 9px;
                font-weight: 850;
                letter-spacing: .06em;
                text-transform: uppercase
            }

            .admin-date-field input {
                height: 34px;
                min-width: 135px;
                border: 1px solid #dce7df;
                border-radius: 8px;
                background: #fff;
                color: #33463a;
                padding: 5px 8px;
                font-size: 11px
            }

            .admin-date-apply {
                height: 34px;
                border: 0;
                border-radius: 8px;
                background: #e8f3eb;
                color: #24613f;
                padding: 0 12px;
                font-size: 11px;
                font-weight: 850
            }

            .admin-date-apply:hover {
                background: #176b4b;
                color: #fff
            }

            @media(max-width:700px) {
                .admin-date-filter-panel {
                    padding: 12px
                }

                .admin-date-filter-controls {
                    align-items: stretch
                }

                .admin-date-presets {
                    display: grid;
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    width: 100%
                }

                .admin-date-preset {
                    padding: 8px 5px;
                    text-align: center
                }

                .admin-date-custom {
                    width: 100%
                }

                .admin-date-field {
                    flex: 1;
                    min-width: 0
                }

                .admin-date-field input {
                    width: 100%;
                    min-width: 0
                }

                .admin-date-apply {
                    width: 100%
                }
            }
        </style>
    @endpush
@endonce

<section class="admin-date-filter-panel" data-admin-date-filter aria-label="{{ $dateFilterTitle }}">
    <div class="admin-date-filter-head">
        <div>
            <h2 class="admin-date-filter-title"><span class="admin-date-filter-title-icon"><i
                        class="bi bi-calendar3"></i></span>{{ $dateFilterTitle }}</h2>
            <p class="admin-date-filter-hint">{{ $dateFilterHint }}</p>
        </div>
        <span class="small text-muted"><i
                class="bi bi-clock-history me-1"></i>{{ $datePreset === 'all' ? 'All records' : ucfirst($datePreset === 'week' ? 'This week' : ($datePreset === 'month' ? 'This month' : $datePreset)) }}</span>
    </div>
    <input type="hidden" name="date_preset" value="{{ $datePreset }}" data-admin-date-preset-input>
    <div class="admin-date-filter-controls">
        <div class="admin-date-presets" role="group" aria-label="Quick date ranges">
            @foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'all' => 'All time'] as $key => $label)
                <button type="button" class="admin-date-preset {{ $datePreset === $key ? 'is-active' : '' }}"
                    data-admin-date-preset="{{ $key }}"
                    aria-pressed="{{ $datePreset === $key ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="admin-date-custom">
            <div class="admin-date-field"><label for="{{ $dateFromField }}_admin_date_from">From</label><input
                    id="{{ $dateFromField }}_admin_date_from" type="date" name="{{ $dateFromField }}"
                    value="{{ $dateFromValue }}" data-admin-date-custom></div>
            <div class="admin-date-field"><label for="{{ $dateToField }}_admin_date_to">To</label><input
                    id="{{ $dateToField }}_admin_date_to" type="date" name="{{ $dateToField }}"
                    value="{{ $dateToValue }}" data-admin-date-custom></div>
            <button type="submit" class="admin-date-apply"><i class="bi bi-filter me-1"></i>Apply dates</button>
        </div>
    </div>
</section>

@once
    <script>
        document.addEventListener('click', function(event) {
            const presetButton = event.target.closest('[data-admin-date-preset]');
            if (!presetButton) return;
            const panel = presetButton.closest('[data-admin-date-filter]');
            const form = panel?.closest('form');
            const presetInput = panel?.querySelector('[data-admin-date-preset-input]');
            if (!form || !presetInput) return;
            presetInput.value = presetButton.dataset.adminDatePreset;
            panel.querySelectorAll('[data-admin-date-custom]').forEach(input => input.value = '');
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });

        document.addEventListener('change', function(event) {
            if (!event.target.matches('[data-admin-date-custom]')) return;
            const panel = event.target.closest('[data-admin-date-filter]');
            const presetInput = panel?.querySelector('[data-admin-date-preset-input]');
            if (presetInput) presetInput.value = 'custom';
            panel?.querySelectorAll('[data-admin-date-preset]').forEach(button => {
                const active = button.dataset.adminDatePreset === 'custom';
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        });
    </script>
@endonce
