<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class KelasController extends Controller
{
    public function index()
    {
        $kelasParents = $this->parentOptions();

        return view('kelas.index', compact('kelasParents'));
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $this->parentOptions()->map(fn(Kelas $kelas) => [
                'id' => (int) $kelas->id,
                'nama_kelas' => $kelas->nama_kelas,
                'kode' => $kelas->kode,
                'group_mode' => $kelas->groupMode(),
                'jenis_kelamin' => Kelas::normalizeGender($kelas->jenis_kelamin),
                'gender_label' => $kelas->genderLabel(),
            ])->values(),
        ]);
    }

    public function getData(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $query = Kelas::query()
            ->with('parent:id,nama_kelas,kode,is_active')
            ->select([
                'id',
                'parent_id',
                'nama_kelas',
                'kelompok',
                'jenis_kelamin',
                'kode',
                'deskripsi',
                'is_active',
                'urutan',
            ])
            /*
             * Penting: withCount harus dipanggil SETELAH select.
             * Jika select dipanggil sesudah withCount, seluruh kolom *_count
             * akan tertimpa dan DataTables selalu membaca nilainya sebagai 0.
             */
            ->withCount([
                'children',
                'santris',
                'musyrifs',
                'musyrifInduks',
                'musyrifBinaan',
            ])
            ->with([
                'children' => fn($children) => $children
                    ->select(['id', 'parent_id'])
                    ->withCount('santris'),
            ])
            ->urutHierarki();

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('nama_kelas', function (Kelas $row): string {
                $icon = $row->isInduk() ? 'bi-folder2-open' : 'bi-diagram-2';
                $badgeClass = $row->isInduk()
                    ? 'bg-primary-subtle text-primary'
                    : 'bg-info-subtle text-info';
                $badge = $row->isInduk() ? 'Induk' : 'Kelompok ' . e($row->kelompok);
                $indent = $row->isKelompok() ? 'ms-4' : '';
                $parent = $row->isKelompok()
                    ? '<div class="small text-body-secondary mt-1">Induk: ' . e($row->parent?->nama_kelas ?? '-') . '</div>'
                    : '<div class="small text-body-secondary mt-1">' . (int) $row->children_count . ' kelompok</div>';

                return <<<HTML
                    <div class="{$indent}">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <i class="bi {$icon} text-adaptive-purple"></i>
                            <span class="fw-bold">{$this->escape($row->nama_kelas)}</span>
                            <span class="badge {$badgeClass} rounded-pill">{$badge}</span>
                        </div>
                        {$parent}
                    </div>
                HTML;
            })
            ->addColumn('kode_label', fn(Kelas $row): string => $row->kode
                ? '<span class="badge bg-light text-dark border font-monospace">' . e($row->kode) . '</span>'
                : '<span class="text-body-secondary">-</span>')
            ->addColumn('gender_badge', function (Kelas $row): string {
                $gender = Kelas::normalizeGender($row->jenis_kelamin);

                [$class, $icon] = match ($gender) {
                    Kelas::GENDER_PUTRA => ['bg-primary-subtle text-primary', 'bi-gender-male'],
                    Kelas::GENDER_PUTRI => ['bg-danger-subtle text-danger', 'bi-gender-female'],
                    Kelas::GENDER_MIXED => ['bg-info-subtle text-info', 'bi-gender-ambiguous'],
                    default => ['bg-warning-subtle text-warning', 'bi-question-circle'],
                };

                return '<span class="badge ' . $class . ' rounded-pill">'
                    . '<i class="bi ' . $icon . ' me-1"></i>'
                    . e($row->genderLabel())
                    . '</span>';
            })
            ->editColumn('deskripsi', fn(Kelas $row): string => $row->deskripsi
                ? '<span title="' . e($row->deskripsi) . '">' . e(Str::limit($row->deskripsi, 80)) . '</span>'
                : '<span class="text-body-secondary">Tidak ada deskripsi</span>')
            ->addColumn('penggunaan', function (Kelas $row): string {
                if ($row->isInduk()) {
                    // Santri operasional tersimpan pada kelas kelompok/child.
                    $santri = (int) $row->santris_count + (int) $row->children->sum(
                        fn(Kelas $child): int => (int) $child->santris_count
                    );

                    // kelas_induk_id adalah sumber utama; relasi lama tetap dihitung saat transisi.
                    $musyrif = max(
                        (int) $row->musyrif_induks_count,
                        (int) $row->musyrifs_count,
                        (int) $row->musyrif_binaan_count
                    );
                } else {
                    $santri = (int) $row->santris_count;

                    /*
                     * max mencegah Musyrif yang sama terlihat ganda selama
                     * masa transisi dari kelas_id lama ke pivot musyrif_kelas.
                     */
                    $musyrif = max(
                        (int) $row->musyrifs_count,
                        (int) $row->musyrif_binaan_count
                    );
                }

                return <<<HTML
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-dark border">Santri: {$santri}</span>
                        <span class="badge bg-light text-dark border">Musyrif: {$musyrif}</span>
                    </div>
                HTML;
            })
            ->addColumn('status_badge', fn(Kelas $row): string => $row->is_active
                ? '<span class="badge bg-success-subtle text-success rounded-pill">Aktif</span>'
                : '<span class="badge bg-secondary-subtle text-secondary rounded-pill">Nonaktif</span>')
            ->addColumn('aksi', function (Kelas $row): string {
                $addChild = '';

                if ($row->isInduk()) {
                    $addChild = '<button type="button"
                        class="btn btn-sm btn-outline-primary rounded-3 btn-add-kelompok"
                        data-parent-id="' . (int) $row->id . '"
                        data-parent-name="' . e($row->nama_kelas) . '"
                        title="Tambah kelompok">
                        <i class="bi bi-node-plus"></i>
                    </button>';
                }

                return '<div class="d-flex justify-content-end gap-2">'
                    . $addChild
                    . '<button type="button"
                        class="btn btn-sm btn-outline-warning rounded-3 btn-edit-kelas"
                        data-id="' . (int) $row->id . '"
                        data-jenis="' . e($row->jenis) . '"
                        data-parent-id="' . e($row->parent_id ?? '') . '"
                        data-nama="' . e($row->nama_kelas) . '"
                        data-kelompok="' . e($row->kelompok ?? '') . '"
                        data-gender="' . e(Kelas::normalizeGender($row->jenis_kelamin) ?? '') . '"
                        data-kode="' . e($row->kode ?? '') . '"
                        data-urutan="' . (int) $row->urutan . '"
                        data-active="' . ($row->is_active ? '1' : '0') . '"
                        data-deskripsi="' . e($row->deskripsi ?? '') . '"
                        title="Edit kelas">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button"
                        class="btn btn-sm btn-outline-danger rounded-3 btn-delete-kelas"
                        data-id="' . (int) $row->id . '"
                        data-label="' . e($row->nama_kelas) . '"
                        title="Hapus kelas">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>';
            })
            ->rawColumns([
                'nama_kelas',
                'kode_label',
                'gender_badge',
                'deskripsi',
                'penggunaan',
                'status_badge',
                'aksi',
            ])
            ->toJson();
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);

        $kelas = DB::transaction(fn() => $this->saveKelas(new Kelas(), $payload));

        return response()->json([
            'status' => 'success',
            'message' => $kelas->isInduk()
                ? 'Kelas induk berhasil ditambahkan.'
                : 'Kelompok kelas berhasil ditambahkan.',
            'data' => $kelas,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $kelas = Kelas::query()->with('children')->findOrFail($id);
        $payload = $this->validatedPayload($request, $kelas);

        $kelas = DB::transaction(fn() => $this->saveKelas($kelas, $payload));

        return response()->json([
            'status' => 'success',
            'message' => 'Data kelas berhasil diperbarui.',
            'data' => $kelas->fresh(['parent']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $kelas = Kelas::query()->withCount('children')->findOrFail($id);

        if ($kelas->children_count > 0) {
            throw ValidationException::withMessages([
                'kelas' => ['Kelas induk tidak dapat dihapus karena masih memiliki kelompok.'],
            ]);
        }

        $references = $this->referenceCounts($kelas->id);
        $totalReferences = array_sum($references);

        if ($totalReferences > 0) {
            $usedBy = collect($references)
                ->filter(fn(int $count) => $count > 0)
                ->map(fn(int $count, string $name) => "{$name}: {$count}")
                ->implode(', ');

            throw ValidationException::withMessages([
                'kelas' => ["Kelas masih dipakai oleh data lain ({$usedBy}). Nonaktifkan kelas, jangan menghapusnya."],
            ]);
        }

        try {
            $kelas->delete();
        } catch (QueryException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kelas tidak dapat dihapus karena masih digunakan oleh data lain.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }

    private function validatedPayload(Request $request, ?Kelas $kelas = null): array
    {
        $jenis = (string) $request->input('jenis_kelas', 'induk');
        $isKelompok = $jenis === 'kelompok';

        $validated = $request->validate([
            'jenis_kelas' => ['required', Rule::in(['induk', 'kelompok'])],
            'parent_id' => [
                Rule::requiredIf($isKelompok),
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
            'nama_kelas' => [
                Rule::requiredIf(!$isKelompok),
                'nullable',
                'string',
                'max:100',
            ],
            'kelompok' => [
                Rule::requiredIf($isKelompok),
                'nullable',
                'string',
                'max:5',
                'regex:/^(?:[A-Za-z]|[1-9][0-9]*)$/',
            ],
            'jenis_kelamin' => [
                Rule::requiredIf($isKelompok),
                'nullable',
                'string',
                Rule::in([
                    Kelas::GENDER_PUTRA,
                    Kelas::GENDER_PUTRI,
                    Kelas::GENDER_MIXED,
                ]),
            ],
            'kode' => ['nullable', 'string', 'max:30'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ], [
            'jenis_kelas.required' => 'Jenis kelas wajib dipilih.',
            'parent_id.required' => 'Kelas induk wajib dipilih untuk data kelompok.',
            'nama_kelas.required' => 'Nama kelas induk wajib diisi.',
            'kelompok.required' => 'Kode kelompok wajib diisi.',
            'kelompok.regex' => 'Kelompok harus satu huruf A-Z atau angka positif.',
            'jenis_kelamin.required' => 'Jenis kelamin kelas kelompok wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin kelas tidak valid.',
        ]);

        if ($kelas) {
            $currentType = $kelas->isKelompok() ? 'kelompok' : 'induk';

            if ($currentType !== $jenis) {
                throw ValidationException::withMessages([
                    'jenis_kelas' => ['Jenis record kelas tidak dapat diubah setelah dibuat.'],
                ]);
            }
        }

        $parent = null;
        $kelompok = null;
        $classGender = Kelas::normalizeGender(
            $validated['jenis_kelamin'] ?? null
        );

        if ($isKelompok) {
            $parent = Kelas::query()->findOrFail((int) $validated['parent_id']);

            if (!$parent->isInduk()) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Parent harus berupa kelas induk, bukan kelompok lain.'],
                ]);
            }

            if (!$parent->is_active) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Kelas induk nonaktif tidak dapat menerima kelompok baru.'],
                ]);
            }

            if ($kelas && (int) $parent->id === (int) $kelas->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Kelas tidak boleh menjadi parent untuk dirinya sendiri.'],
                ]);
            }

            $kelompok = $parent->normalizeGroupValue((string) $validated['kelompok']);

            if ($parent->usesNumericGroups()) {
                if (preg_match('/^[1-9][0-9]*$/', $kelompok) !== 1) {
                    throw ValidationException::withMessages([
                        'kelompok' => ['Kelompok SMA (kelas 10-12) wajib menggunakan numbering angka, misalnya 1, 2, atau 3.'],
                    ]);
                }
            } elseif (preg_match('/^[A-Z]$/', $kelompok) !== 1) {
                throw ValidationException::withMessages([
                    'kelompok' => ['Kelompok SMP (kelas 7-9) wajib menggunakan huruf A sampai Z.'],
                ]);
            }

            $nama = trim($parent->nama_kelas . ' ' . $kelompok);

            $duplicateGroup = Kelas::query()
                ->where('parent_id', $parent->id)
                ->where('kelompok', $kelompok)
                ->when($kelas, fn($query) => $query->where('id', '!=', $kelas->id))
                ->exists();

            $duplicateName = Kelas::query()
                ->whereRaw('LOWER(TRIM(nama_kelas)) = ?', [mb_strtolower($nama)])
                ->when($kelas, fn($query) => $query->where('id', '!=', $kelas->id))
                ->exists();

            if ($duplicateName) {
                throw ValidationException::withMessages([
                    'kelompok' => ["Nama kelas {$nama} sudah digunakan record lain."],
                ]);
            }

            if ($duplicateGroup) {
                throw ValidationException::withMessages([
                    'kelompok' => ["Kelompok {$kelompok} sudah tersedia pada {$parent->nama_kelas}."],
                ]);
            }
        } else {
            $nama = trim((string) $validated['nama_kelas']);

            $duplicateName = Kelas::query()
                ->whereRaw('LOWER(TRIM(nama_kelas)) = ?', [mb_strtolower($nama)])
                ->when($kelas, fn($query) => $query->where('id', '!=', $kelas->id))
                ->exists();

            if ($duplicateName) {
                throw ValidationException::withMessages([
                    'nama_kelas' => ['Nama kelas tersebut sudah digunakan.'],
                ]);
            }
        }

        $code = strtoupper(trim((string) ($validated['kode'] ?? '')));
        $code = $code !== ''
            ? $code
            : ($isKelompok
                ? trim((string) $parent?->kode . '-' . $kelompok, '-')
                : $this->makeParentCode($nama));

        $codeConflict = Kelas::query()
            ->where('kode', $code)
            ->when($kelas, fn($query) => $query->where('id', '!=', $kelas->id))
            ->exists();

        if ($codeConflict) {
            throw ValidationException::withMessages([
                'kode' => ["Kode {$code} sudah digunakan kelas lain."],
            ]);
        }

        $isActive = $request->boolean('is_active');

        if ($kelas?->isInduk() && !$isActive && $kelas->children()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'is_active' => ['Nonaktifkan seluruh kelompok aktif terlebih dahulu sebelum menonaktifkan kelas induk.'],
            ]);
        }

        if (
            $kelas?->isKelompok()
            && !$isActive
            && (
                $kelas->santris()->where('status', 'aktif')->exists()
                || $kelas->musyrifs()->exists()
                || $kelas->musyrifBinaan()->exists()
            )
        ) {
            throw ValidationException::withMessages([
                'is_active' => ['Kelompok yang masih dipakai santri aktif atau musyrif tidak dapat dinonaktifkan.'],
            ]);
        }

        return [
            'jenis_kelas' => $jenis,
            'parent_id' => $parent?->id,
            'nama_kelas' => $nama,
            'kelompok' => $kelompok,
            'jenis_kelamin' => $classGender,
            'kode' => $code,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_active' => $isActive,
            'urutan' => (int) ($validated['urutan'] ?? 0),
        ];
    }

    private function saveKelas(Kelas $kelas, array $payload): Kelas
    {
        $oldName = $kelas->exists ? $kelas->nama_kelas : null;
        $oldCode = $kelas->exists ? $kelas->kode : null;

        $kelas->fill([
            'parent_id' => $payload['parent_id'],
            'nama_kelas' => $payload['nama_kelas'],
            'kelompok' => $payload['kelompok'],
            'jenis_kelamin' => $payload['jenis_kelamin'],
            'kode' => $payload['kode'],
            'deskripsi' => $payload['deskripsi'],
            'is_active' => $payload['is_active'],
            'urutan' => $payload['urutan'],
        ])->save();

        if (
            $kelas->isInduk()
            && ($oldName !== null || $oldCode !== null)
            && ($oldName !== $kelas->nama_kelas || $oldCode !== $kelas->kode)
        ) {
            foreach ($kelas->children()->get() as $child) {
                $childCode = trim((string) $kelas->kode . '-' . $child->kelompok, '-');

                $conflict = Kelas::query()
                    ->where('kode', $childCode)
                    ->where('id', '!=', $child->id)
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'kode' => ["Perubahan kode induk menghasilkan kode child duplikat: {$childCode}."],
                    ]);
                }

                $child->forceFill([
                    'nama_kelas' => trim($kelas->nama_kelas . ' ' . $child->kelompok),
                    'kode' => $childCode,
                ])->save();
            }
        }

        return $kelas->fresh(['parent']);
    }

    private function parentOptions()
    {
        return Kelas::query()
            ->induk()
            ->aktif()
            ->orderBy('urutan')
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas', 'kode', 'jenis_kelamin', 'urutan']);
    }

    private function makeParentCode(string $name): string
    {
        if (preg_match('/kelas\\s*(\\d+)\\s*(int)?/i', $name, $match)) {
            return 'K' . $match[1] . (!empty($match[2]) ? 'I' : '');
        }

        return Str::upper(Str::substr(Str::slug($name, ''), 0, 30));
    }

    private function referenceCounts(int $kelasId): array
    {
        $references = [
            'Santri' => ['santris', 'kelas_id'],
            'Musyrif operasional utama' => ['musyrifs', 'kelas_id'],
            'Tingkat utama Musyrif' => ['musyrifs', 'kelas_induk_id'],
            'Pivot kelas binaan Musyrif' => ['musyrif_kelas', 'kelas_id'],
            'Placement' => ['santri_semester_placements', 'kelas_id'],
            'Histori kelas' => ['santri_kelas_histories', 'kelas_id'],
            'Histori status' => ['santri_status_histories', 'kelas_id'],
            'Batch asal' => ['santri_migration_batches', 'from_kelas_id'],
            'Batch tujuan' => ['santri_migration_batches', 'to_kelas_id'],
            'Item batch asal' => ['santri_migration_batch_items', 'from_kelas_id'],
            'Item batch tujuan' => ['santri_migration_batch_items', 'to_kelas_id'],
        ];

        $counts = [];

        foreach ($references as $label => [$table, $column]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $counts[$label] = DB::table($table)->where($column, $kelasId)->count();
        }

        return $counts;
    }

    private function escape(?string $value): string
    {
        return e($value ?? '-');
    }
}
