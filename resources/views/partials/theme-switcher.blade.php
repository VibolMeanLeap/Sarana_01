@php($themeLocale = app()->getLocale())
<div class="cc-theme-switcher cc-theme-switcher--icon" data-cc-theme-switcher data-cc-locale="{{ $themeLocale }}">
    <button
        class="cc-theme-icon-toggle"
        type="button"
        data-cc-theme-toggle
        aria-label="{{ $themeLocale === 'km' ? 'ប្តូររវាងរូបរាងភ្លឺ និងងងឹត' : 'Toggle light and dark theme' }}"
        aria-pressed="false"
        title="{{ $themeLocale === 'km' ? 'ប្តូររូបរាង' : 'Toggle theme' }}"
    >
        <i class="bi bi-moon-stars-fill cc-theme-icon-glyph" data-cc-theme-icon aria-hidden="true"></i>
        <span class="visually-hidden" data-cc-theme-live aria-live="polite"></span>
    </button>
</div>
