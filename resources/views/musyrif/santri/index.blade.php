    @extends('layouts.app')

    @section('title', 'Santri Binaan')

    @section('content')
        <div class="card">
            <div class="card-header">
                <span>Daftar Santri Binaan</span>
            </div>
            <div class="card-body table-responsive">
                <table id="musyrif-santri-table" class="table table-striped align-middle w-100">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Santri</th>
                            <th>Kelas</th>
                            <th>NIS</th>
                            <th>Tanggal Lahir</th>
                            <th>Jenis Kelamin</th>
                            <th>Progress Hafalan</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

            </div>
        </div>
    @endsection

    @push('modals')
        // Modal Progress Hafalan Santri
        <div class="modal fade" id="modalProgressSantri" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Progress Hafalan</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <div class="fw-semibold" id="progressSantriName">-</div>
                            <div class="text-muted small" id="progressSantriMeta">-</div>
                        </div>
                        <div id="progressSantriBody"></div>
                    </div>
                </div>
            </div>
        </div>
    @endpush
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const modalProgress = new coreui.Modal(document.getElementById('modalProgressSantri'));

                $('#musyrif-santri-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('musyrif.santri.datatable') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'nama',
                            name: 'nama'
                        },
                        {
                            data: 'kelas',
                            name: 'kelas',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'nis',
                            name: 'nis'
                        },
                        {
                            data: 'tanggal_lahir',
                            name: 'tanggal_lahir',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'jenis_kelamin',
                            name: 'jenis_kelamin',
                            orderable: false,
                            searchable: false
                        },

                        // NEW
                        {
                            data: 'progress_ringkas',
                            name: 'progress_ringkas',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'aksi',
                            name: 'aksi',
                            orderable: false,
                            searchable: false
                        },
                    ],
                    order: [
                        [1, 'asc']
                    ],
                    drawCallback: function() {
                        $('.btn-progress').off('click').on('click', function() {
                            const d = $(this).data();

                            $('#progressSantriName').text(d.nama || '-');
                            $('#progressSantriMeta').text(d.kelas ? `Kelas: ${d.kelas}` : '-');

                            // detail_html sudah di-escape, jadi decode via DOM trick
                            const textarea = document.createElement('textarea');
                            textarea.innerHTML = d.detail_html || '';
                            $('#progressSantriBody').html(textarea.value ||
                                '<div class="text-muted">Tidak ada data.</div>');

                            modalProgress.show();
                        });
                    }
                });
            });
        </script>
    @endpush
