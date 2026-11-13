@php
    $activeLocale = in_array(app()->getLocale(), ['en', 'km'], true) ? app()->getLocale() : 'en';
@endphp

<style>
    .cc-pos-settings .dropdown-menu {
        min-width: 255px;
        padding: 12px;
        border: 1px solid #e5ebe6;
        border-radius: 16px;
        box-shadow: 0 16px 38px rgba(22, 59, 39, .14);
    }
    .cc-pos-settings .cc-pref-label {
        display: block;
        margin: 2px 4px 9px;
        color: #728078;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .11em;
        text-transform: uppercase;
    }
    .cc-pos-settings .cc-locale-choice {
        display: flex;
        align-items: center;
        gap: 11px;
        width: 100%;
        padding: 11px 12px;
        border: 1px solid transparent;
        border-radius: 11px;
        background: transparent;
        color: #26382d;
        text-align: left;
        font-size: 13px;
        font-weight: 700;
    }
    .cc-pos-settings .cc-locale-choice:hover { background: #f2f8f3; }
    .cc-pos-settings .cc-locale-choice.active {
        border-color: #d5e9dc;
        background: #edf7f0;
        color: #176b4b;
    }
    .cc-pos-settings .cc-locale-choice .cc-check { margin-left: auto; }
    .cc-pos-settings .cc-pref-footnote {
        margin: 10px 4px 2px;
        color: #87928a;
        font-size: 11px;
        line-height: 1.45;
    }
</style>

<div class="dropdown cc-pos-settings">
    <button class="btn btn-sm btn-pos-outline dropdown-toggle" type="button"
        data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ $activeLocale === 'km' ? 'ការកំណត់' : 'Settings' }}">
        <i class="bi bi-gear me-1"></i>
        <span>{{ $activeLocale === 'km' ? 'ការកំណត់' : 'Settings' }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        <div class="d-flex align-items-center gap-2 px-1 mb-3">
            <span class="rounded-3 d-inline-flex align-items-center justify-content-center"
                style="width:36px;height:36px;background:#edf7f0;color:#176b4b">
                <i class="bi bi-sliders2"></i>
            </span>
            <div>
                <div class="fw-bold small">{{ $activeLocale === 'km' ? 'ចំណូលចិត្ត POS' : 'POS preferences' }}</div>
                <div class="text-muted" style="font-size:11px">{{ $activeLocale === 'km' ? 'ការកំណត់សម្រាប់វគ្គនេះ' : 'Personal workspace settings' }}</div>
            </div>
        </div>

        <span class="cc-pref-label">{{ $activeLocale === 'km' ? 'ភាសាប្រើប្រាស់' : 'INTERFACE LANGUAGE' }}</span>

        <form method="POST" action="{{ route('language.switch') }}">
            @csrf
            <input type="hidden" name="locale" value="en">
            <button type="submit" class="cc-locale-choice {{ $activeLocale === 'en' ? 'active' : '' }}">
                <span aria-hidden="true" style="font-size:19px">🇬🇧</span>
                <span>English</span>
                @if ($activeLocale === 'en') <i class="bi bi-check-circle-fill cc-check"></i> @endif
            </button>
        </form>

        <form method="POST" action="{{ route('language.switch') }}">
            @csrf
            <input type="hidden" name="locale" value="km">
            <button type="submit" class="cc-locale-choice {{ $activeLocale === 'km' ? 'active' : '' }}">
                <span aria-hidden="true" style="font-size:19px">🇰🇭</span>
                <span>ភាសាខ្មែរ</span>
                @if ($activeLocale === 'km') <i class="bi bi-check-circle-fill cc-check"></i> @endif
            </button>
        </form>

        <p class="cc-pref-footnote">
            {{ $activeLocale === 'km' ? 'ភាសាដែលបានជ្រើសរើស នឹងត្រូវបានរក្សាទុកសម្រាប់វគ្គប្រើប្រាស់នេះ។' : 'Your language choice is saved for this browser session across the POS and other workspaces.' }}
        </p>
    </div>
</div>

<script>
    (() => {
        const locale = @json($activeLocale);
        document.documentElement.lang = locale === 'km' ? 'km' : 'en';
        document.documentElement.dataset.khmerDictionary = @json(asset('assets/lang/km-ui.json'));
        const applyLocale = () => document.body?.classList.toggle('locale-km', locale === 'km');
        if (document.body) applyLocale();
        else document.addEventListener('DOMContentLoaded', applyLocale, { once: true });
    })();
</script>
<script src="{{ asset('assets/js/cc-khmer-ui.js') }}?v=20261113-locale-flash-fix" defer onerror="document.documentElement.classList.add('cc-locale-ready')"></script>
