<?php

namespace App\Services;

use App\Models\HafalanTemplate;
use App\Models\Surah;
use App\Models\SurahSegment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TilawahProgressService
{
    private const SCHEMA_V1 = 'tilawah.v1';

    private const SCHEMA_V2 = 'tilawah.v2';

    private const MODES = ['group', 'catchup'];

    private ?Collection $surahCache = null;

    /**
     * @return Collection<int, Surah>
     */
    public function surahs(): Collection
    {
        return $this->surahCache ??= Surah::query()
            ->orderBy('id')
            ->get(['id', 'nama', 'jumlah_ayat']);
    }

    /**
     * @return array{surah_id:int,surah:string,ayat:int,quran_index:int}
     */
    public function makePoint(int $surahId, int $ayat, string $field): array
    {
        $surah = $this->surahs()->firstWhere('id', $surahId);

        if (!$surah) {
            throw ValidationException::withMessages([
                $field => 'Surat yang dipilih tidak ditemukan.',
            ]);
        }

        $jumlahAyat = (int) $surah->jumlah_ayat;

        if ($jumlahAyat < 1) {
            throw ValidationException::withMessages([
                $field => 'Jumlah ayat surat belum dikonfigurasi. Jalankan pembaruan data surat terlebih dahulu.',
            ]);
        }

        if ($ayat < 1 || $ayat > $jumlahAyat) {
            throw ValidationException::withMessages([
                $field => "Ayat harus berada antara 1 sampai {$jumlahAyat} untuk surat {$surah->nama}.",
            ]);
        }

        $previousAyatCount = (int) $this->surahs()
            ->where('id', '<', $surahId)
            ->sum('jumlah_ayat');

        return [
            'surah_id' => (int) $surah->id,
            'surah' => (string) $surah->nama,
            'ayat' => $ayat,
            'quran_index' => $previousAyatCount + $ayat,
        ];
    }

    /**
     * @return array{surah_id:int,surah:string,ayat:int,quran_index:int}
     */
    public function pointFromIndex(int $quranIndex, string $field = 'ayat'): array
    {
        if ($quranIndex < 1) {
            throw ValidationException::withMessages([
                $field => 'Indeks ayat Al-Qur\'an tidak valid.',
            ]);
        }

        $remaining = $quranIndex;

        foreach ($this->surahs() as $surah) {
            $jumlahAyat = (int) $surah->jumlah_ayat;

            if ($remaining <= $jumlahAyat) {
                return $this->makePoint((int) $surah->id, $remaining, $field);
            }

            $remaining -= $jumlahAyat;
        }

        throw ValidationException::withMessages([
            $field => 'Indeks ayat melewati akhir Al-Qur\'an.',
        ]);
    }

    /**
     * @param array{surah_id:int,surah:string,ayat:int,quran_index:int} $from
     * @param array{surah_id:int,surah:string,ayat:int,quran_index:int} $to
     * @param array<string, mixed>|null $baseline
     * @return array<string, mixed>
     */
    public function buildPayload(
        array $from,
        array $to,
        ?string $note,
        string $mode = 'group',
        ?array $baseline = null
    ): array {
        if ($from['quran_index'] > $to['quran_index']) {
            throw ValidationException::withMessages([
                'to_ayat' => 'Titik sampai tidak boleh berada sebelum titik mulai.',
            ]);
        }

        if (!in_array($mode, self::MODES, true)) {
            throw ValidationException::withMessages([
                'mode' => 'Mode pencatatan Tilawah tidak valid.',
            ]);
        }

        return [
            'schema' => self::SCHEMA_V2,
            'mode' => $mode,
            'baseline' => $baseline,
            'from' => $from,
            'to' => $to,
            'total_ayat' => $to['quran_index'] - $from['quran_index'] + 1,
            'note' => $this->normalizeNote($note),
        ];
    }

    /**
     * @param array{surah_id:int,surah:string,ayat:int,quran_index:int} $point
     */
    public function resolveEndpointTemplate(array $point): HafalanTemplate
    {
        $ayat = $point['ayat'];

        $template = HafalanTemplate::query()
            ->where('tahap', 'harian')
            ->whereHas('segments', function ($query) use ($point, $ayat): void {
                $query
                    ->where('surah_id', $point['surah_id'])
                    ->where('ayat_awal', '<=', $ayat)
                    ->where(function ($rangeQuery) use ($ayat): void {
                        $rangeQuery
                            ->where('ayat_akhir', 0)
                            ->orWhere('ayat_akhir', '>=', $ayat);
                    });
            })
            ->orderBy('juz')
            ->orderBy('urutan')
            ->first();

        if (!$template) {
            /*
             * JSON progress adalah sumber ukur utama. hafalan_template_id
             * hanya bookmark kompatibilitas untuk tabel tilawahs lama.
             * Jika master lama mempunyai celah mapping, gunakan segmen harian
             * terdekat pada surat yang sama agar pencatatan per ayat tidak
             * terblokir.
             */
            $nearestSegment = SurahSegment::query()
                ->where('surah_id', $point['surah_id'])
                ->whereHas('template', fn($query) =>
                    $query->where('tahap', 'harian')
                )
                ->with('template')
                ->orderByRaw(
                    'CASE '
                    . 'WHEN ayat_akhir = 0 THEN 0 '
                    . 'WHEN ayat_akhir < ? THEN ? - ayat_akhir '
                    . 'WHEN ayat_awal > ? THEN ayat_awal - ? '
                    . 'ELSE 0 END',
                    [$ayat, $ayat, $ayat, $ayat]
                )
                ->orderBy('ayat_awal')
                ->first();

            $template = $nearestSegment?->template;
        }

        if (!$template) {
            throw ValidationException::withMessages([
                'to_ayat' => "Surat {$point['surah']} belum memiliki bookmark template Tilawah harian.",
            ]);
        }

        return $template;
    }

    /**
     * Membaca schema v1 dan v2. Mode v1 "lanjut" dinormalisasi menjadi group.
     *
     * @return array<string, mixed>|null
     */
    public function parse(?string $catatan): ?array
    {
        if (!$catatan) {
            return null;
        }

        $payload = json_decode($catatan, true);
        $schema = is_array($payload) ? ($payload['schema'] ?? null) : null;

        if (
            !is_array($payload)
            || !in_array($schema, [self::SCHEMA_V1, self::SCHEMA_V2], true)
            || !is_array($payload['from'] ?? null)
            || !is_array($payload['to'] ?? null)
        ) {
            return null;
        }

        $payload['mode'] = ($payload['mode'] ?? null) === 'catchup'
            ? 'catchup'
            : 'group';
        $payload['baseline'] = is_array($payload['baseline'] ?? null)
            ? $payload['baseline']
            : null;

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function isGroupPayload(array $payload): bool
    {
        return ($payload['mode'] ?? 'group') === 'group';
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{surah_id:int,surah:string,ayat:int,quran_index:int}|null
     */
    public function nextPoint(array $payload): ?array
    {
        $to = $payload['to'] ?? null;

        if (!is_array($to)) {
            return null;
        }

        return $this->nextPointFromPoint($to);
    }

    /**
     * @param array{surah_id:int,surah:string,ayat:int,quran_index:int} $point
     * @return array{surah_id:int,surah:string,ayat:int,quran_index:int}|null
     */
    public function nextPointFromPoint(array $point): ?array
    {
        $surahId = (int) ($point['surah_id'] ?? 0);
        $ayat = (int) ($point['ayat'] ?? 0);
        $surah = $this->surahs()->firstWhere('id', $surahId);

        if (!$surah || $ayat < 1) {
            return null;
        }

        if ($ayat < (int) $surah->jumlah_ayat) {
            return $this->makePoint($surahId, $ayat + 1, 'from_ayat');
        }

        $nextSurah = $this->surahs()
            ->where('id', '>', $surahId)
            ->sortBy('id')
            ->first();

        if (!$nextSurah) {
            return null;
        }

        return $this->makePoint((int) $nextSurah->id, 1, 'from_ayat');
    }

    /**
     * @param array{surah_id:int,surah:string,ayat:int,quran_index:int} $point
     * @return array{surah_id:int,surah:string,ayat:int,quran_index:int}|null
     */
    public function previousPoint(array $point): ?array
    {
        $index = (int) ($point['quran_index'] ?? 0);

        return $index > 1
            ? $this->pointFromIndex($index - 1, 'from_ayat')
            : null;
    }

    public function rangeLabel(?string $catatan): ?string
    {
        $payload = $this->parse($catatan);

        if (!$payload) {
            return null;
        }

        $from = $payload['from'];
        $to = $payload['to'];

        if (($from['surah_id'] ?? null) === ($to['surah_id'] ?? null)) {
            return sprintf(
                '%s:%d–%d',
                $from['surah'] ?? '-',
                (int) ($from['ayat'] ?? 0),
                (int) ($to['ayat'] ?? 0)
            );
        }

        return sprintf(
            '%s:%d – %s:%d',
            $from['surah'] ?? '-',
            (int) ($from['ayat'] ?? 0),
            $to['surah'] ?? '-',
            (int) ($to['ayat'] ?? 0)
        );
    }

    public function note(?string $catatan): ?string
    {
        $payload = $this->parse($catatan);

        if (!$payload) {
            return $this->normalizeNote($catatan);
        }

        return $this->normalizeNote($payload['note'] ?? null);
    }

    public function display(?string $catatan): string
    {
        $payload = $this->parse($catatan);

        if (!$payload) {
            return $this->normalizeNote($catatan) ?? '-';
        }

        $range = $this->rangeLabel($catatan) ?? '-';
        $total = (int) ($payload['total_ayat'] ?? 0);
        $note = $this->note($catatan);
        $display = ($payload['mode'] ?? 'group') === 'catchup'
            ? 'Susulan · '
            : '';

        $baselineThrough = $payload['baseline']['through'] ?? null;
        if (is_array($baselineThrough)) {
            $display .= sprintf(
                'Baseline lama s.d. %s:%d · ',
                $baselineThrough['surah'] ?? '-',
                (int) ($baselineThrough['ayat'] ?? 0)
            );
        }

        $display .= $range;

        if ($total > 0) {
            $display .= " ({$total} ayat)";
        }

        if ($note) {
            $display .= ' | ' . $note;
        }

        return $display;
    }

    public function withNote(?string $catatan, ?string $note): ?string
    {
        $payload = $this->parse($catatan);

        if (!$payload) {
            return $this->normalizeNote($note);
        }

        $payload['note'] = $this->normalizeNote($note);

        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function normalizeNote(mixed $note): ?string
    {
        if (!is_string($note)) {
            return null;
        }

        $note = trim($note);

        return $note !== '' ? $note : null;
    }
}
