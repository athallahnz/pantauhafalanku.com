@extends('layouts.app')

@section('title', 'Review Sistem')

@section('content')
    <style>
        :root,
        [data-coreui-theme="light"] {
            --review-surface: #ffffff;
            --review-soft: #f7f8fc;
            --review-text: #202436;
            --review-muted: #707586;
            --review-border: rgba(23, 26, 42, .09);
            --review-purple: var(--islamic-purple-600, #6b4eff);
            --review-purple-dark: var(--islamic-purple-700, #5638d8);
            --review-shadow: 0 9px 28px rgba(27, 32, 56, .07);
        }

        [data-coreui-theme="dark"] {
            --review-surface: #20212b;
            --review-soft: #292b36;
            --review-text: #f2f3f7;
            --review-muted: #a8adbc;
            --review-border: rgba(255, 255, 255, .09);
            --review-purple: #aa9cff;
            --review-purple-dark: #8b79ff;
            --review-shadow: 0 14px 36px rgba(0, 0, 0, .26);
        }

        .review-admin-page {
            color: var(--review-text);
            padding-bottom: 2rem;
        }

        .review-admin-hero {
            position: relative;
            overflow: hidden;
            padding: clamp(1.4rem, 3vw, 2rem);
            border-radius: 25px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 10%, rgba(255, 255, 255, .20), transparent 28%),
                linear-gradient(135deg, #433280 0%, #1599aa 100%);
            box-shadow: 0 20px 48px rgba(72, 58, 171, .22);
        }

        .review-admin-hero::after {
            content: '';
            position: absolute;
            right: -80px;
            bottom: -110px;
            width: 230px;
            height: 230px;
            border: 34px solid rgba(255, 255, 255, .055);
            border-radius: 50%;
        }

        .review-admin-hero > * {
            position: relative;
            z-index: 1;
        }

        .review-admin-card {
            border: 1px solid var(--review-border);
            border-radius: 20px;
            background: var(--review-surface);
            box-shadow: var(--review-shadow);
        }

        .review-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 125px;
            padding: 1rem;
        }

        .review-stat-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: var(--stat-color, var(--review-purple));
        }

        .review-stat-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            color: var(--stat-color, var(--review-purple));
            background: var(--stat-soft, rgba(107, 78, 255, .11));
        }

        .review-stat-label {
            color: var(--review-muted);
            font-size: .65rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .review-stat-value {
            margin-top: .7rem;
            color: var(--review-text);
            font-size: 1.8rem;
            font-weight: 850;
            line-height: 1;
        }

        .review-filter-card {
            padding: 1rem;
        }

        .review-filter-card .form-select {
            min-height: 42px;
            border-color: var(--review-border);
            border-radius: 11px;
            background: var(--review-soft);
            color: var(--review-text);
        }

        .review-table-card {
            overflow: hidden;
        }

        .review-table-card .card-header {
            padding: 1.05rem 1.2rem;
            border-bottom: 1px solid var(--review-border);
            background: transparent;
        }

        .review-table-card .table > :not(caption) > * > * {
            padding-top: .9rem;
            padding-bottom: .9rem;
            border-bottom-color: var(--review-border);
            vertical-align: middle;
        }

        .review-table-card thead th {
            color: var(--review-muted);
            background: var(--review-soft);
            font-size: .64rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .review-content-cell {
            min-width: 280px;
            max-width: 480px;
            line-height: 1.55;
        }

        .dataTables_wrapper > .row:first-child {
            padding: .95rem 1rem .75rem;
            margin: 0;
            border-bottom: 1px solid var(--review-border);
        }

        .dataTables_wrapper > .row:last-child {
            padding: .85rem 1rem 1rem;
            margin: 0;
            border-top: 1px solid var(--review-border);
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            min-height: 38px;
            border: 1px solid var(--review-border);
            border-radius: 10px;
            background: var(--review-soft);
            color: var(--review-text);
        }

        .dataTables_wrapper .dataTables_filter input {
            min-width: 240px;
            padding: .42rem .7rem;
        }

        @media (max-width: 767.98px) {
            .dataTables_wrapper .dataTables_filter input {
                min-width: 0;
                width: 100%;
            }
        }
    </style>

    <div class="review-admin-page">
        <section class="review-admin-hero mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
                <div>
                    <span class="badge bg-white bg-opacity-10 border border-white border-opacity-10 rounded-pill px-3 py-2 mb-3">
                        <i class="bi bi-chat-square-heart-fill me-1"></i>
                        Public Review Governance
                    </span>
                    <h3 class="fw-bold text-white mb-2">Review Sistem</h3>
                    <p class="text-white-50 mb-0" style="max-width: 720px;">
                        Moderasi review dari Musyrif sebelum ditampilkan pada landing page publik.
                        Review baru selalu masuk sebagai pending.
                    </p>
                </div>

                <a href="{{ route('welcome') }}#review"
                    target="_blank"
                    class="btn btn-light rounded-pill px-4 fw-bold flex-shrink-0">
                    <i class="bi bi-box-arrow-up-right me-1"></i>
                    Lihat Landing Page
                </a>
            </div>
        </section>

        <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-5">
            @foreach ([
                ['label' => 'Total Review', 'value' => $summary['total'], 'icon' => 'bi-chat-left-text-fill', 'color' => '#6b4eff', 'soft' => 'rgba(107, 78, 255, .11)'],
                ['label' => 'Pending', 'value' => $summary['pending'], 'icon' => 'bi-clock-history', 'color' => '#c98000', 'soft' => 'rgba(255, 193, 7, .16)'],
                ['label' => 'Published', 'value' => $summary['published'], 'icon' => 'bi-eye-fill', 'color' => '#198754', 'soft' => 'rgba(25, 135, 84, .11)'],
                ['label' => 'Hidden', 'value' => $summary['hidden'], 'icon' => 'bi-eye-slash-fill', 'color' => '#687083', 'soft' => 'rgba(104, 112, 131, .12)'],
                ['label' => 'Rating Rata-rata', 'value' => number_format($summary['average_rating'], 1, ',', '.'), 'icon' => 'bi-star-fill', 'color' => '#d98b00', 'soft' => 'rgba(255, 193, 7, .16)'],
            ] as $stat)
                <div class="col">
                    <div class="review-admin-card review-stat-card"
                        style="--stat-color: {{ $stat['color'] }}; --stat-soft: {{ $stat['soft'] }};">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="review-stat-label">{{ $stat['label'] }}</div>
                                <div class="review-stat-value">{{ $stat['value'] }}</div>
                            </div>
                            <span class="review-stat-icon">
                                <i class="bi {{ $stat['icon'] }}"></i>
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="review-admin-card review-filter-card mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Status</label>
                    <select class="form-select" id="filterReviewStatus">
                        <option value="">Semua status</option>
                        <option value="pending">Pending</option>
                        <option value="published">Published</option>
                        <option value="hidden">Hidden</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Rating</label>
                    <select class="form-select" id="filterReviewRating">
                        <option value="">Semua rating</option>
                        @for ($rating = 5; $rating >= 1; $rating--)
                            <option value="{{ $rating }}">{{ $rating }} bintang</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-4 d-grid">
                    <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold"
                        id="btnResetReviewFilter">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <section class="review-admin-card review-table-card">
            <div class="card-header">
                <div class="fw-bold">
                    <i class="bi bi-shield-check text-primary me-2"></i>
                    Daftar Review Musyrif
                </div>
                <div class="small text-muted mt-1">
                    Publish menampilkan review ke publik. Hidden menghapusnya dari landing page tanpa menghapus data.
                </div>
            </div>

            <div class="table-responsive">
                <table id="system-review-table" class="table table-hover align-middle w-100 mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">No.</th>
                            <th>Reviewer</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Status</th>
                            <th>Dikirim</th>
                            <th>Moderasi</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const table = $('#system-review-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                autoWidth: false,
                ajax: {
                    url: @json(route('superadmin.system-reviews.data')),
                    data: function(data) {
                        data.status = $('#filterReviewStatus').val();
                        data.rating = $('#filterReviewRating').val();
                    }
                },
                columns: [
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'reviewer',
                        name: 'display_name'
                    },
                    {
                        data: 'rating_html',
                        name: 'rating',
                        className: 'text-nowrap'
                    },
                    {
                        data: 'review_content',
                        name: 'review',
                        orderable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        className: 'text-nowrap'
                    },
                    {
                        data: 'submitted_at',
                        name: 'created_at',
                        className: 'text-nowrap'
                    },
                    {
                        data: 'moderation',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
                ],
                order: [[5, 'desc']],
                language: {
                    processing: '<span class="spinner-border spinner-border-sm me-2"></span>Memuat review...',
                    search: '',
                    searchPlaceholder: 'Cari nama, email, atau isi review...',
                    lengthMenu: 'Tampilkan _MENU_',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ review',
                    infoEmpty: 'Belum ada review',
                    zeroRecords: 'Review tidak ditemukan',
                    emptyTable: 'Belum ada review yang dikirim',
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                }
            });

            $('#filterReviewStatus, #filterReviewRating').on('change', function() {
                table.ajax.reload();
            });

            $('#btnResetReviewFilter').on('click', function() {
                $('#filterReviewStatus, #filterReviewRating').val('');
                table.ajax.reload();
            });

            $(document).on('click', '.btn-review-visibility', function() {
                const button = $(this);
                const reviewId = button.data('id');
                const status = button.data('status');
                const label = button.data('label');
                const isPublish = status === 'published';

                const message = isPublish
                    ? `Review dari ${label} akan tampil di landing page publik.`
                    : `Review dari ${label} akan disembunyikan dari landing page.`;

                const execute = function() {
                    const originalHtml = button.html();
                    button.prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm"></span>');

                    $.ajax({
                        url: @json(url('/superadmin/system-reviews')) +
                            `/${reviewId}/visibility`,
                        type: 'POST',
                        data: {
                            _method: 'PATCH',
                            _token: csrfToken,
                            status: status
                        }
                    })
                    .done(function(response) {
                        table.ajax.reload(null, false);

                        if (window.AppAlert?.success) {
                            AppAlert.success(response.message);
                        } else {
                            window.alert(response.message);
                        }
                    })
                    .fail(function(xhr) {
                        const errorMessage =
                            xhr.responseJSON?.message ??
                            'Status review gagal diperbarui.';

                        if (window.AppAlert?.error) {
                            AppAlert.error(errorMessage);
                        } else {
                            window.alert(errorMessage);
                        }
                    })
                    .always(function() {
                        button.prop('disabled', false).html(originalHtml);
                    });
                };

                if (window.AppAlert?.warning) {
                    AppAlert.warning(message, isPublish ? 'Publish Review?' : 'Sembunyikan Review?')
                        .then(function(result) {
                            if (result.isConfirmed) {
                                execute();
                            }
                        });
                    return;
                }

                if (window.confirm(message)) {
                    execute();
                }
            });
        });
    </script>
@endpush
