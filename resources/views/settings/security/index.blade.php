@extends('layouts.app')

@section('title', 'Keamanan Akun - SIMTAQU')

@section('content')
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Keamanan Akun</h1>
            <p class="text-body-secondary mb-0">Kelola passkey sebagai cara masuk alternatif tanpa menghapus akses password.</p>
        </div>
        <span class="badge text-bg-success align-self-start px-3 py-2">
            <i class="bi bi-shield-check me-1"></i> Verifikasi pengguna wajib
        </span>
    </div>

    <div class="alert alert-info border-0 shadow-sm" role="alert">
        <div class="d-flex gap-3">
            <i class="bi bi-fingerprint fs-3"></i>
            <div>
                <div class="fw-semibold">Biometrik tetap berada di perangkat Anda</div>
                <div class="small">
                    SIMTAQU tidak menerima atau menyimpan sidik jari, wajah, maupun PIN perangkat. Server hanya
                    menyimpan public key dan data teknis minimum untuk memverifikasi login.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-2">Tambah passkey</h2>
                    <p class="text-body-secondary small mb-4">
                        Tambahkan ponsel, komputer, atau security key. Setiap akun boleh memiliki beberapa perangkat.
                    </p>

                    <form id="passkeyRegistrationForm" class="no-loader" novalidate>
                        <div class="mb-3">
                            <label for="passkeyName" class="form-label fw-semibold">Nama perangkat</label>
                            <input id="passkeyName" name="name" type="text" class="form-control"
                                maxlength="100" autocomplete="off" placeholder="Contoh: MacBook Kantor" required>
                        </div>

                        <div class="mb-3">
                            <label for="passkeyCurrentPassword" class="form-label fw-semibold">Konfirmasi password</label>
                            <input id="passkeyCurrentPassword" name="current_password" type="password"
                                class="form-control" maxlength="4096" autocomplete="current-password" required>
                            <div class="form-text">Password hanya dipakai untuk mengizinkan penambahan passkey.</div>
                        </div>

                        <div id="passkeyRegistrationStatus" class="small mb-3 d-none" role="status" aria-live="polite"></div>

                        <button id="passkeyRegistrationButton" type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-key me-2"></i>Tambah passkey
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Passkey tersimpan</h2>
                            <div class="small text-body-secondary">{{ $credentials->count() }} perangkat terdaftar</div>
                        </div>
                        <i class="bi bi-device-ssd fs-3 text-primary"></i>
                    </div>

                    @forelse ($credentials as $credential)
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex flex-column flex-md-row gap-3 justify-content-between">
                                <div class="flex-grow-1">
                                    <div class="fw-bold">{{ $credential->name }}</div>
                                    <div class="small text-body-secondary mt-1">
                                        Ditambahkan {{ $credential->created_at?->format('d M Y H:i') ?? '-' }}
                                        <span class="mx-1">&middot;</span>
                                        @if ($credential->last_used_at)
                                            Terakhir dipakai {{ $credential->last_used_at->format('d M Y H:i') }}
                                        @else
                                            Belum pernah dipakai
                                        @endif
                                    </div>

                                    <div class="mt-2">
                                        @forelse (($credential->transports ?? []) as $transport)
                                            <span class="badge text-bg-light border me-1">{{ $transport }}</span>
                                        @empty
                                            <span class="badge text-bg-light border">Transport tidak dilaporkan</span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="d-flex flex-column gap-2" style="min-width: 220px;">
                                    <form method="POST"
                                        action="{{ route('account.security.passkeys.update', $credential->id) }}"
                                        class="d-flex gap-2 no-loader">
                                        @csrf
                                        @method('PATCH')
                                        <label class="visually-hidden" for="passkey-name-{{ $credential->id }}">Nama passkey</label>
                                        <input id="passkey-name-{{ $credential->id }}" name="name" type="text"
                                            class="form-control form-control-sm" value="{{ $credential->name }}"
                                            maxlength="100" required>
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Simpan nama">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('account.security.passkeys.destroy', $credential->id) }}"
                                        class="passkey-revoke-form no-loader">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <i class="bi bi-trash me-1"></i>Cabut passkey
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center border rounded-3 py-5 px-3">
                            <i class="bi bi-key fs-1 text-body-secondary"></i>
                            <div class="fw-semibold mt-2">Belum ada passkey</div>
                            <div class="small text-body-secondary">Password tetap dapat digunakan untuk masuk.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/passkeys.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('passkeyRegistrationForm');
            const button = document.getElementById('passkeyRegistrationButton');
            const status = document.getElementById('passkeyRegistrationStatus');

            function showStatus(message, type) {
                status.textContent = message;
                status.className = 'small mb-3 alert py-2 ' + (type === 'error' ? 'alert-danger' : 'alert-info');
            }

            function errorMessage(error) {
                const validationErrors = error && error.payload && error.payload.errors;
                if (validationErrors) {
                    const messages = Object.values(validationErrors).flat();
                    if (messages.length) return messages[0];
                }

                if (error && error.name === 'NotAllowedError') {
                    return 'Proses dibatalkan atau melewati batas waktu.';
                }

                return error && error.message ? error.message : 'Passkey gagal ditambahkan.';
            }

            if (!window.SimtaquPasskeys.isSupported()) {
                showStatus('Browser atau koneksi ini belum mendukung passkey. Gunakan HTTPS atau localhost.', 'error');
                button.disabled = true;
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (!form.reportValidity()) return;

                button.disabled = true;
                showStatus('Ikuti petunjuk verifikasi pada perangkat Anda…', 'info');

                try {
                    const result = await window.SimtaquPasskeys.register({
                        optionsUrl: @json(route('account.security.passkeys.options')),
                        verifyUrl: @json(route('account.security.passkeys.store')),
                        name: document.getElementById('passkeyName').value,
                        currentPassword: document.getElementById('passkeyCurrentPassword').value,
                    });

                    showStatus(result.message || 'Passkey berhasil ditambahkan.', 'info');
                    window.location.reload();
                } catch (error) {
                    showStatus(errorMessage(error), 'error');
                    button.disabled = false;
                }
            });

            document.querySelectorAll('.passkey-revoke-form').forEach(function (revokeForm) {
                revokeForm.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    let confirmed = false;
                    if (window.Swal) {
                        const decision = await window.Swal.fire({
                            icon: 'warning',
                            title: 'Cabut passkey?',
                            text: 'Perangkat tersebut tidak dapat dipakai untuk login lagi.',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, cabut',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#dc3545',
                        });
                        confirmed = decision.isConfirmed;
                    } else {
                        confirmed = window.confirm('Cabut passkey ini? Perangkat tersebut tidak dapat dipakai untuk login lagi.');
                    }

                    if (confirmed) revokeForm.submit();
                });
            });
        });
    </script>
@endpush
