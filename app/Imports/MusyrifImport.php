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
    public function onRow(Row $row): void
    {
        $rowNumber = $row->getIndex();
        $data = $this->normalizeRow($row->toArray());

        try {
            Validator::make($data, [
                'nama' => ['required', 'string', 'max:150'],
                'jenis_kelamin' => ['required'],
                'kode' => ['nullable', 'string', 'max:50'],
                'kelas' => ['nullable'],
                'pendidikan_terakhir' => ['nullable', 'in:SMA,D3,S1,S2'],
                'domisili' => ['nullable', 'in:Dalam Pondok (Mukim),Luar Pondok (Pulang-Pergi)'],
                'halaqah' => ['nullable', 'in:Reguler,Takhassus,Pengganti'],
                'alamat' => ['nullable', 'string'],
                'keterangan' => ['nullable', 'string'],
                'metode_alquran' => ['nullable', 'string', 'max:255'],
                'is_sertifikasi_ummi' => ['nullable'],
                'tahun_sertifikasi' => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
                'email' => ['nullable', 'email', 'max:255'],
                'password' => ['nullable', 'string', 'min:8'],
            ], [
                'nama.required' => 'Kolom nama wajib diisi.',
                'jenis_kelamin.required' => 'Jenis kelamin wajib diisi.',
                'pendidikan_terakhir.in' => 'Pendidikan terakhir harus SMA, D3, S1, atau S2.',
                'domisili.in' => 'Nilai domisili tidak sesuai pilihan template.',
                'halaqah.in' => 'Nilai halaqah tidak sesuai pilihan template.',
                'email.email' => 'Format email tidak valid.',
                'password.min' => 'Password minimal 8 karakter.',
            ])->validate();

            $gender = $this->normalizeGender($data['jenis_kelamin']);

            if (!$gender) {
                throw ValidationException::withMessages([
                    'jenis_kelamin' => ['Jenis kelamin harus Laki-laki/Putra atau Perempuan/Putri.'],
                ]);
            }

            DB::transaction(function () use ($data, $rowNumber, $gender): void {
                $kelasId = $this->resolveOperationalKelasId($data['kelas'], $rowNumber);
                $userId = $this->resolveUserId($data, $rowNumber);

                $identity = !empty($data['kode'])
                    ? ['kode' => $data['kode']]
                    : ($userId !== null ? ['user_id' => $userId] : null);

                $musyrif = $identity
                    ? Musyrif::query()->firstOrNew($identity)
                    : new Musyrif();

                $musyrif->forceFill([
                    'user_id' => $userId,
                    'kelas_id' => $kelasId,
                    'nama' => $data['nama'],
                    'jenis_kelamin' => $gender,
                    'kode' => $data['kode'],
                    'alamat' => $data['alamat'],
                    'pendidikan_terakhir' => $data['pendidikan_terakhir'],
                    'domisili' => $data['domisili'],
                    'halaqah' => $data['halaqah'],
                    'metode_alquran' => $data['metode_alquran'],
                    'is_sertifikasi_ummi' => $this->toBoolean($data['is_sertifikasi_ummi']),
                    'tahun_sertifikasi' => $data['tahun_sertifikasi'],
                    'keterangan' => $data['keterangan'],
                ])->save();
            });
        } catch (ValidationException $exception) {
            $messages = collect($exception->errors())->flatten()->implode(' ');

            throw ValidationException::withMessages([
                "baris_{$rowNumber}" => "Baris {$rowNumber}: {$messages}",
            ]);
        }
    }

    private function normalizeRow(array $row): array
    {
        $keys = [
            'nama',
            'jenis_kelamin',
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
            $value = is_string($value) ? trim($value) : $value;
            $normalized[$key] = $value === '' ? null : $value;
        }

        if (!empty($normalized['email'])) {
            $normalized['email'] = Str::lower($normalized['email']);
        }

        return $normalized;
    }

    private function resolveOperationalKelasId(mixed $kelasValue, int $rowNumber): ?int
    {
        if ($kelasValue === null || $kelasValue === '') {
            return null;
        }

        $query = Kelas::query()->with('parent:id,is_active')->operasional();

        $kelas = is_numeric($kelasValue)
            ? $query->whereKey((int) $kelasValue)->first()
            : $query->whereRaw(
                'LOWER(TRIM(nama_kelas)) = ?',
                [Str::lower(trim((string) $kelasValue))]
            )->first();

        if (!$kelas) {
            throw ValidationException::withMessages([
                'kelas' => ["Kelas '{$kelasValue}' pada baris {$rowNumber} tidak ditemukan atau bukan kelompok aktif. Gunakan contoh seperti Kelas 7 A."],
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
                    'password' => ["Password minimal 8 karakter wajib diisi pada baris {$rowNumber} karena email tersebut belum terdaftar."],
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

    private function normalizeGender(mixed $value): ?string
    {
        $value = Str::lower(trim((string) $value));
        $value = str_replace(['_', ' '], '-', $value);

        return match ($value) {
            'l', 'lk', 'laki', 'laki-laki', 'putra', 'male' => 'L',
            'p', 'pr', 'perempuan', 'putri', 'female' => 'P',
            default => null,
        };
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(Str::lower(trim((string) ($value ?? '0'))), [
            '1', 'ya', 'yes', 'true', 'sudah', 'sudah sertifikasi',
        ], true);
    }
}
