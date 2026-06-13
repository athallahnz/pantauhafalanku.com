<?php

namespace App\Support\Academic;

use App\Models\Semester;
use Illuminate\Validation\ValidationException;

trait ResolvesActiveSemester
{
    protected function activeSemesterForInput(): Semester
    {
        $semesters = Semester::query()
            ->with('tahunAjaran')
            ->active()
            ->get();

        if ($semesters->isEmpty()) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Belum ada semester aktif. Aktifkan semester terlebih dahulu.',
                ],
            ]);
        }

        if ($semesters->count() > 1) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Ditemukan lebih dari satu semester aktif. Hubungi Admin.',
                ],
            ]);
        }

        /** @var Semester $semester */
        $semester = $semesters->first();

        if ($semester->isInputLocked()) {
            $label = trim(
                ($semester->nama ?? '') . ' ' .
                ($semester->tahunAjaran?->nama ?? '')
            );

            throw ValidationException::withMessages([
                'semester_id' => [
                    "Input akademik semester {$label} sedang dikunci karena proses pergantian semester.",
                ],
            ]);
        }

        return $semester;
    }

    protected function activeSemesterId(): int
    {
        return (int) $this->activeSemesterForInput()->id;
    }

    protected function assertAcademicInputOpen(): Semester
    {
        return $this->activeSemesterForInput();
    }

    /**
     * Menolak perubahan data semester lama.
     *
     * Record lama dengan semester_id null masih boleh dipulihkan ke
     * semester aktif untuk kompatibilitas data legacy.
     */
    protected function assertRecordEditableInActiveSemester(
        ?int $recordSemesterId
    ): Semester {
        $activeSemester = $this->activeSemesterForInput();

        if (
            $recordSemesterId !== null
            && (int) $recordSemesterId !== (int) $activeSemester->id
        ) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Data dari semester lama sudah dikunci dan tidak dapat diubah.',
                ],
            ]);
        }

        return $activeSemester;
    }
}
