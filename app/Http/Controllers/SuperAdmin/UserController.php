<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\User;
use App\Models\UserLifecycleLog;
use App\Services\UserLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    private const ROLES = [
        'superadmin',
        'pimpinan',
        'admin',
        'musyrif',
        'santri',
    ];

    private const ACCOUNT_STATUSES = [
        UserLifecycleService::STATUS_PENDING,
        UserLifecycleService::STATUS_ACTIVE,
        UserLifecycleService::STATUS_SUSPENDED,
        UserLifecycleService::STATUS_REJECTED,
        UserLifecycleService::STATUS_ARCHIVED,
    ];

    private const STRUCTURAL_ROLES = [
        'musyrif',
        'santri',
    ];

    public function __construct(
        private readonly UserLifecycleService $lifecycle
    ) {}

    private function generateKode(string $nama, int $id): string
    {
        $parts = preg_split('/\s+/', trim($nama)) ?: [];
        $initials = '';

        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        $initials = substr($initials, 0, 3);
        $suffix = str_pad((string) $id, 2, '0', STR_PAD_LEFT);

        return ($initials !== '' ? $initials : 'MSY') . '-' . $suffix;
    }

    public function index()
    {
        $kelas = Kelas::query()
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas']);

        $statusCounts = User::query()
            ->withTrashed()
            ->selectRaw('account_status, COUNT(*) AS total')
            ->groupBy('account_status')
            ->pluck('total', 'account_status');

        $statusCounts['all'] = User::query()->count();

        return view(
            'superadmin.users.index',
            compact('kelas', 'statusCounts')
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nomor' => ['nullable', 'string', 'max:20', 'unique:users,nomor'],
            'role' => ['required', 'string', 'in:' . implode(',', self::ROLES)],
            'password' => ['required', 'string', 'min:6'],
            'kelas_id' => [
                'nullable',
                'required_if:role,santri',
                'integer',
                'exists:kelas,id',
            ],
        ]);

        $actor = $request->user();

        $user = DB::transaction(function () use ($validated, $actor): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'nomor' => $validated['nomor'] ?? null,
                'role' => $validated['role'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->forceFill([
                'account_status' => UserLifecycleService::STATUS_ACTIVE,
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'email_verified_at' => now(),
            ])->save();

            $this->syncRequiredProfile(
                $user,
                $validated['kelas_id'] ?? null
            );

            $this->lifecycle->recordCreated($actor, $user);

            return $user;
        });

        return response()->json([
            'message' => 'User ' . $user->name . ' berhasil dibuat dan langsung diaktifkan.',
        ]);
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $oldRole = (string) $user->role;
        $before = $this->lifecycle->snapshot($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'nomor' => [
                'nullable',
                'string',
                'max:20',
                'unique:users,nomor,' . $user->id,
            ],
            'role' => ['required', 'string', 'in:' . implode(',', self::ROLES)],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $newRole = (string) $validated['role'];
        $this->ensureRoleTransitionIsSafe($user, $oldRole, $newRole);

        DB::transaction(function () use (
            $request,
            $user,
            $validated,
            $before
        ): void {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'nomor' => $validated['nomor'] ?? null,
                'role' => $validated['role'],
            ]);

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            if ($user->role === 'musyrif') {
                Musyrif::query()
                    ->where('user_id', $user->id)
                    ->update(['nama' => $user->name]);
            }

            if ($user->role === 'santri') {
                Santri::query()
                    ->where('user_id', $user->id)
                    ->update(['nama' => $user->name]);
            }

            $this->lifecycle->recordUpdated(
                $request->user(),
                $user,
                $before,
                !empty($validated['password'])
                    ? 'Identitas akun diperbarui dan password diganti.'
                    : 'Identitas atau role akun diperbarui.'
            );
        });

        return response()->json([
            'message' => 'User berhasil diperbarui.',
        ]);
    }

    /**
     * Route DELETE lama dipertahankan, tetapi sekarang berarti arsip/soft delete.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $user = User::query()->findOrFail($id);
        $this->lifecycle->archive(
            $request->user(),
            $user,
            $validated['reason']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil diarsipkan. Data tidak dihapus permanen.',
        ]);
    }

    public function getData(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $role = $request->string('role')->trim()->toString();
        $status = $request->string('account_status')->trim()->toString();
        $currentUserId = (int) $request->user()->id;

        $activeSuperadminCount = User::query()
            ->where('role', 'superadmin')
            ->where('account_status', UserLifecycleService::STATUS_ACTIVE)
            ->where('is_approved', true)
            ->count();

        $query = $status === UserLifecycleService::STATUS_ARCHIVED
            ? User::query()->onlyTrashed()
            : User::query();

        $query->select([
            'id',
            'name',
            'email',
            'nomor',
            'role',
            'is_approved',
            'account_status',
            'approved_at',
            'suspended_at',
            'rejected_at',
            'archived_at',
            'created_at',
            'deleted_at',
        ]);

        if ($role !== '') {
            $query->where('role', $role);
        }

        if ($status !== '' && in_array($status, self::ACCOUNT_STATUSES, true)) {
            $query->where('account_status', $status);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function (User $row) use ($currentUserId): string {
                $disabled = (int) $row->id === $currentUserId || $row->trashed()
                    ? ' disabled title="Akun ini tidak dapat dipilih untuk aksi massal"'
                    : '';

                return '<input type="checkbox" class="form-check-input user-checkbox"'
                    . ' value="' . $row->id . '"'
                    . ' data-role="' . e((string) $row->role) . '"'
                    . ' data-status="' . e((string) $row->account_status) . '"'
                    . $disabled
                    . '>';
            })
            ->editColumn('role', function (User $row): string {
                $colors = [
                    'superadmin' => 'danger',
                    'pimpinan' => 'info',
                    'admin' => 'success',
                    'musyrif' => 'warning',
                    'santri' => 'primary',
                ];

                $color = $colors[$row->role] ?? 'secondary';
                $textClass = in_array($row->role, ['pimpinan', 'musyrif'], true)
                    ? ' text-dark'
                    : '';

                return '<span class="badge bg-' . $color . $textClass
                    . ' rounded-pill px-3">'
                    . e(strtoupper((string) $row->role))
                    . '</span>';
            })
            ->addColumn('status_badge', fn(User $row): string => $this->statusBadge($row))
            ->addColumn('aksi', function (User $row) use (
                $currentUserId,
                $activeSuperadminCount
            ): string {
                $buttons = [];
                $isSelf = (int) $row->id === $currentUserId;
                $isProtectedLastSuperadmin = $row->role === 'superadmin'
                    && $row->account_status === UserLifecycleService::STATUS_ACTIVE
                    && $activeSuperadminCount <= 1;

                $buttons[] = '<button class="btn btn-sm btn-outline-secondary btn-audit"'
                    . ' data-id="' . $row->id . '"'
                    . ' data-name="' . e($row->name) . '"'
                    . ' title="Riwayat lifecycle">'
                    . '<i class="bi bi-clock-history"></i>'
                    . '</button>';

                if ($row->account_status === UserLifecycleService::STATUS_PENDING) {
                    $buttons[] = '<button class="btn btn-sm btn-success text-white btn-approve"'
                        . ' data-id="' . $row->id . '"'
                        . ' data-name="' . e($row->name) . '"'
                        . ' data-role="' . e((string) $row->role) . '"'
                        . ' title="Setujui akun"><i class="bi bi-check-lg"></i></button>';

                    if (!$isSelf) {
                        $buttons[] = $this->lifecycleButton($row, 'reject', 'danger', 'x-circle', 'Tolak akun');
                    }
                }

                if ($row->account_status === UserLifecycleService::STATUS_ACTIVE && !$isSelf) {
                    if (!$isProtectedLastSuperadmin) {
                        $buttons[] = $this->lifecycleButton($row, 'suspend', 'warning', 'pause-circle', 'Tangguhkan akun');
                    }
                }

                if ($row->account_status === UserLifecycleService::STATUS_SUSPENDED) {
                    $buttons[] = $this->lifecycleButton($row, 'reactivate', 'success', 'arrow-counterclockwise', 'Aktifkan kembali');
                }

                if ($row->account_status === UserLifecycleService::STATUS_REJECTED) {
                    $buttons[] = '<button class="btn btn-sm btn-success text-white btn-approve"'
                        . ' data-id="' . $row->id . '"'
                        . ' data-name="' . e($row->name) . '"'
                        . ' data-role="' . e((string) $row->role) . '"'
                        . ' title="Setujui kembali akun"><i class="bi bi-check-lg"></i></button>';
                }

                if ($row->account_status === UserLifecycleService::STATUS_ARCHIVED || $row->trashed()) {
                    $buttons[] = $this->lifecycleButton($row, 'restore', 'primary', 'arrow-up-circle', 'Pulihkan akun');
                } else {
                    $buttons[] = '<button class="btn btn-sm btn-warning text-white btn-edit"'
                        . ' data-id="' . $row->id . '"'
                        . ' data-name="' . e($row->name) . '"'
                        . ' data-email="' . e($row->email) . '"'
                        . ' data-nomor="' . e((string) ($row->nomor ?? '')) . '"'
                        . ' data-role="' . e((string) $row->role) . '"'
                        . ' title="Edit user"><i class="bi bi-pencil"></i></button>';

                    if (!$isSelf && !$isProtectedLastSuperadmin) {
                        $buttons[] = $this->lifecycleButton($row, 'archive', 'secondary', 'archive', 'Arsipkan akun');
                    }
                }

                return '<div class="d-flex justify-content-end flex-wrap gap-2">'
                    . implode('', $buttons)
                    . '</div>';
            })
            ->rawColumns([
                'checkbox',
                'role',
                'status_badge',
                'aksi',
            ])
            ->make(true);
    }

    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        if ($user->role === 'santri' && empty($validated['kelas_id'])) {
            throw ValidationException::withMessages([
                'kelas_id' => ['Kelas wajib dipilih sebelum akun santri diaktifkan.'],
            ]);
        }

        DB::transaction(function () use ($request, $user, $validated): void {
            $this->syncRequiredProfile(
                $user,
                $validated['kelas_id'] ?? null
            );

            $this->lifecycle->approve($request->user(), $user);
        });

        return response()->json([
            'success' => true,
            'message' => 'Akun ' . $user->name . ' berhasil diaktifkan.',
        ]);
    }

    public function suspend(Request $request, int|string $id): JsonResponse
    {
        $validated = $this->validateReason($request);
        $user = User::query()->findOrFail($id);

        $this->lifecycle->suspend($request->user(), $user, $validated['reason']);

        return response()->json(['message' => 'Akun berhasil ditangguhkan.']);
    }

    public function reactivate(Request $request, int|string $id): JsonResponse
    {
        $validated = $this->validateReason($request);
        $user = User::query()->findOrFail($id);

        $this->lifecycle->reactivate($request->user(), $user, $validated['reason']);

        return response()->json(['message' => 'Akun berhasil diaktifkan kembali.']);
    }

    public function reject(Request $request, int|string $id): JsonResponse
    {
        $validated = $this->validateReason($request);
        $user = User::query()->findOrFail($id);

        $this->lifecycle->reject($request->user(), $user, $validated['reason']);

        return response()->json(['message' => 'Permohonan akun berhasil ditolak.']);
    }

    public function archive(Request $request, int|string $id): JsonResponse
    {
        return $this->destroy($request, $id);
    }

    public function restore(Request $request, int|string $id): JsonResponse
    {
        $validated = $this->validateReason($request);
        $user = User::query()->withTrashed()->findOrFail($id);

        $this->lifecycle->restore($request->user(), $user, $validated['reason']);

        return response()->json(['message' => 'Akun berhasil dipulihkan.']);
    }

    public function lifecycleLogs(Request $request, int|string $id): JsonResponse
    {
        $user = User::query()->withTrashed()->findOrFail($id);

        $logs = UserLifecycleLog::query()
            ->with('actor:id,name')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn(UserLifecycleLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'reason' => $log->reason,
                'actor' => $log->actor?->name ?? 'Sistem',
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->translatedFormat('d M Y, H:i'),
                'before_data' => $log->before_data,
                'after_data' => $log->after_data,
            ]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'account_status' => $user->account_status,
            ],
            'logs' => $logs,
        ]);
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $users = User::query()
            ->whereIn('id', $validated['ids'])
            ->get();

        $approved = 0;
        $skipped = [];

        foreach ($users as $user) {
            if ($user->account_status !== UserLifecycleService::STATUS_PENDING) {
                $skipped[] = $user->name . ' (status bukan pending)';
                continue;
            }

            if ($user->role === 'santri') {
                $skipped[] = $user->name . ' (kelas santri belum dipilih)';
                continue;
            }

            if ($user->role === 'superadmin') {
                $skipped[] = $user->name . ' (Super Admin wajib approval individual)';
                continue;
            }

            $this->syncRequiredProfile($user, null);
            $this->lifecycle->approve($request->user(), $user);
            $approved++;
        }

        return response()->json([
            'status' => 'success',
            'message' => $approved . ' akun berhasil disetujui.',
            'approved_count' => $approved,
            'skipped' => $skipped,
        ]);
    }

    public function bulkArchive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $users = User::query()
            ->whereIn('id', $validated['ids'])
            ->get();

        $archived = 0;
        $skipped = [];

        foreach ($users as $user) {
            try {
                $this->lifecycle->archive(
                    $request->user(),
                    $user,
                    $validated['reason']
                );
                $archived++;
            } catch (ValidationException $exception) {
                $skipped[] = $user->name . ' ('
                    . collect($exception->errors())->flatten()->first()
                    . ')';
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $archived . ' akun berhasil diarsipkan.',
            'archived_count' => $archived,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Endpoint route lama agar tidak memutus frontend lama.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        return $this->bulkArchive($request);
    }

    private function syncRequiredProfile(User $user, ?int $kelasId): void
    {
        if ($user->role === 'musyrif') {
            $musyrif = Musyrif::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['nama' => $user->name]
            );

            if (!$musyrif->kode) {
                $musyrif->kode = $this->generateKode(
                    $musyrif->nama,
                    (int) $musyrif->id
                );
                $musyrif->save();
            }
        }

        if ($user->role === 'santri') {
            if (!$kelasId) {
                throw ValidationException::withMessages([
                    'kelas_id' => ['Kelas wajib dipilih untuk membuat profil santri.'],
                ]);
            }

            Santri::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama' => $user->name,
                    'kelas_id' => $kelasId,
                ]
            );
        }
    }

    private function ensureRoleTransitionIsSafe(
        User $user,
        string $oldRole,
        string $newRole
    ): void {
        if ($oldRole === $newRole) {
            return;
        }

        if ((int) $user->id === (int) auth()->id()) {
            throw ValidationException::withMessages([
                'role' => ['Role akun yang sedang digunakan tidak dapat diubah.'],
            ]);
        }

        if (
            $oldRole === 'superadmin'
            && $newRole !== 'superadmin'
            && User::query()
            ->where('role', 'superadmin')
            ->where('account_status', UserLifecycleService::STATUS_ACTIVE)
            ->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => ['Role Super Admin aktif terakhir tidak dapat diturunkan.'],
            ]);
        }

        if (
            in_array($oldRole, self::STRUCTURAL_ROLES, true)
            || in_array($newRole, self::STRUCTURAL_ROLES, true)
        ) {
            throw ValidationException::withMessages([
                'role' => [
                    'Perubahan role yang melibatkan Santri atau Musyrif harus menggunakan workflow khusus agar profil dan riwayat tetap aman.',
                ],
            ]);
        }
    }

    private function validateReason(Request $request): array
    {
        return $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
    }

    private function statusBadge(User $user): string
    {
        $status = (string) ($user->account_status ?: UserLifecycleService::STATUS_PENDING);

        $config = match ($status) {
            UserLifecycleService::STATUS_ACTIVE => ['success', 'check-circle-fill', 'Aktif'],
            UserLifecycleService::STATUS_SUSPENDED => ['danger', 'pause-circle-fill', 'Suspended'],
            UserLifecycleService::STATUS_REJECTED => ['dark', 'x-circle-fill', 'Rejected'],
            UserLifecycleService::STATUS_ARCHIVED => ['secondary', 'archive-fill', 'Archived'],
            default => ['warning', 'clock-history', 'Pending'],
        };

        [$color, $icon, $label] = $config;
        $textClass = $color === 'warning' ? ' text-dark' : '';

        return '<span class="badge bg-' . $color . $textClass
            . ' rounded-pill px-3 py-2">'
            . '<i class="bi bi-' . $icon . ' me-1"></i>'
            . $label
            . '</span>';
    }

    private function lifecycleButton(
        User $user,
        string $action,
        string $color,
        string $icon,
        string $title
    ): string {
        return '<button class="btn btn-sm btn-' . $color
            . ' text-white btn-lifecycle"'
            . ' data-id="' . $user->id . '"'
            . ' data-name="' . e($user->name) . '"'
            . ' data-action="' . e($action) . '"'
            . ' title="' . e($title) . '">'
            . '<i class="bi bi-' . e($icon) . '"></i>'
            . '</button>';
    }
}
