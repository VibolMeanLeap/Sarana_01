@php($activeLocale = in_array(app()->getLocale(), ['en', 'km'], true) ? app()->getLocale() : 'en')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Khmer:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    /* Krasa is used where the locally installed font supports the glyphs.
       Noto Sans Khmer / Khmer OS safely render Khmer glyphs when Krasa lacks them. */
    body.locale-km,
    html[lang="km"] body,
    body.locale-km :where(h1,h2,h3,h4,h5,h6,p,a,button,label,th,td,li,small,strong,em,span,div,select,option,input,textarea):not(.bi):not(.fa):not(.mdi):not(.material-icons):not([class*="fa-"]):not([class*="mdi-"]):not([class*="icon"]),
    html[lang="km"] body :where(h1,h2,h3,h4,h5,h6,p,a,button,label,th,td,li,small,strong,em,span,div,select,option,input,textarea):not(.bi):not(.fa):not(.mdi):not(.material-icons):not([class*="fa-"]):not([class*="mdi-"]):not([class*="icon"]) {
        font-family: "Krasa", "Noto Sans Khmer", "Khmer OS System", "Leelawadee UI", sans-serif !important;
        letter-spacing: normal !important;
    }
    body.locale-km { line-height: 1.55; }
    body.locale-km h1, body.locale-km h2, body.locale-km h3,
    body.locale-km h4, body.locale-km h5, html[lang="km"] body h1,
    html[lang="km"] body h2, html[lang="km"] body h3,
    html[lang="km"] body h4, html[lang="km"] body h5 { line-height: 1.35; }
    .cc-language-menu { min-width: 174px; padding: 7px; border: 1px solid #e7eee9; border-radius: 13px; box-shadow: 0 12px 30px rgba(20,55,36,.12); }
    .cc-language-menu button { display:flex; align-items:center; gap:10px; width:100%; border:0; background:transparent; padding:10px 11px; border-radius:9px; text-align:left; color:#34443a; font-size:13px; font-weight:650; }
    .cc-language-menu button:hover { background:#edf6f0; color:#176b4b; }
    .cc-language-menu button.active { background:#e8f4ec; color:#176b4b; }
    .cc-language-menu .cc-lang-check { margin-left:auto; }
</style>
<div class="dropdown cc-language-switcher">
    <button class="btn btn-sm btn-light border rounded-pill d-inline-flex align-items-center gap-2 px-3"
        type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Change language') }}">
        <i class="bi bi-translate text-success"></i>
        <span>{{ $activeLocale === 'km' ? 'ខ្មែរ' : 'English' }}</span>
        <i class="bi bi-chevron-down small text-muted"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end cc-language-menu">
        <form method="POST" action="{{ route('language.switch') }}">
            @csrf
            <input type="hidden" name="locale" value="en">
            <button type="submit" class="{{ $activeLocale === 'en' ? 'active' : '' }}">
                <span aria-hidden="true">🇬🇧</span><span>English</span>
                @if ($activeLocale === 'en') <i class="bi bi-check2 cc-lang-check"></i> @endif
            </button>
        </form>
        <form method="POST" action="{{ route('language.switch') }}">
            @csrf
            <input type="hidden" name="locale" value="km">
            <button type="submit" class="{{ $activeLocale === 'km' ? 'active' : '' }}">
                <span aria-hidden="true">🇰🇭</span><span>ភាសាខ្មែរ</span>
                @if ($activeLocale === 'km') <i class="bi bi-check2 cc-lang-check"></i> @endif
            </button>
        </form>
    </div>
</div>
<script>
    (() => {
        const locale = @json($activeLocale);
        document.documentElement.lang = locale === 'km' ? 'km' : 'en';
        document.documentElement.dataset.khmerDictionary = @json(asset('assets/lang/km-ui.json'));
        const applyLocaleClass = () => document.body?.classList.toggle('locale-km', locale === 'km');
        if (document.body) applyLocaleClass();
        else document.addEventListener('DOMContentLoaded', applyLocaleClass, { once: true });
    })();
</script>
<script src="{{ asset('assets/js/cc-khmer-ui.js') }}" defer></script>
