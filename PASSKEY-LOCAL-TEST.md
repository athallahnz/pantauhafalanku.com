# Pengujian Lokal Passkey SIMTAQU

Implementasi ini menambahkan passkey sebagai alternatif login. Login dengan password tetap tersedia dan tidak ada
data biometrik yang dikirim ke atau disimpan oleh SIMTAQU.

## 1. Pasang dependensi dan migrasi

```bash
composer install
php artisan optimize:clear
php artisan migrate
npm ci
npm run build
```

Migration baru membuat tabel `passkey_credentials`. Satu user dapat mempunyai banyak baris/perangkat.

## 2. Konfigurasi origin dan RP ID

WebAuthn mengikat passkey ke domain. Nilainya harus sama persis dengan URL yang dibuka di browser.

Untuk `php artisan serve` pada localhost:

```dotenv
APP_URL=http://localhost:8000
PASSKEY_RP_NAME=SIMTAQU Lokal
PASSKEY_RP_ID=localhost
PASSKEY_ALLOWED_ORIGINS=http://localhost:8000
```

`http://localhost` diizinkan browser sebagai secure context untuk pengembangan. Untuk domain selain localhost,
gunakan HTTPS. Contoh:

```dotenv
APP_URL=https://simtaqu.test
PASSKEY_RP_NAME=SIMTAQU Lokal
PASSKEY_RP_ID=simtaqu.test
PASSKEY_ALLOWED_ORIGINS=https://simtaqu.test
```

Jika pengujian memang memakai beberapa origin, pisahkan allowlist dengan koma. Jangan tambahkan path atau trailing
slash. Setelah mengubah `.env`, jalankan:

```bash
php artisan optimize:clear
```

## 3. Jalankan automated test

```bash
php artisan test --filter=PasskeyAuthenticationTest
```

Test mencakup password login yang tetap aktif, role yang diizinkan, opsi WebAuthn dengan user verification wajib,
resident credential, challenge sekali pakai, kepemilikan credential, banyak perangkat, exact-origin check,
verifikasi signature/counter, penolakan assertion tanpa UV, serta ketiadaan kolom biometrik.

## 4. Uji manual

1. Masuk dengan password sebagai `superadmin`, `admin`, `pimpinan`, atau `musyrif` yang aktif dan approved.
2. Buka menu avatar, lalu **Keamanan Akun**.
3. Isi nama perangkat dan password saat ini, lalu pilih **Tambah passkey**.
4. Selesaikan verifikasi perangkat (PIN, sidik jari, wajah, atau security key sesuai perangkat). Verifikasi berlangsung
   lokal pada authenticator; SIMTAQU tidak menerima data biometriknya.
5. Logout. Pada halaman login, pilih **Masuk dengan Passkey** tanpa mengisi email atau password.
6. Kembali ke **Keamanan Akun**, ubah nama passkey dan pastikan tersimpan.
7. Tambahkan perangkat kedua dan pastikan keduanya muncul.
8. Cabut salah satu passkey, logout, lalu pastikan credential yang dicabut tidak lagi dapat dipakai. Password harus
   tetap dapat dipakai untuk login.
9. Ulangi smoke test untuk keempat role. Role `santri` tidak boleh mendapat halaman atau login passkey.

## Catatan operasional

- `userVerification` dan resident key dikunci ke `required`; attestation dikunci ke `none`.
- RP ID berasal dari konfigurasi, bukan dari header `Host` request.
- Challenge disimpan singkat di session dan dihapus saat verifikasi pertama, berhasil maupun gagal.
- Audit Composer pada 14 September 2026 tidak menemukan advisory untuk `lbuchs/webauthn` yang baru ditambahkan,
  tetapi lockfile sumber memiliki 58 advisory pada 17 paket lama. Selesaikan audit dependency baseline sebagai gate
  terpisah sebelum rilis production.
- Jangan menjalankan migration atau mengganti konfigurasi ini di production sebelum hasil pengujian lokal disetujui.
