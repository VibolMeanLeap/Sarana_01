@php
    $activeLocale = in_array(app()->getLocale(), ['en', 'km'], true) ? app()->getLocale() : 'en';
@endphp
<section class="settings-card p-4 mb-4" id="language-preferences">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="settings-stat-icon flex-shrink-0"><i class="bi bi-translate"></i></span>
            <div>
                <h2 class="h5 fw-bold mb-1">{{ $activeLocale === 'km' ? 'ភាសាប្រព័ន្ធ' : 'Application language' }}</h2>
                <p class="small text-muted mb-0">{{ $activeLocale === 'km' ? 'ជ្រើសរើសភាសាដែលអ្នកចង់ប្រើក្នុងផ្ទាំងការងាររបស់អ្នក។' : 'Choose the language used in your admin and POS workspaces.' }}</p>
            </div>
        </div>
        <span class="badge rounded-pill text-bg-light">{{ $activeLocale === 'km' ? 'កំពុងប្រើភាសាខ្មែរ' : 'Current: English' }}</span>
    </div>
    <div class="row g-2 mt-3">
        <div class="col-12 col-md-6">
            <form method="POST" action="{{ route('language.switch') }}">
                @csrf
                <input type="hidden" name="locale" value="en">
                <button type="submit" class="btn w-100 d-flex justify-content-between align-items-center rounded-3 {{ $activeLocale === 'en' ? 'btn-success' : 'btn-outline-secondary' }}">
                    <span><span class="me-2">🇬🇧</span>English</span>
                    @if ($activeLocale === 'en') <i class="bi bi-check-circle-fill"></i> @endif
                </button>
            </form>
        </div>
        <div class="col-12 col-md-6">
            <form method="POST" action="{{ route('language.switch') }}">
                @csrf
                <input type="hidden" name="locale" value="km">
                <button type="submit" class="btn w-100 d-flex justify-content-between align-items-center rounded-3 {{ $activeLocale === 'km' ? 'btn-success' : 'btn-outline-secondary' }}">
                    <span><span class="me-2">🇰🇭</span>ភាសាខ្មែរ</span>
                    @if ($activeLocale === 'km') <i class="bi bi-check-circle-fill"></i> @endif
                </button>
            </form>
        </div>
    </div>
    <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>{{ $activeLocale === 'km' ? 'ការជ្រើសរើសនេះអនុវត្តចំពោះវគ្គប្រើប្រាស់របស់អ្នកប៉ុណ្ណោះ មិនប្តូរភាសារបស់អ្នកប្រើប្រាស់ផ្សេងទៀតទេ។' : 'This preference applies to your current browser session. It does not change another staff member’s language.' }}</p>
</section>
