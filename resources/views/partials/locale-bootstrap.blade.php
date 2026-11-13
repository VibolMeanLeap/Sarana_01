@php
    $ccLocale = in_array(app()->getLocale(), ['en', 'km'], true) ? app()->getLocale() : 'en';
    $ccKhmerUiDictionary = [];

    if ($ccLocale === 'km') {
        $ccDictionaryPath = public_path('assets/lang/km-ui.json');
        if (is_file($ccDictionaryPath) && is_readable($ccDictionaryPath)) {
            $ccDecodedDictionary = json_decode((string) file_get_contents($ccDictionaryPath), true);
            $ccKhmerUiDictionary = is_array($ccDecodedDictionary) ? $ccDecodedDictionary : [];
        }
    }
@endphp

@if ($ccLocale === 'km')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Khmer:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Keep the Khmer page from painting its English source text before the
           synchronous first-pass dictionary translation has completed. */
        html[lang="km"]:not(.cc-locale-ready) body { visibility: hidden !important; }
        html[lang="km"]:not(.cc-locale-ready) { background: #f5f7f5; }
        html[lang="km"] body,
        html[lang="km"] body :where(h1,h2,h3,h4,h5,h6,p,a,button,label,th,td,li,small,strong,em,span,div,select,option,input,textarea):not(.bi):not(.fa):not(.mdi):not(.material-icons):not([class*="fa-"]):not([class*="mdi-"]):not([class*="icon"]) {
            font-family: "Krasa", "Noto Sans Khmer", "Khmer OS System", "Leelawadee UI", sans-serif !important;
            letter-spacing: normal !important;
        }
        html[lang="km"] body { line-height: 1.55; }
        html[lang="km"] body h1, html[lang="km"] body h2, html[lang="km"] body h3,
        html[lang="km"] body h4, html[lang="km"] body h5 { line-height: 1.35; }
    </style>
    <script>
        // Inline dictionary avoids a late fetch and therefore avoids an English-to-Khmer flash.
        window.CC_KHMER_DICTIONARY = @json($ccKhmerUiDictionary, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        // Fail open if the translator asset is blocked, so the page is never left invisible.
        window.setTimeout(function () {
            document.documentElement.classList.add('cc-locale-ready');
        }, 8000);
    </script>
    <noscript><style>html[lang="km"] body { visibility: visible !important; }</style></noscript>
@endif
