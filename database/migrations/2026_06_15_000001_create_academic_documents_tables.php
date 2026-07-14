<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Nomor Urut Dokumen
        |--------------------------------------------------------------------------
        |
        | Menjamin nomor Raport/Syahadah tidak bentrok ketika beberapa dokumen
        | diterbitkan hampir bersamaan.
        |
        */

        Schema::create(
            'academic_document_sequences',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * Contoh:
                 * raport:2026:semester:12
                 * syahadah:2026
                 */
                $table
                    ->string('sequence_key', 150)
                    ->unique();

                $table
                    ->unsignedBigInteger('last_number')
                    ->default(0);

                $table->timestamps();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Dokumen Akademik
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'academic_documents',
            function (Blueprint $table): void {
                $table->id();

                /*
                 * ID publik yang aman digunakan pada URL.
                 * ID database tidak ditampilkan kepada pengguna.
                 */
                $table
                    ->uuid('public_id')
                    ->unique();

                /*
                 * Pemilik dan periode dokumen.
                 */
                $table
                    ->foreignId('santri_id')
                    ->constrained('santris')
                    ->restrictOnDelete();

                $table
                    ->foreignId('semester_id')
                    ->nullable()
                    ->constrained('semesters')
                    ->restrictOnDelete();

                /*
                 * raport | syahadah
                 *
                 * Pada Step 2A hanya "raport" yang digunakan.
                 */
                $table
                    ->string('document_type', 30)
                    ->default('raport')
                    ->index();

                /*
                 * draft | review | published | revoked
                 */
                $table
                    ->string('status', 30)
                    ->default('draft')
                    ->index();

                /*
                 * Nomor revisi dimulai dari 1.
                 */
                $table
                    ->unsignedSmallInteger('revision')
                    ->default(1);

                /*
                 * Dokumen aktif terbaru.
                 * Saat revisi baru diterbitkan, dokumen lama menjadi false.
                 */
                $table
                    ->boolean('is_current')
                    ->default(true)
                    ->index();

                /*
                 * Nomor resmi dibuat ketika dokumen dipublikasikan.
                 *
                 * Contoh:
                 * RPT/DTQ/2026/GENAP/000001
                 */
                $table
                    ->string('document_number', 120)
                    ->nullable();

                /*
                 * Versi desain/template PDF.
                 */
                $table
                    ->string('template_version', 50)
                    ->default('raport-v1');

                /*
                 * Snapshot data resmi.
                 *
                 * Setelah published, dokumen membaca data dari snapshot ini,
                 * bukan membaca transaksi live.
                 */
                $table
                    ->json('snapshot_json')
                    ->nullable();

                $table
                    ->char('snapshot_sha256', 64)
                    ->nullable();

                /*
                 * Catatan dan evaluasi.
                 */
                $table
                    ->string('predikat', 50)
                    ->nullable();

                $table
                    ->text('catatan_musyrif')
                    ->nullable();

                $table
                    ->text('catatan_admin')
                    ->nullable();

                $table
                    ->text('rekomendasi')
                    ->nullable();

                $table
                    ->text('review_notes')
                    ->nullable();

                /*
                 * Override warning.
                 *
                 * Digunakan ketika dokumen tetap diterbitkan walaupun terdapat
                 * soft warning, misalnya belum ada transaksi Tilawah.
                 */
                $table
                    ->text('override_reason')
                    ->nullable();

                $table
                    ->foreignId('override_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                 * PDF final disimpan pada private storage.
                 */
                $table
                    ->string('pdf_path', 500)
                    ->nullable();

                $table
                    ->char('pdf_sha256', 64)
                    ->nullable();

                $table
                    ->timestamp('pdf_generated_at')
                    ->nullable();

                /*
                 * Token verifikasi.
                 *
                 * Token asli dimasukkan ke QR, tetapi database hanya menyimpan
                 * hash SHA-256-nya.
                 */
                $table
                    ->char('verification_token_hash', 64)
                    ->nullable()
                    ->unique('ad_verification_hash_uq');

                /*
                 * Statistik download.
                 */
                $table
                    ->unsignedInteger('download_count')
                    ->default(0);

                $table
                    ->timestamp('last_downloaded_at')
                    ->nullable();

                /*
                 * Lifecycle dokumen.
                 */
                $table
                    ->timestamp('generated_at')
                    ->nullable();

                $table
                    ->timestamp('submitted_at')
                    ->nullable();

                $table
                    ->foreignId('reviewed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp('reviewed_at')
                    ->nullable();

                $table
                    ->foreignId('published_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp('published_at')
                    ->nullable();

                $table
                    ->foreignId('revoked_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp('revoked_at')
                    ->nullable();

                $table
                    ->text('revocation_reason')
                    ->nullable();

                /*
                 * Relasi revisi.
                 *
                 * Contoh:
                 * revision 2 menggantikan revision 1.
                 */
                $table
                    ->foreignId('supersedes_document_id')
                    ->nullable()
                    ->constrained('academic_documents')
                    ->nullOnDelete();

                /*
                 * Audit creator/updater.
                 */
                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                 * Informasi tambahan yang tidak harus menjadi kolom khusus.
                 */
                $table
                    ->json('metadata')
                    ->nullable();

                $table->timestamps();

                /*
                 * Satu santri tidak boleh memiliki nomor revisi yang sama
                 * pada jenis dokumen dan semester yang sama.
                 */
                $table->unique(
                    [
                        'santri_id',
                        'semester_id',
                        'document_type',
                        'revision',
                    ],
                    'ad_santri_sem_type_rev_uq'
                );

                /*
                 * Nomor dokumen dapat sama pada revision berbeda,
                 * tetapi tidak boleh sama dalam revision yang sama.
                 */
                $table->unique(
                    [
                        'document_number',
                        'revision',
                    ],
                    'ad_doc_no_rev_uq'
                );

                $table->index(
                    [
                        'santri_id',
                        'document_type',
                        'status',
                    ],
                    'ad_santri_type_status_idx'
                );

                $table->index(
                    [
                        'semester_id',
                        'document_type',
                        'status',
                    ],
                    'ad_sem_type_status_idx'
                );

                $table->index(
                    [
                        'status',
                        'published_at',
                    ],
                    'ad_status_published_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'academic_documents'
        );

        Schema::dropIfExists(
            'academic_document_sequences'
        );
    }
};
