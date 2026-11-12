<style>
    .aura-date-filter { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:17px 19px; border:1px solid #e6eee8; border-radius:17px; background:#fff; box-shadow:0 5px 16px rgba(23,65,42,.035); }
    .aura-range-pills { display:flex; align-items:center; gap:6px; flex-wrap:wrap; padding:4px; background:#f3f7f4; border-radius:12px; }
    .aura-range-pills a { padding:8px 12px; border-radius:9px; color:#65736a; font-size:12px; font-weight:750; text-decoration:none; }
    .aura-range-pills a:hover { color:#176b4b; background:#e9f4ed; }
    .aura-range-pills a.active { color:#fff; background:#176b4b; box-shadow:0 3px 8px rgba(23,107,75,.18); }
    .aura-custom-range { display:flex; align-items:end; gap:8px; flex-wrap:wrap; }
    .aura-custom-range label { display:block; margin:0 0 4px; color:#77847b; font-size:10px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .aura-custom-range input { min-width:142px; border:1px solid #dce7df; border-radius:9px; padding:8px 10px; color:#28372e; font-size:12px; }
    .aura-custom-range button { border:0; border-radius:9px; padding:9px 13px; background:#176b4b; color:#fff; font-size:12px; font-weight:750; }
    @media(max-width:640px){.aura-date-filter{align-items:stretch}.aura-range-pills{width:100%}.aura-range-pills a{flex:1;text-align:center;padding:8px 7px}.aura-custom-range{width:100%}.aura-custom-range>div{flex:1}.aura-custom-range input{width:100%;min-width:0}.aura-custom-range button{width:100%}}
</style>
<section class="aura-date-filter" aria-label="{{ __('Dashboard date filter') }}">
    <div>
        <div class="small fw-bold text-dark mb-1"><i class="bi bi-calendar3 text-success me-1"></i>{{ __('Reporting period') }}</div>
        <div class="small text-muted">{{ __('Showing dashboard activity for') }} <strong>{{ __($dateRangeLabel) }}</strong></div>
    </div>
    <div class="d-flex flex-column gap-2 align-items-stretch">
        <nav class="aura-range-pills" aria-label="{{ __('Date range presets') }}">
            @foreach ([ 'today' => 'Today', '7days' => 'Last 7 days', '30days' => 'Last 30 days', 'this_month' => 'This month', 'custom' => 'Custom' ] as $rangeKey => $rangeLabel)
                <a href="{{ route('admin.dashboard', ['range' => $rangeKey]) }}" class="{{ $range === $rangeKey ? 'active' : '' }}" @if($range === $rangeKey) aria-current="page" @endif>{{ __($rangeLabel) }}</a>
            @endforeach
        </nav>
        @if ($range === 'custom')
            <form method="GET" action="{{ route('admin.dashboard') }}" class="aura-custom-range">
                <input type="hidden" name="range" value="custom">
                <div><label for="dashboardStartDate">{{ __('From') }}</label><input id="dashboardStartDate" type="date" name="start_date" value="{{ $startDateValue }}" required></div>
                <div><label for="dashboardEndDate">{{ __('To') }}</label><input id="dashboardEndDate" type="date" name="end_date" value="{{ $endDateValue }}" required></div>
                <button type="submit"><i class="bi bi-filter me-1"></i>{{ __('Apply range') }}</button>
            </form>
        @endif
    </div>
</section>
@if ($filterError)
    <div class="alert alert-warning rounded-3 mb-0 py-2 small"><i class="bi bi-exclamation-triangle me-1"></i>{{ __($filterError) }}</div>
@endif
