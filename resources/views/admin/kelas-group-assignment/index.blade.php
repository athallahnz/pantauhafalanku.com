@extends('layouts.app')

@section('title', 'Assignment Kelas Kelompok')

@section('content')
    <style>
        .assignment-hero {
            position: relative;
            overflow: hidden;
            border: 0;
            border-radius: 1.35rem;
            color: #fff;
            background:
                radial-gradient(circle at 88% 16%, rgba(255, 255, 255, .20), transparent 22%),
                linear-gradient(135deg,
                    var(--islamic-purple-700, #59359d),
                    var(--islamic-purple-600, #6f42c1));
            box-shadow: 0 1rem 2.5rem rgba(89, 53, 157, .20);
        }

        .assignment-hero::after {
            content: '';
            position: absolute;
            right: -75px;
            bottom: -105px;
            width: 260px;
            height: 260px;
            border: 42px solid rgba(255, 255, 255, .08);
            border-radius: 50%;
        }

        .assignment-hero > * {
            position: relative;
            z-index: 1;
        }

        .assignment-card {
            border: 1px solid var(--cui-border-color);
            border-radius: 1rem;
            background: var(--cui-card-bg, var(--cui-body-bg));
        }

        .assignment-stat {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 1rem;
            background: var(--cui-card-bg, var(--cui-body-bg));
        }

        .assignment-stat-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .9rem;
            font-size: 1.1rem;
        }

        .assignment-map-row + .assignment-map-row {
            border-top: 1px solid var(--cui-border-color);
        }

        .assignment-arrow {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--cui-primary);
            background: var(--cui-primary-bg-subtle);
        }

        .assignment-status {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .35rem .7rem;
            font-size: .72rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .assignment-table th {
            color: var(--cui-secondary-color);
            font-size: .72rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .assignment-note {
            border: 1px dashed rgba(13, 110, 253, .30);
            border-radius: .9rem;
            background: rgba(13, 110, 253, .06);
        }
    </style>

    @php
        $summary = $state['summary'];
        $activeSemester = $state['semester'];

        $statusClass = static fn (string $status): string => match ($status) {
            'previewed' => 'text-bg-primary',
            'executed' => 'text-bg-success',
            'rolled_back' => 'text-bg-secondary',
            'blocked' => 'text-bg-danger',
            default => 'text-bg-light',
        };

        $statusIcon = static fn (string $status): string => match ($status) {
            'previewed' => 'bi-eye-fill',
            'executed' => 'bi-check-circle-fill',
            'rolled_back' => 'bi-arrow-counterclockwise',
            'blocked' => 'bi-x-octagon-fill',
            default => 'bi-circle',
        };
    @endphp

    <section class="assignment-hero p-4 p-lg-5 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="small text-white-50 text-uppercase fw-bold mb-2">
                    Tahap 4 · Migrasi Struktur Akademik
                </div>
                <h2 class="fw-bold mb-2">Assignment Kelas Induk ke Kelompok</h2>
                <p class="mb-0 text-white-75">
                    Pindahkan current projection santri, musyrif, dan placement semester aktif
                    ke kelas kelompok melalui preview, checksum, transaksi, audit, dan rollback aman.
                </p>
            </div>

            <div class="col-lg-4">
                <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-25">
                    <div class="small text-white-50 mb-1">Semester aktif</div>
                    <div class="fw-bold fs-5">
                        @if ($activeSemester)
                            {{ ucfirst($activeSemester->nama) }}
                            · {{ $activeSemester->tahunAjaran?->nama ?? '-' }}
                        @else
                            Tidak ditemukan
                        @endif
                    </div>
                    <div class="small text-white-50 mt-1">
                        Hanya placement semester ini yang disinkronkan.
                    </div>
                </div>
            </div>
        </div>
    </section>

    @foreach (['success', 'warning', 'error'] as $flashType)
        @if (session($flashType))
            <div class="alert alert-{{ $flashType === 'error' ? 'danger' : $flashType }} alert-dismissible fade show rounded-4"
                role="alert">
                {{ session($flashType) }}
                <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger rounded-4">
            <div class="fw-bold mb-2">
                <i class="bi bi-exclamation-octagon-fill me-1"></i>
                Proses belum dapat dilanjutkan
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="assignment-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="assignment-stat-icon bg-primary-subtle text-primary">
                        <i class="bi bi-people-fill"></i>
                    </span>
                    <div>
                        <div class="small text-body-secondary">Santri di Parent</div>
                        <div class="fs-3 fw-bold">{{ number_format($summary['santri_legacy']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="assignment-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="assignment-stat-icon bg-warning-subtle text-warning">
                        <i class="bi bi-person-badge-fill"></i>
                    </span>
                    <div>
                        <div class="small text-body-secondary">Musyrif di Parent</div>
                        <div class="fs-3 fw-bold">{{ number_format($summary['musyrif_legacy']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="assignment-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="assignment-stat-icon bg-info-subtle text-info">
                        <i class="bi bi-calendar2-check-fill"></i>
                    </span>
                    <div>
                        <div class="small text-body-secondary">Placement di Parent</div>
                        <div class="fs-3 fw-bold">{{ number_format($summary['placement_legacy']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="assignment-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="assignment-stat-icon bg-danger-subtle text-danger">
                        <i class="bi bi-calendar2-x-fill"></i>
                    </span>
                    <div>
                        <div class="small text-body-secondary">Placement Belum Ada</div>
                        <div class="fs-3 fw-bold">{{ number_format($summary['missing_placements']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($state['blockers']))
        <div class="alert alert-danger rounded-4 mb-4">
            <div class="fw-bold mb-2">
                <i class="bi bi-shield-x me-1"></i>
                Blocker konfigurasi
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($state['blockers'] as $blocker)
                    <li>{{ $blocker }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="assignment-card h-100">
                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h5 class="fw-bold mb-1">Peta Assignment</h5>
                            <div class="small text-body-secondary">
                                Target dibaca dari <code>config/kelas_group_assignment.php</code>.
                            </div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill">
                            {{ count($state['mapping']) }} kelas induk
                        </span>
                    </div>
                </div>

                <div>
                    @foreach ($state['mapping'] as $row)
                        <div class="assignment-map-row p-3 p-lg-4">
                            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="small text-body-secondary">Kelas induk</div>
                                    <div class="fw-bold">{{ $row['parent_name'] }}</div>
                                    <div class="small text-body-secondary">
                                        {{ $row['parent_code'] ?: 'Tanpa kode' }}
                                    </div>
                                </div>

                                <span class="assignment-arrow">
                                    <i class="bi bi-arrow-right"></i>
                                </span>

                                <div class="flex-grow-1 min-w-0">
                                    <div class="small text-body-secondary">Target kelompok</div>
                                    @if ($row['target_id'])
                                        <div class="fw-bold text-success">{{ $row['target_name'] }}</div>
                                        <div class="small text-body-secondary">
                                            {{ $row['target_code'] }} · Kelompok {{ $row['target_group'] }}
                                        </div>
                                    @else
                                        <div class="fw-bold text-danger">Belum dipetakan</div>
                                    @endif
                                </div>

                                <div class="text-md-end">
                                    <div class="small text-body-secondary">Assignment aktif</div>
                                    <div class="fw-bold">{{ number_format($row['assignment_count']) }}</div>
                                    <div class="small text-body-secondary">
                                        S {{ $row['counts']['santri'] }}
                                        · M {{ $row['counts']['musyrif'] }}
                                        · P {{ $row['counts']['placement'] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="assignment-card mb-4">
                <div class="p-4 border-bottom">
                    <h5 class="fw-bold mb-1">Buat Preview</h5>
                    <div class="small text-body-secondary">
                        Preview hanya membuat audit batch. Database assignment belum diubah.
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.kelas-group-assignment.preview') }}" class="p-4">
                    @csrf

                    <div class="mb-3">
                        <label for="semester_id" class="form-label fw-semibold">Semester Aktif</label>
                        <select name="semester_id" id="semester_id" class="form-select" required>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}"
                                    @selected($activeSemester?->id === $semester->id)
                                    @disabled(!$semester->is_active && $semester->status !== 'active')>
                                    {{ ucfirst($semester->nama) }}
                                    · {{ $semester->tahunAjaran?->nama ?? '-' }}
                                    · {{ $semester->status }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="note" class="form-label fw-semibold">Catatan</label>
                        <textarea name="note" id="note" rows="3" class="form-control"
                            placeholder="Contoh: Migrasi awal seluruh kelas induk ke kelompok A.">{{ old('note') }}</textarea>
                    </div>

                    <div class="assignment-note p-3 mb-3 small">
                        <div class="fw-bold mb-1">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            Pemeriksaan otomatis
                        </div>
                        Sistem memvalidasi target child aktif, kesesuaian musyrif,
                        placement semester aktif, checksum state, dan kemungkinan rollback.
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold"
                        @disabled(!$activeSemester)>
                        <i class="bi bi-eye-fill me-1"></i>
                        Buat Preview & Audit Batch
                    </button>
                </form>
            </div>

            <div class="assignment-card">
                <div class="p-4">
                    <h6 class="fw-bold mb-2">Urutan aman</h6>
                    <ol class="small text-body-secondary mb-0 ps-3">
                        <li class="mb-1">Buat preview.</li>
                        <li class="mb-1">Pastikan status <strong>previewed</strong>.</li>
                        <li class="mb-1">Eksekusi batch yang sama.</li>
                        <li>Jalankan SQL verifikasi setelah selesai.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="assignment-card mt-4">
        <div class="p-4 border-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Riwayat Batch</h5>
                    <div class="small text-body-secondary">
                        Preview, eksekusi, dan rollback tersimpan sebagai audit terpisah.
                    </div>
                </div>
                <code>php artisan kelas:assignment-status</code>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table assignment-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Batch</th>
                        <th>Status</th>
                        <th>Semester</th>
                        <th>Item</th>
                        <th>Blocker</th>
                        <th>Dibuat</th>
                        <th>Pelaksana</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        @php
                            $batchSummary = $batch->summary ?? [];
                            $batchBlockers = data_get($batch->metadata, 'blockers', []);
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $batch->code }}</div>
                                <div class="small text-body-secondary font-monospace">
                                    {{ $batch->id }}
                                </div>
                            </td>
                            <td>
                                <span class="assignment-status {{ $statusClass($batch->status) }}">
                                    <i class="bi {{ $statusIcon($batch->status) }}"></i>
                                    {{ str_replace('_', ' ', $batch->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ ucfirst($batch->semester?->nama ?? '-') }}</div>
                                <div class="small text-body-secondary">
                                    {{ $batch->semester?->tahunAjaran?->nama ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ number_format($batch->items_count) }}</div>
                                <div class="small text-body-secondary">
                                    S {{ data_get($batchSummary, 'items.santri', 0) }}
                                    · M {{ data_get($batchSummary, 'items.musyrif', 0) }}
                                    · P {{ data_get($batchSummary, 'items.placement', 0) }}
                                </div>
                            </td>
                            <td>
                                @if (count($batchBlockers) > 0)
                                    <span class="badge text-bg-danger">{{ count($batchBlockers) }}</span>
                                    <details class="small mt-1">
                                        <summary>Lihat</summary>
                                        <ul class="ps-3 mt-2 mb-0">
                                            @foreach ($batchBlockers as $blocker)
                                                <li>{{ $blocker }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @else
                                    <span class="text-success">
                                        <i class="bi bi-check-circle-fill"></i> 0
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $batch->created_at?->format('d M Y H:i') ?? '-' }}</div>
                                <div class="small text-body-secondary">
                                    {{ $batch->createdBy?->name ?? 'CLI / sistem' }}
                                </div>
                            </td>
                            <td>
                                @if ($batch->status === 'executed')
                                    {{ $batch->executedBy?->name ?? 'CLI / sistem' }}
                                @elseif ($batch->status === 'rolled_back')
                                    {{ $batch->rolledBackBy?->name ?? 'CLI / sistem' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if ($batch->canExecute())
                                    <form method="POST"
                                        action="{{ route('admin.kelas-group-assignment.execute', $batch) }}"
                                        class="d-inline assignment-confirm-form"
                                        data-confirm="Eksekusi {{ $batch->code }}? Seluruh assignment akan diubah dalam satu transaction.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-play-fill me-1"></i> Eksekusi
                                        </button>
                                    </form>
                                @elseif ($batch->canRollback())
                                    <form method="POST"
                                        action="{{ route('admin.kelas-group-assignment.rollback', $batch) }}"
                                        class="d-inline assignment-confirm-form"
                                        data-confirm="Rollback {{ $batch->code }}? Proses hanya berhasil bila assignment belum berubah setelah eksekusi.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Rollback
                                        </button>
                                    </form>
                                @else
                                    <span class="text-body-secondary small">Tidak ada aksi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-body-secondary py-5">
                                Belum ada batch assignment.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.assignment-confirm-form').forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    const message = form.dataset.confirm || 'Lanjutkan proses ini?';

                    if (!window.confirm(message)) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>
@endpush
