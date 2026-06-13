<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Support\Academic\ResolvesActiveSemester;
use App\Models\Santri;
use App\Models\Musyrif;
use App\Models\Tilawah;
use App\Models\HafalanTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TilawahController extends Controller
{
    use ResolvesActiveSemester;

    /**
     * Mengambil data santri beserta rekomendasi template tilawah selanjutnya
     */
    public function getProgress(): JsonResponse
    {
        $musyrif = Musyrif::where('user_id', Auth::id())->firstOrFail();

        // Ambil semua santri binaan
        $santris = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->get([
                'id',
                'nama',
            ]);

        // Ambil semua template khusus tahap 'harian' untuk pilihan select
        // Diurutkan berdasarkan juz dan urutan agar logis/sekuensial
        $templates = HafalanTemplate::where('tahap', 'harian')
            ->orderBy('juz')
            ->orderBy('urutan')
            ->get(['id', 'juz', 'label']);

        $santriProgress = [];

        foreach ($santris as $santri) {
            // Cari rekam jejak tilawah terakhir santri ini
            $lastTilawah = Tilawah::where('santri_id', $santri->id)
                ->latest('tanggal')
                ->latest('id')
                ->first();

            $nextTemplateId = null;

            if ($lastTilawah) {
                // Temukan posisi/index dari template terakhir di dalam Collection
                $currentIndex = $templates->search(fn($t) => $t->id === $lastTilawah->hafalan_template_id);

                // Jika ketemu dan masih ada urutan bacaan berikutnya, rekomendasikan yang berikutnya
                if ($currentIndex !== false && isset($templates[$currentIndex + 1])) {
                    $nextTemplateId = $templates[$currentIndex + 1]->id;
                } else {
                    // Jika sudah mencapai batas akhir, pertahankan di template terakhir
                    $nextTemplateId = $lastTilawah->hafalan_template_id;
                }
            } else {
                // Jika belum pernah tilawah sama sekali, berikan ID bacaan pertama (Juz 1 awal)
                $nextTemplateId = $templates->first()->id ?? null;
            }

            $santriProgress[] = [
                'id' => $santri->id,
                'nama' => $santri->nama,
                'recommended_template_id' => $nextTemplateId
            ];
        }

        return response()->json([
            'status' => 'success',
            'templates' => $templates,
            'data_santri' => $santriProgress
        ]);
    }

    /**
     * Menyimpan data tilawah masal (1 target bacaan untuk semua santri)
     */
    public function storeMasal(Request $request): JsonResponse
    {
        $activeSemester = $this->assertAcademicInputOpen();
        $semesterId = (int) $activeSemester->id;

        $validated = $request->validate([
            'template_id' => [
                'required',
                'integer',
                'exists:hafalan_templates,id',
            ],
            'detail_ayat' => [
                'required',
                'string',
                'max:150',
            ],
            'catatan' => [
                'nullable',
                'string',
            ],
        ]);

        try {
            $musyrif = Musyrif::query()
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $tanggal = now('Asia/Jakarta')->toDateString();

            $santriIds = Santri::query()
                ->active()
                ->where('musyrif_id', $musyrif->id)
                ->pluck('id');

            if ($santriIds->isEmpty()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Gagal! Anda belum memiliki santri binaan.',
                ], 422);
            }

            $catatanFinal =
                'Ayat: ' . trim($validated['detail_ayat']);

            if (!empty($validated['catatan'])) {
                $catatanFinal .=
                    ' | Catatan: ' . trim($validated['catatan']);
            }

            DB::transaction(function () use (
                $santriIds,
                $tanggal,
                $semesterId,
                $musyrif,
                $validated,
                $catatanFinal
            ): void {
                foreach ($santriIds as $santriId) {
                    Tilawah::query()->updateOrCreate(
                        [
                            'santri_id' => $santriId,
                            'semester_id' => $semesterId,
                            'tanggal' => $tanggal,
                        ],
                        [
                            'musyrif_id' => $musyrif->id,
                            'hafalan_template_id' =>
                            $validated['template_id'],
                            'status' => 'hadir',
                            'catatan' => $catatanFinal,
                        ]
                    );
                }
            });

            return response()->json([
                'ok' => true,
                'message' =>
                'Berhasil! Target Tilawah diterapkan ke '
                    . $santriIds->count()
                    . ' santri.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan data Tilawah.',
            ], 500);
        }
    }

    /**
     * Menampilkan Datatables Riwayat Tilawah
     */
    public function datatable(Request $request)
    {
        $musyrif = Musyrif::where('user_id', Auth::id())->firstOrFail();

        $query = Tilawah::where('musyrif_id', $musyrif->id)
            ->with(['santri', 'template'])
            ->select('tilawahs.*');

        // Logic Filter Tanggal (Sama seperti Tahsin)
        if ($request->filter_tanggal == 'today') {
            $query->whereDate('tanggal', today());
        } elseif ($request->filter_tanggal == 'yesterday') {
            $query->whereDate('tanggal', today()->subDay());
        } elseif ($request->filter_tanggal == 'last_7_days') {
            $query->whereDate('tanggal', '>=', today()->subDays(7));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('santri', fn($row) => $row->santri->nama)
            ->addColumn('target_bacaan', function ($row) {
                if ($row->template) {
                    return "<span class='fw-bold'>Juz {$row->template->juz}</span><br><small class='text-muted'>{$row->template->label}</small>";
                }
                return '-';
            })
            ->addColumn('catatan_ayat', function ($row) {
                // Memotong teks jika terlalu panjang agar tabel tidak melar
                $text = e($row->catatan ?? '-');
                return "<span class='small text-wrap' style='max-width: 250px; display: inline-block;'>{$text}</span>";
            })
            ->addColumn('status_label', function ($row) {
                $color = match ($row->status) {
                    'hadir' => 'success',
                    'izin'  => 'secondary',
                    'sakit' => 'primary',
                    'alpha' => 'danger',
                    default => 'dark'
                };
                return '<span class="badge bg-' . $color . '-subtle text-' . $color . ' rounded-pill px-3">' . ucfirst($row->status) . '</span>';
            })
            ->addColumn('aksi', function ($row) {
                $target = $row->template ? "Juz {$row->template->juz} - {$row->template->label}" : '-';
                return '
                <div class="d-flex justify-content-end gap-2 flex-nowrap">
                    <button type="button" class="btn btn-sm btn-info btn-detail-tilawah"
                        data-santri_nama="' . e($row->santri->nama) . '"
                        data-target_bacaan="' . e($target) . '"
                        data-tanggal_label="' . $row->tanggal->format('d M Y') . '"
                        data-status_text="' . $row->status . '"
                        data-catatan="' . e($row->catatan ?? 'Tidak ada catatan.') . '"
                        data-coreui-toggle="tooltip" title="Lihat Detail">
                        <i class="bi bi-eye text-white"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-primary btn-edit-tilawah"
                        data-id="' . $row->id . '"
                        data-santri_nama="' . e($row->santri->nama) . '"
                        data-target_bacaan="' . e($target) . '"
                        data-status="' . $row->status . '"
                        data-catatan="' . e($row->catatan) . '"
                        data-coreui-toggle="tooltip" title="Edit Status">
                        <i class="bi bi-pencil-square"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-tilawah"
                        data-id="' . $row->id . '"
                        data-coreui-toggle="tooltip" title="Hapus Data">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['target_bacaan', 'catatan_ayat', 'status_label', 'aksi'])
            ->make(true);
    }

    public function update(
        Request $request,
        Tilawah $tilawah
    ) {
        $musyrif = Musyrif::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ((int) $tilawah->musyrif_id !== (int) $musyrif->id) {
            return response()->json([
                'message' => 'Unauthorized! Data ini bukan milik Anda.',
            ], 403);
        }

        $activeSemester =
            $this->assertRecordEditableInActiveSemester(
                $tilawah->semester_id
            );

        $validated = $request->validate([
            'status' => [
                'required',
                'in:hadir,izin,sakit,alpha',
            ],
            'catatan' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['semester_id'] =
            (int) $activeSemester->id;

        $tilawah->update($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Status Tilawah individu berhasil diperbarui.',
        ]);
    }

    /**
     * Menghapus data riwayat Tilawah
     */
    public function destroy(Tilawah $tilawah)
    {
        $musyrif = Musyrif::query()
            ->where('user_id', Auth::id())
            ->first();

        if (
            !$musyrif
            || (int) $tilawah->musyrif_id !== (int) $musyrif->id
        ) {
            return response()->json([
                'message' => 'Unauthorized! Data ini bukan milik Anda.',
            ], 403);
        }

        $this->assertRecordEditableInActiveSemester(
            $tilawah->semester_id
        );

        $tilawah->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Data Tilawah berhasil dihapus.',
        ]);
    }
}
