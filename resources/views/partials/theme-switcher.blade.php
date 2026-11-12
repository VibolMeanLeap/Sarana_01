@php($themeLocale = app()->getLocale())
<div class="cc-theme-switcher" data-cc-theme-switcher data-cc-locale="{{ $themeLocale }}">
    <button
        class="cc-theme-trigger"
        type="button"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="{{ $themeLocale === 'km' ? 'ការកំណត់រូបរាង' : 'Appearance settings' }}"
        data-cc-theme-trigger
    >
        <span class="cc-theme-trigger-icon" aria-hidden="true"><i class="bi bi-circle-half"></i></span>
        <span class="cc-theme-trigger-copy">
            <span class="cc-theme-trigger-title">{{ $themeLocale === 'km' ? 'រូបរាង' : 'Appearance' }}</span>
            <span class="cc-theme-trigger-current" data-cc-theme-current>{{ $themeLocale === 'km' ? 'តាមប្រព័ន្ធ' : 'Follow system' }}</span>
        </span>
        <i class="bi bi-chevron-down cc-theme-chevron" aria-hidden="true"></i>
    </button>

    <div class="cc-theme-menu" data-cc-theme-menu role="group" aria-label="{{ $themeLocale === 'km' ? 'ជ្រើសរើសរូបរាង' : 'Choose appearance' }}" hidden>
        <div class="cc-theme-menu-heading">{{ $themeLocale === 'km' ? 'រូបរាងកម្មវិធី' : 'APP APPEARANCE' }}</div>
        <button type="button" class="cc-theme-option" data-cc-theme-mode="light" aria-pressed="false">
            <span class="cc-theme-option-icon"><i class="bi bi-sun"></i></span>
            <span class="cc-theme-option-copy">
                <strong>{{ $themeLocale === 'km' ? 'ពន្លឺ' : 'Light' }}</strong>
                <small>{{ $themeLocale === 'km' ? 'ផ្ទៃភ្លឺស្រទន់' : 'Bright, clean workspace' }}</small>
            </span>
            <i class="bi bi-check2 cc-theme-option-check" aria-hidden="true"></i>
        </button>
        <button type="button" class="cc-theme-option" data-cc-theme-mode="dark" aria-pressed="false">
            <span class="cc-theme-option-icon"><i class="bi bi-moon-stars"></i></span>
            <span class="cc-theme-option-copy">
                <strong>{{ $themeLocale === 'km' ? 'ងងឹត' : 'Dark' }}</strong>
                <small>{{ $themeLocale === 'km' ? 'ស្រួលភ្នែកនៅកន្លែងងងឹត' : 'Comfortable in low light' }}</small>
            </span>
            <i class="bi bi-check2 cc-theme-option-check" aria-hidden="true"></i>
        </button>
        <button type="button" class="cc-theme-option" data-cc-theme-mode="system" aria-pressed="false">
            <span class="cc-theme-option-icon"><i class="bi bi-display"></i></span>
            <span class="cc-theme-option-copy">
                <strong>{{ $themeLocale === 'km' ? 'តាមប្រព័ន្ធ' : 'Follow system' }}</strong>
                <small>{{ $themeLocale === 'km' ? 'ប្រើការកំណត់ឧបករណ៍របស់អ្នក' : 'Match your device preference' }}</small>
            </span>
            <i class="bi bi-check2 cc-theme-option-check" aria-hidden="true"></i>
        </button>
    </div>
    <span class="visually-hidden" data-cc-theme-live aria-live="polite"></span>
</div>
