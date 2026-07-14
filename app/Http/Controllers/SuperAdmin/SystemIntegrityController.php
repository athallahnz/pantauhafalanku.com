<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\SystemIntegrityRepairLog;
use App\Services\UserProfileConsistencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Yajra\DataTables\Facades\DataTables;

class SystemIntegrityController extends Controller
{
    public function __construct(
        private readonly UserProfileConsistencyService $consistency
    ) {
    }

    public function index()
    {
        $scan = $this->consistency->scan(false);

        $kelasList = Kelas::query()
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas']);

        $musyrifList = Musyrif::query()
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $latestRepairs = SystemIntegrityRepairLog::query()
            ->with('actor:id,name')
            ->latest('id')
            ->limit(8)
            ->get();

        return view(
            'superadmin.system-integrity.index',
            compact(
                'scan',
                'kelasList',
                'musyrifList',
                'latestRepairs'
            )
        );
    }

    public function data(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $severity = $request->string('severity')->trim()->toString();
        $category = $request->string('category')->trim()->toString();
        $repairability = $request->string('repairability')->trim()->toString();

        /** @var Collection<int,array<string,mixed>> $issues */
        $issues = collect($this->consistency->scan(false)['issues']);

        if ($severity !== '') {
            $issues = $issues->where('severity', $severity);
        }

        if ($category !== '') {
            $issues = $issues->where('category', $category);
        }

        if ($repairability === 'repairable') {
            $issues = $issues->where('repairable', true);
        } elseif ($repairability === 'safe') {
            $issues = $issues->where('safe_auto_repair', true);
        } elseif ($repairability === 'manual') {
            $issues = $issues->where('repairable', false);
        }

        return DataTables::of($issues->values())
            ->addIndexColumn()
            ->make(true);
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => $this->consistency->scan(false),
        ]);
    }

    public function repair(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'issue_type' => ['required', 'string', 'max:100'],
            'entity_id' => ['required', 'integer', 'min:1'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'musyrif_id' => ['nullable', 'integer', 'exists:musyrifs,id'],
        ]);

        $result = $this->consistency->repair(
            $request->user(),
            $validated['issue_type'],
            (int) $validated['entity_id'],
            [
                'kelas_id' => $validated['kelas_id'] ?? null,
                'musyrif_id' => $validated['musyrif_id'] ?? null,
            ]
        );

        return response()->json($result);
    }

    public function repairSafe(Request $request): JsonResponse
    {
        $result = $this->consistency->repairAllSafe($request->user());

        return response()->json([
            'status' => $result['failed'] > 0 ? 'warning' : 'success',
            'message' => sprintf(
                '%d berhasil diperbaiki, %d dilewati, %d gagal.',
                $result['success'],
                $result['skipped'],
                $result['failed']
            ),
            'data' => $result,
        ]);
    }

    public function logs(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $query = SystemIntegrityRepairLog::query()
            ->with('actor:id,name')
            ->latest('id');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn(
                'actor_name',
                fn(SystemIntegrityRepairLog $log): string =>
                    $log->actor?->name ?? 'System'
            )
            ->editColumn(
                'created_at',
                fn(SystemIntegrityRepairLog $log): string =>
                    $log->created_at?->translatedFormat('d M Y H:i') ?? '-'
            )
            ->make(true);
    }
}
