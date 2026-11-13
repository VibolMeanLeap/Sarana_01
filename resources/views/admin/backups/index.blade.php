@extends('layout.admin')

@section('page_title', 'Backups & restore')
@section('topbar_title', 'Backups & restore')

@section('content')
    @php
        $humanSize = static function ($bytes) {
            $bytes = (int) $bytes;
            if ($bytes >= 1024 ** 3) {
                return number_format($bytes / 1024 ** 3, 2) . ' GB';
            }
            if ($bytes >= 1024 ** 2) {
                return number_format($bytes / 1024 ** 2, 2) . ' MB';
            }
            return number_format($bytes / 1024, 1) . ' KB';
        };
    @endphp

    <style>
        .backup-hero {
            background: radial-gradient(circle at 92% 8%, rgba(234, 195, 132, .24), transparent 28%), linear-gradient(120deg, #102b21, #1b563b);
            border-radius: 24px;
            color: #fff;
            padding: 28px 30px;
        }

        .backup-card {
            border: 1px solid #e7eee9;
            border-radius: 19px;
            background: #fff;
            box-shadow: 0 8px 26px rgba(21, 53, 35, .045);
            height: 100%;
        }

        .backup-card-header {
            padding: 19px 21px;
            border-bottom: 1px solid #edf1ed;
        }

        .backup-stat {
            padding: 17px;
            border: 1px solid #e7eee9;
            background: #fff;
            border-radius: 17px;
            height: 100%;
        }

        .backup-stat-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: #edf7f0;
            color: #176b4b;
            font-size: 1.15rem;
        }

        .backup-muted {
            color: #718077;
        }

        .backup-table th {
            color: #65736a;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
        }

        .backup-table td {
            vertical-align: middle;
        }

        .backup-file {
            overflow-wrap: anywhere;
            max-width: 320px;
            font-size: .8rem;
        }

        .backup-danger {
            border: 1px solid #f0d7d4;
            background: #fff8f7;
            border-radius: 15px;
        }

        .backup-modal .modal-content {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(12, 35, 23, .24);
        }

        .backup-modal-head {
            color: #fff;
            padding: 24px 26px;
            background: radial-gradient(circle at 90% 0%, rgba(234, 195, 132, .22), transparent 34%), linear-gradient(120deg, #102b21, #1b563b);
        }

        .backup-modal-icon {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .22);
            background: rgba(255, 255, 255, .12);
            border-radius: 16px;
            font-size: 1.35rem;
        }

        .backup-modal-check {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            flex: 0 0 30px;
            border-radius: 10px;
            background: #edf7f0;
            color: #176b4b;
        }

        .backup-modal .modal-footer {
            border-top: 1px solid #edf1ed;
            padding: 17px 24px 22px;
        }

        .backup-action-modal .modal-content {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 28px 80px rgba(12, 35, 23, .28);
        }

        .backup-action-modal .modal-header {
            border: 0;
            color: #fff;
            padding: 23px 25px;
            background: radial-gradient(circle at 92% 0%, rgba(234, 195, 132, .24), transparent 34%), linear-gradient(120deg, #102b21, #1b563b);
        }

        .backup-action-modal .modal-body {
            padding: 25px;
        }

        .backup-action-modal .modal-footer {
            border-top: 1px solid #edf1ed;
            padding: 17px 25px 22px;
        }

        .backup-action-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            border-radius: 17px;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .2);
            font-size: 1.45rem;
        }

        .backup-action-summary {
            border: 1px solid #e7eee9;
            background: #f8fbf9;
            border-radius: 16px;
            padding: 15px;
        }

        .backup-action-warning {
            display: flex;
            gap: 11px;
            align-items: flex-start;
            border: 1px solid #f1d9d5;
            background: #fff8f7;
            border-radius: 14px;
            padding: 13px 14px;
            color: #8c3731;
            font-size: .86rem;
        }

        .backup-phrase {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            letter-spacing: .12em;
            font-weight: 800;
            text-transform: uppercase;
        }

        .backup-table .backup-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            justify-content: flex-end;
        }

        .backup-table .backup-row-actions form {
            display: inline-flex;
            margin: 0;
        }

        .backup-swal-popup {
            border-radius: 20px !important;
            box-shadow: 0 20px 60px rgba(12, 35, 23, .18) !important;
        }

        .backup-swal-toast {
            border-radius: 16px !important;
            box-shadow: 0 14px 38px rgba(12, 35, 23, .16) !important;
        }

        @media(max-width:575px) {
            .backup-hero {
                padding: 22px 19px;
            }

            .backup-modal .modal-footer .btn,
            .backup-action-modal .modal-footer .btn {
                flex: 1 1 100%;
            }

            .backup-action-modal .modal-body {
                padding: 20px;
            }
        }
    </style>

    <div class="backup-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.15em;color:#e9c890"><i
                        class="bi bi-shield-lock me-2"></i>Data protection centre</div>
                <h1 class="h2 fw-bold mb-2">Backups & restore</h1>
                <p class="text-white-50 mb-0" style="max-width:690px">Protect your coffee shop records, products, stock
                    history, orders, settings, product photos and shop logo in one downloadable archive.</p>
            </div>
            <button class="btn btn-light rounded-pill fw-bold px-4 py-2" type="button" data-bs-toggle="modal"
                data-bs-target="#createBackupModal">
                <i class="bi bi-cloud-arrow-down-fill me-2"></i>Create backup now
            </button>
        </div>
    </div>

    <div class="modal fade backup-modal" id="createBackupModal" tabindex="-1" aria-labelledby="createBackupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="backup-modal-head">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="backup-modal-icon"><i class="bi bi-shield-check"></i></div>
                            <div>
                                <div class="small text-uppercase fw-bold mb-1" style="letter-spacing:.12em;color:#e9c890">
                                    Data protection</div>
                                <h2 class="h5 fw-bold mb-0" id="createBackupModalLabel">Create a new backup?</h2>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>
                <form id="createBackupForm" method="POST" action="{{ route('admin.backups.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-secondary mb-3">A backup archive will be created and saved in your application's
                            private storage. You can download it later from Backup history.</p>
                        <div class="d-grid gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="backup-modal-check"><i class="bi bi-database-check"></i></span>
                                <div>
                                    <div class="fw-semibold">MySQL / MariaDB database</div>
                                    <div class="small backup-muted">Products, orders, customers, stock, settings and related
                                        records</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="backup-modal-check"><i class="bi bi-images"></i></span>
                                <div>
                                    <div class="fw-semibold">Shop images and uploaded files</div>
                                    <div class="small backup-muted">Product photos, shop logo and managed storage files
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="backup-modal-check"><i class="bi bi-file-earmark-zip"></i></span>
                                <div>
                                    <div class="fw-semibold">One downloadable ZIP archive</div>
                                    <div class="small backup-muted">Includes a manifest and file integrity checks</div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info border-0 rounded-4 small mt-4 mb-0">
                            <i class="bi bi-info-circle-fill me-2"></i>Keep this page open while the backup is being
                            generated. Large databases or image folders may take longer.
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column flex-sm-row">
                        <button type="button" class="btn btn-light border rounded-pill px-4 fw-semibold"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="confirmCreateBackup" class="btn btn-success rounded-pill px-4 fw-bold">
                            <span class="backup-submit-label"><i class="bi bi-cloud-arrow-down-fill me-2"></i>Create
                                backup</span>
                            <span class="backup-submit-loading d-none"><span class="spinner-border spinner-border-sm me-2"
                                    aria-hidden="true"></span>Creating backup...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="backup-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small backup-muted fw-semibold">Saved backup archives</div>
                        <div class="h3 fw-bold mt-2 mb-0">{{ count($backupFiles) }}</div>
                    </div>
                    <div class="backup-stat-icon"><i class="bi bi-archive-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="backup-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small backup-muted fw-semibold">Total backup storage</div>
                        <div class="h3 fw-bold mt-2 mb-0">{{ $humanSize($totalBytes) }}</div>
                    </div>
                    <div class="backup-stat-icon"><i class="bi bi-device-hdd-fill"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="backup-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="small backup-muted fw-semibold">Most recent backup</div>
                        <div class="h6 fw-bold mt-2 mb-0">
                            {{ $latestBackup ? \Illuminate\Support\Carbon::parse($latestBackup['created_at'])->timezone(config('backup.timezone', 'Asia/Phnom_Penh'))->format('d M Y, H:i') : 'Not created yet' }}
                        </div>
                    </div>
                    <div class="backup-stat-icon"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <section class="backup-card">
                <div class="backup-card-header d-flex align-items-center gap-3">
                    <div class="backup-stat-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                    <div>
                        <h2 class="h6 fw-bold mb-1">Restore from a ZIP file</h2>
                        <div class="small backup-muted">Upload a backup archive created by this system.</div>
                    </div>
                </div>
                <div class="p-4">
                    <form id="uploadBackupRestoreForm" method="POST" action="{{ route('admin.backups.restore-upload') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="restore_confirmation" value="">
                        <div class="mb-3">
                            <label for="backup_file" class="form-label fw-semibold">Backup ZIP file</label>
                            <input class="form-control" type="file" id="backup_file" name="backup_file"
                                accept=".zip,application/zip" required>
                            <div class="form-text">Maximum upload size:
                                {{ number_format((int) config('backup.upload_max_kb', 1024000) / 1024, 0) }} MB. PHP/server
                                upload limits may also apply.</div>
                        </div>
                        <button type="button" id="openUploadRestoreDialog"
                            class="btn btn-danger rounded-pill fw-bold px-4">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Review restore
                        </button>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="backup-card">
                <div class="backup-card-header d-flex align-items-center gap-3">
                    <div class="backup-stat-icon"><i class="bi bi-calendar2-check-fill"></i></div>
                    <div>
                        <h2 class="h6 fw-bold mb-1">Automatic backup schedule</h2>
                        <div class="small backup-muted">Runs through the Laravel scheduler.</div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="d-flex justify-content-between gap-3 mb-3"><span class="backup-muted">Daily backup
                            time</span><strong>{{ config('backup.daily_at', '02:00') }}
                            {{ config('backup.timezone', 'Asia/Phnom_Penh') }}</strong></div>
                    <div class="d-flex justify-content-between gap-3 mb-3"><span class="backup-muted">Retention
                            period</span><strong>{{ (int) config('backup.retention_days', 30) === 0 ? 'Unlimited' : (int) config('backup.retention_days', 30) . ' days' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between gap-3 mb-3"><span class="backup-muted">Backup
                            location</span><strong class="text-end">Private application storage</strong></div>
                    <div class="small backup-muted">Daily backups are created only when the server runs <code>php artisan
                            schedule:run</code>. See <code>BACKUP_RESTORE_INSTALL.md</code> for Windows Task Scheduler and
                        Linux cron setup.</div>
                </div>
            </section>
        </div>
    </div>

    <section class="backup-danger p-3 p-md-4 mb-4">
        <div class="d-flex gap-3">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
            <div>
                <div class="fw-bold text-danger mb-1">Important: restoring replaces current data</div>
                <div class="small text-dark">Restore overwrites the configured MySQL/MariaDB database and the managed
                    folders <code>public/images</code>, <code>public/uploads</code> and <code>storage/app/public</code>. A
                    pre-restore backup is automatically created first. Do not close the browser or stop the server while a
                    restore is running.</div>
            </div>
        </div>
    </section>

    <div class="modal fade backup-action-modal" id="backupActionModal" tabindex="-1"
        aria-labelledby="backupActionModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="backup-action-icon" id="backupActionIcon"><i class="bi bi-shield-exclamation"></i>
                        </div>
                        <div>
                            <div class="small text-uppercase fw-bold mb-1" style="letter-spacing:.12em;color:#e9c890">
                                Administrator protection</div>
                            <h2 class="h5 fw-bold mb-0" id="backupActionModalTitle">Confirm restore</h2>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="backupRestoreDetails">
                        <p class="text-secondary mb-3" id="backupActionDescription">This action replaces the current shop
                            database and managed uploads.</p>
                        <div class="backup-action-summary mb-3">
                            <div class="small text-uppercase fw-bold backup-muted mb-2" style="letter-spacing:.08em">
                                Selected archive</div>
                            <div class="fw-bold text-break mb-2" id="backupActionFilename">—</div>
                            <div class="d-flex flex-wrap gap-3 small backup-muted">
                                <span><i class="bi bi-database me-1"></i><span id="backupActionDatabase">—</span></span>
                                <span><i class="bi bi-hdd me-1"></i><span id="backupActionSize">—</span></span>
                                <span><i class="bi bi-calendar3 me-1"></i><span id="backupActionCreated">—</span></span>
                            </div>
                        </div>
                        <div class="backup-action-warning mb-3">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <div><strong>This is a destructive operation.</strong> Current records and managed upload
                                folders will be replaced. A safety backup of the current system is created first.</div>
                        </div>
                        <label for="backupActionPhrase" class="form-label fw-semibold">Enter the word <span
                                class="backup-phrase">RESTORE</span> to authorize</label>
                        <input type="text" id="backupActionPhrase" class="form-control form-control-lg backup-phrase"
                            maxlength="7" autocomplete="off" spellcheck="false" placeholder="RESTORE">
                        <div class="form-text">The restore starts only when the phrase matches exactly.</div>
                    </div>
                    <div id="backupDeleteDetails" class="d-none">
                        <p class="text-secondary mb-3">You are about to permanently delete this backup archive. This cannot
                            be undone.</p>
                        <div class="backup-action-summary">
                            <div class="small text-uppercase fw-bold backup-muted mb-2" style="letter-spacing:.08em">
                                Archive to delete</div>
                            <div class="fw-bold text-break" id="backupDeleteFilename">—</div>
                        </div>
                    </div>
                    <div id="backupActionBusy" class="d-none text-center py-4" aria-live="polite">
                        <div class="spinner-border text-success mb-3" role="status"><span
                                class="visually-hidden">Working...</span></div>
                        <div class="fw-bold fs-5" id="backupActionBusyTitle">Restoring backup...</div>
                        <p class="small backup-muted mb-0 mt-2">Keep this page open. Large databases and image folders may
                            take several minutes.</p>
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row" id="backupActionFooter">
                    <button type="button" class="btn btn-light border rounded-pill px-4 fw-semibold"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" id="confirmBackupAction"
                        disabled>
                        <span id="backupActionButtonLabel"><i class="bi bi-arrow-counterclockwise me-2"></i>Confirm
                            restore</span>
                        <span id="backupActionButtonBusy" class="d-none"><span
                                class="spinner-border spinner-border-sm me-2"
                                aria-hidden="true"></span>Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <section class="backup-card">
        <div class="backup-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="h6 fw-bold mb-1">Backup history</h2>
                <div class="small backup-muted">Archives are stored outside the public web directory.</div>
            </div>
            <span class="badge rounded-pill text-bg-light border">{{ count($backupFiles) }} file(s)</span>
        </div>
        @if (count($backupFiles) > 0)
            <div class="table-responsive">
                <table class="table backup-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Created</th>
                            <th>Type</th>
                            <th>Archive</th>
                            <th>Size</th>
                            <th>Files</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backupFiles as $backup)
                            <tr>
                                <td class="ps-4 text-nowrap">
                                    <div class="fw-semibold">
                                        {{ \Illuminate\Support\Carbon::parse($backup['created_at'])->timezone(config('backup.timezone', 'Asia/Phnom_Penh'))->format('d M Y') }}
                                    </div>
                                    <div class="small backup-muted">
                                        {{ \Illuminate\Support\Carbon::parse($backup['created_at'])->timezone(config('backup.timezone', 'Asia/Phnom_Penh'))->format('H:i:s') }}
                                    </div>
                                </td>
                                <td><span
                                        class="badge rounded-pill {{ $backup['type'] === 'pre_restore' ? 'text-bg-warning' : ($backup['type'] === 'scheduled' ? 'text-bg-info' : 'text-bg-light border text-dark') }}">{{ str_replace('_', ' ', ucfirst($backup['type'])) }}</span>
                                </td>
                                <td>
                                    <div class="backup-file fw-semibold">{{ $backup['filename'] }}</div>
                                    <div class="small backup-muted">
                                        {{ $backup['database'] ? 'Database: ' . $backup['database'] : 'Manifest unavailable' }}
                                    </div>
                                </td>
                                <td class="text-nowrap">{{ $humanSize($backup['size']) }}</td>
                                <td>{{ $backup['file_count'] === null ? '—' : $backup['file_count'] }}</td>
                                <td><span
                                        class="badge rounded-pill {{ $backup['status'] === 'Ready' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $backup['status'] }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="backup-row-actions">
                                        <a class="btn btn-sm btn-outline-success rounded-pill"
                                            href="{{ route('admin.backups.download', $backup['filename']) }}"><i
                                                class="bi bi-download me-1"></i>Download</a>
                                        @if ($backup['status'] === 'Ready')
                                            <form method="POST"
                                                action="{{ route('admin.backups.restore', $backup['filename']) }}"
                                                class="backup-restore-form">
                                                @csrf
                                                <input type="hidden" name="restore_confirmation" value="">
                                                <button
                                                    class="btn btn-sm btn-outline-danger rounded-pill js-open-backup-restore"
                                                    type="button" data-backup-name="{{ $backup['filename'] }}"
                                                    data-backup-database="{{ $backup['database'] ?: 'Database details unavailable' }}"
                                                    data-backup-size="{{ $humanSize($backup['size']) }}"
                                                    data-backup-created="{{ \Illuminate\Support\Carbon::parse($backup['created_at'])->timezone(config('backup.timezone', 'Asia/Phnom_Penh'))->format('d M Y, H:i') }}">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST"
                                            action="{{ route('admin.backups.destroy', $backup['filename']) }}"
                                            class="backup-delete-form">
                                            @csrf @method('DELETE')
                                            <button
                                                class="btn btn-sm btn-outline-secondary rounded-pill js-open-backup-delete"
                                                type="button" data-backup-name="{{ $backup['filename'] }}"
                                                aria-label="Delete backup" title="Delete backup"><i
                                                    class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-5 text-center">
                <div class="backup-stat-icon mx-auto mb-3"><i class="bi bi-archive"></i></div>
                <h3 class="h6 fw-bold">No backup archives yet</h3>
                <p class="small backup-muted mb-0">Create your first full backup to protect your database and uploaded
                    images.</p>
            </div>
        @endif
        <div class="border-top p-3 small backup-muted"><i class="bi bi-lock-fill me-1 text-success"></i>Only active
            administrator accounts can open this page, download archives, or restore data.</div>
    </section>
@endsection


@section('scripts')
    <script>
        (() => {
            const createForm = document.getElementById('createBackupForm');
            const createButton = document.getElementById('confirmCreateBackup');
            if (createForm && createButton) {
                createForm.addEventListener('submit', () => {
                    if (createButton.disabled) return;
                    createButton.disabled = true;
                    createButton.querySelector('.backup-submit-label')?.classList.add('d-none');
                    createButton.querySelector('.backup-submit-loading')?.classList.remove('d-none');
                });
            }

            const modalElement = document.getElementById('backupActionModal');
            if (!modalElement || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const phraseInput = document.getElementById('backupActionPhrase');
            const confirmButton = document.getElementById('confirmBackupAction');
            const restoreDetails = document.getElementById('backupRestoreDetails');
            const deleteDetails = document.getElementById('backupDeleteDetails');
            const busyPanel = document.getElementById('backupActionBusy');
            const modalFooter = document.getElementById('backupActionFooter');
            let activeForm = null;
            let activeMode = null;
            let busy = false;

            const configureModal = (mode, form, details = {}) => {
                activeForm = form;
                activeMode = mode;
                busy = false;
                modalElement.dataset.mode = mode;
                document.getElementById('backupActionModalTitle').textContent = mode === 'delete' ?
                    'Delete backup archive?' : 'Restore this backup?';
                document.getElementById('backupActionIcon').innerHTML = mode === 'delete' ?
                    '<i class="bi bi-trash3"></i>' :
                    '<i class="bi bi-shield-exclamation"></i>';
                document.getElementById('backupActionButtonLabel').innerHTML = mode === 'delete' ?
                    '<i class="bi bi-trash3 me-2"></i>Delete permanently' :
                    '<i class="bi bi-arrow-counterclockwise me-2"></i>Confirm restore';
                document.getElementById('backupActionDescription').textContent = mode === 'upload' ?
                    'The uploaded archive will replace the current shop database and managed upload folders.' :
                    'The selected archive will replace the current shop database and managed upload folders.';
                restoreDetails.classList.toggle('d-none', mode === 'delete');
                deleteDetails.classList.toggle('d-none', mode !== 'delete');
                busyPanel.classList.add('d-none');
                modalFooter.classList.remove('d-none');
                document.getElementById('backupActionFilename').textContent = details.filename ||
                    'Uploaded ZIP archive';
                document.getElementById('backupActionDatabase').textContent = details.database || (mode ===
                    'upload' ? 'Read from archive manifest' : 'Database details unavailable');
                document.getElementById('backupActionSize').textContent = details.size || (mode === 'upload' ?
                    'Uploaded file' : '—');
                document.getElementById('backupActionCreated').textContent = details.created || '—';
                document.getElementById('backupDeleteFilename').textContent = details.filename || '—';
                phraseInput.value = '';
                phraseInput.classList.remove('is-valid', 'is-invalid');
                confirmButton.disabled = mode !== 'delete';
                confirmButton.classList.toggle('btn-danger', mode === 'delete');
                confirmButton.classList.toggle('btn-success', mode !== 'delete');
                document.getElementById('backupActionButtonBusy').classList.add('d-none');
                document.getElementById('backupActionButtonLabel').classList.remove('d-none');
                modal.show();
            };

            document.querySelectorAll('.js-open-backup-restore').forEach((button) => {
                button.addEventListener('click', () => {
                    const form = button.closest('form');
                    configureModal('restore', form, {
                        filename: button.dataset.backupName,
                        database: button.dataset.backupDatabase,
                        size: button.dataset.backupSize,
                        created: button.dataset.backupCreated,
                    });
                });
            });

            document.querySelectorAll('.js-open-backup-delete').forEach((button) => {
                button.addEventListener('click', () => configureModal('delete', button.closest('form'), {
                    filename: button.dataset.backupName,
                }));
            });

            const uploadForm = document.getElementById('uploadBackupRestoreForm');
            const uploadButton = document.getElementById('openUploadRestoreDialog');
            if (uploadForm && uploadButton) {
                uploadButton.addEventListener('click', () => {
                    const fileInput = uploadForm.querySelector('input[type="file"]');
                    if (!fileInput?.files?.length) {
                        fileInput?.reportValidity();
                        return;
                    }
                    const file = fileInput.files[0];
                    configureModal('upload', uploadForm, {
                        filename: file.name,
                        database: 'Read from archive manifest',
                        size: file.size ? `${(file.size / 1024 / 1024).toFixed(2)} MB` :
                            'Uploaded file',
                    });
                });
            }

            phraseInput.addEventListener('input', () => {
                const isValid = phraseInput.value === 'RESTORE';
                phraseInput.classList.toggle('is-valid', isValid);
                phraseInput.classList.toggle('is-invalid', phraseInput.value.length > 0 && !isValid);
                confirmButton.disabled = activeMode !== 'delete' && !isValid;
            });

            confirmButton.addEventListener('click', () => {
                if (!activeForm || busy) return;
                if (activeMode !== 'delete' && phraseInput.value !== 'RESTORE') {
                    phraseInput.focus();
                    return;
                }

                if (activeMode !== 'delete') {
                    const hiddenConfirmation = activeForm.querySelector('[name="restore_confirmation"]');
                    if (hiddenConfirmation) hiddenConfirmation.value = 'RESTORE';
                }

                busy = true;
                confirmButton.disabled = true;
                document.getElementById('backupActionButtonLabel').classList.add('d-none');
                document.getElementById('backupActionButtonBusy').classList.remove('d-none');
                document.getElementById('backupActionBusyTitle').textContent = activeMode === 'delete' ?
                    'Deleting backup archive...' :
                    'Restoring backup...';
                restoreDetails.classList.add('d-none');
                deleteDetails.classList.add('d-none');
                busyPanel.classList.remove('d-none');
                modalFooter.classList.add('d-none');
                activeForm.requestSubmit();
            });

            modalElement.addEventListener('hide.bs.modal', (event) => {
                if (busy) event.preventDefault();
            });

            const flash = {
                type: @json(session()->has('success') ? 'success' : (session()->has('error') ? 'error' : '')),
                message: @json(session('success') ?? (session('error') ?? '')),
                errors: @json($errors->all()),
            };

            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 6500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'backup-swal-toast'
                    },
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    },
                });

                if (flash.message) {
                    Toast.fire({
                        icon: flash.type === 'success' ? 'success' : 'error',
                        title: flash.type === 'success' ? 'Backup operation completed' :
                            'Backup operation failed',
                        text: flash.message,
                    });
                } else if (flash.errors.length) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Please check the restore details',
                        text: flash.errors.join(' ')
                    });
                }
            }
        })();
    </script>
@endsection
