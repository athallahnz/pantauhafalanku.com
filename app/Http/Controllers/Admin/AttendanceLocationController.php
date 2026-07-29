<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceLocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = AttendanceLocation::query()
            ->ordered()
            ->get([
                'id',
                'name',
                'address',
                'latitude',
                'longitude',
                'radius_m',
                'color',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ]);

        return response()->json([
            'data' => $locations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateLocation($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        $location = AttendanceLocation::create($validated);

        return response()->json([
            'message' => 'Lokasi absensi berhasil ditambahkan.',
            'data' => $location,
        ], 201);
    }

    public function update(Request $request, AttendanceLocation $attendanceLocation): JsonResponse
    {
        $validated = $this->validateLocation($request);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_by'] = $request->user()?->id;

        $attendanceLocation->update($validated);

        return response()->json([
            'message' => 'Lokasi absensi berhasil diperbarui.',
            'data' => $attendanceLocation->fresh(),
        ]);
    }

    public function destroy(AttendanceLocation $attendanceLocation): JsonResponse
    {
        $attendanceLocation->delete();

        return response()->json([
            'message' => 'Lokasi absensi berhasil dihapus.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_m' => ['required', 'integer', 'between:20,5000'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
        ], [
            'latitude.between' => 'Latitude harus berada pada rentang -90 sampai 90.',
            'longitude.between' => 'Longitude harus berada pada rentang -180 sampai 180.',
            'radius_m.between' => 'Radius harus antara 20 sampai 5.000 meter.',
            'color.regex' => 'Warna lokasi tidak valid.',
        ]);
    }
}
