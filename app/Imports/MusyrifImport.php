<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class MusyrifImport implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    /**
     * Header yang didukung:
     * nama, kode, kelas, pendidikan_terakhir, domisili, halaqah,
     * alamat, keterangan, metode_alquran, is_sertifikasi_ummi,
     * tahun_sertifikasi, email, password.
     */
    public function onRow(Row $row): void
    {
        $rowNumber = $row->getIndex();
        $data = $this->normalizeRow($row->toArray());

        try {
            Validator::make(
                $data,
                [
                    'nama' => ['required', 'string', 'max:150'],
                    'kode' => ['nullable', 'string', 'max:50'],
                    'kelas' => ['nullable'],
                    'pendidikan_terakhir' => ['nullable', 'in:SMA,D3,S1,S2'],
                    'domisili' => [
                        'nullable',
                        'in:Dalam Pondok (Mukim),Luar Pondok (Pulang-Pergi)',
                    ],
                    'halaqah' => ['nullable', 'in:Reguler,Takhassus,Pengganti'],
                    'alamat' => ['nullable', 'string'],
                    'keterangan' => ['nullable', 'string'],
                    'metode_alquran' => ['nullable', 'string', 'max:100'],
                    'is_sertifikasi_ummi' => ['nullable'],
                    'tahun_sertifikasi' => [
                        'nullable',
                        'integer',
                        'min:1900',
                        'max:' . (now()->year + 1),
                    ],
                    'email' => ['nullable', 'email', 'max:255'],
                    'password' => ['nullable', 'string', 'min:8'],
                ],
                [
                    'nama.required' => 'Kolom nama wajib diisi.',
                    'pendidikan_terakhir.in' => 'Pendidikan terakhir harus SMA, D3, S1, atau S2.',
                    'domisili.in' => 'Nilai domisili tidak sesuai pilihan template.',
                    'halaqah.in' => 'Nilai halaqah tidak sesuai pilihan template.',
                    'email.email' => 'Format email tidak valid.',
                    'password.min' => 'Password minimal 8 karakter.',
                ]
            )->validate();

            DB::transaction(function () use ($data, $rowNumber): void {
                $kelasId = $this->resolveKelasId($data['kelas'], $rowNumber);
                $userId = $this->resolveUserId($data, $rowNumber);

                $identity = null;

                if (!empty($data['kode'])) {
                    $identity = ['kode' => $data['kode']];
                } elseif ($userId !== null) {
                    $identity = ['user_id' => $userId];
                }

                $musyrif = $identity
                    ? Musyrif::firstOrNew($identity)
                    : new Musyrif();

                $musyrif->fill([
                    'user_id' => $userId,
                    'kelas_id' => $kelasId,
                    'nama' => $data['nama'],
                    'kode' => $data['kode'],
                    'alamat' => $data['alamat'],
                    'pendidikan_terakhir' => $data['pendidikan_terakhir'],
                    'domisili' => $data['domisili'],
                    'halaqah' => $data['halaqah'],
                    'metode_alquran' => $data['metode_alquran'],
                    'is_sertifikasi_ummi' => $this->toBoolean($data['is_sertifikasi_ummi']),
                    'tahun_sertifikasi' => $data['tahun_sertifikasi'],
                    'keterangan' => $data['keterangan'],
                ]);

                $musyrif->save();
            });
        } catch (ValidationException $exception) {
            $messages = collect($exception->errors())
                ->flatten()
                ->implode(' ');

            throw ValidationException::withMessages([
                "baris_{$rowNumber}" => "Baris {$rowNumber}: {$messages}",
            ]);
        }
    }

    private function normalizeRow(array $row): array
    {
        $keys = [
            'nama',
            'kode',
            'kelas',
            'pendidikan_terakhir',
            'domisili',
            'halaqah',
            'alamat',
            'keterangan',
            'metode_alquran',
            'is_sertifikasi_ummi',
            'tahun_sertifikasi',
            'email',
            'password',
        ];

        $normalized = [];

        foreach ($keys as $key) {
            $value = $row[$key] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $normalized[$key] = $value === '' ? null : $value;
        }

        if (!empty($normalized['email'])) {
            $normalized['email'] = Str::lower($normalized['email']);
        }

        return $normalized;
    }

    private function resolveKelasId(mixed $kelasValue, int $rowNumber): ?int
    {
        if ($kelasValue === null || $kelasValue === '') {
            return null;
        }

        if (is_numeric($kelasValue)) {
            $kelas = Kelas::query()->find((int) $kelasValue);
        } else {
            $kelas = Kelas::query()
                ->whereRaw('LOWER(TRIM(nama_kelas)) = ?', [Str::lower(trim((string) $kelasValue))])
                ->first();
        }

        if (!$kelas) {
            throw ValidationException::withMessages([
                'kelas' => "Kelas '{$kelasValue}' pada baris {$rowNumber} tidak ditemukan. Gunakan pilihan kelas dari template.",
            ]);
        }

        return (int) $kelas->id;
    }

    private function resolveUserId(array $data, int $rowNumber): ?int
    {
        if (empty($data['email'])) {
            return null;
        }

        $user = User::query()->where('email', $data['email'])->first();

        if (!$user) {
            if (empty($data['password']) || mb_strlen((string) $data['password']) < 8) {
                throw ValidationException::withMessages([
                    'password' => "Password minimal 8 karakter wajib diisi pada baris {$rowNumber} karena email tersebut belum terdaftar.",
                ]);
            }

            $user = new User();
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
        } elseif (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->name = $data['nama'];
        $user->role = 'musyrif';
        $user->save();

        return (int) $user->id;
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) ($value ?? '0')));

        return in_array($normalized, [
            '1',
            'ya',
            'yes',
            'true',
            'sudah',
            'sudah sertifikasi',
        ], true);
    }
}
