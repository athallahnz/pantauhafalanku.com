<?php

namespace App\Services\Academic;

use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemesterLifecycleService
{
    public function lockInput(Semester $semester): Semester
    {
        return DB::transaction(function () use ($semester) {
            $semester = Semester::query()
                ->lockForUpdate()
                ->findOrFail($semester->id);

            if (!$semester->isActive()) {
                throw ValidationException::withMessages([
                    'semester' => [
                        'Hanya semester aktif yang dapat dikunci inputnya.',
                    ],
                ]);
            }

            if ($semester->isInputLocked()) {
                return $semester;
            }

            $semester->forceFill([
                'input_locked_at' => now(),
            ])->save();

            return $semester->fresh('tahunAjaran');
        });
    }

    public function unlockInput(Semester $semester): Semester
    {
        return DB::transaction(function () use ($semester) {
            $semester = Semester::query()
                ->lockForUpdate()
                ->findOrFail($semester->id);

            if (!$semester->isActive()) {
                throw ValidationException::withMessages([
                    'semester' => [
                        'Hanya semester aktif yang dapat dibuka kembali inputnya.',
                    ],
                ]);
            }

            $semester->forceFill([
                'input_locked_at' => null,
            ])->save();

            return $semester->fresh('tahunAjaran');
        });
    }

    /**
     * Mengaktifkan semester draft.
     *
     * Jika sudah ada semester aktif, semester lama wajib dikunci terlebih dahulu.
     * Aktivasi target otomatis menutup semester lama.
     */
    public function activate(Semester $target): Semester
    {
        return DB::transaction(function () use ($target) {
            $semesters = Semester::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $target = $semesters->firstWhere('id', $target->id);

            if (!$target) {
                throw ValidationException::withMessages([
                    'semester' => ['Semester tujuan tidak ditemukan.'],
                ]);
            }

            if (!$target->isDraft()) {
                throw ValidationException::withMessages([
                    'semester' => [
                        'Hanya semester berstatus draft yang dapat diaktifkan.',
                    ],
                ]);
            }

            /** @var Semester|null $current */
            $current = $semesters->first(
                fn (Semester $semester) => $semester->isActive()
            );

            if ($current && !$current->isInputLocked()) {
                throw ValidationException::withMessages([
                    'semester' => [
                        'Input semester aktif harus dikunci sebelum semester baru diaktifkan.',
                    ],
                ]);
            }

            if ($current) {
                $current->forceFill([
                    'status' => Semester::STATUS_CLOSED,
                    'is_active' => false,
                    'input_locked_at' => $current->input_locked_at ?? now(),
                    'closed_at' => now(),
                ])->save();
            }

            /*
             * Defensive cleanup untuk data legacy yang mungkin masih
             * memiliki is_active=true tetapi statusnya tidak konsisten.
             */
            Semester::query()
                ->where('id', '!=', $target->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                ]);

            $target->forceFill([
                'status' => Semester::STATUS_ACTIVE,
                'is_active' => true,
                'input_locked_at' => null,
                'activated_at' => now(),
                'closed_at' => null,
            ])->save();

            return $target->fresh('tahunAjaran');
        });
    }
}
