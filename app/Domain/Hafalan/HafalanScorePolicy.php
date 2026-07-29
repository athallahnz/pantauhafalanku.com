<?php

declare(strict_types=1);

namespace App\Domain\Hafalan;

final class HafalanScorePolicy
{
    public const SCORED_STATUS = 'lulus';

    /** @var list<string> */
    public const VALID_STATUSES = [
        'lulus',
        'ulang',
        'hadir_tidak_setor',
        'alpha',
        'izin',
        'sakit',
    ];

    /**
     * Nilai yang boleh dipilih untuk input baru.
     * Mardud tidak tersedia untuk input baru karena bertentangan dengan status lulus.
     *
     * @var list<string>
     */
    public const INPUT_SCORE_LABELS = [
        'mumtaz',
        'jayyid_jiddan',
        'jayyid',
    ];

    /**
     * Mardud dipertahankan hanya untuk membaca histori lama.
     *
     * @var list<string>
     */
    public const STORED_SCORE_LABELS = [
        'mumtaz',
        'jayyid_jiddan',
        'jayyid',
        'mardud',
    ];

    /** @var array<string, int> */
    public const SCORE_MAP = [
        'mumtaz' => 95,
        'jayyid_jiddan' => 85,
        'jayyid' => 75,
        'mardud' => 65,
    ];

    public static function statusHasScore(?string $status): bool
    {
        return $status === self::SCORED_STATUS;
    }

    /**
     * Menormalkan payload sebelum disimpan.
     * Semua status selain lulus harus bebas dari nilai label dan nilai angka legacy.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $payload): array
    {
        $status = isset($payload['status'])
            ? trim((string) $payload['status'])
            : null;

        if (!self::statusHasScore($status)) {
            $payload['nilai_label'] = null;
            $payload['nilai'] = null;
        }

        return $payload;
    }

    public static function numericScore(
        ?string $status,
        ?string $scoreLabel
    ): ?int {
        if (!self::statusHasScore($status) || $scoreLabel === null) {
            return null;
        }

        return self::SCORE_MAP[$scoreLabel] ?? null;
    }

    public static function isLegacyMardudPass(
        ?string $status,
        ?string $scoreLabel
    ): bool {
        return $status === self::SCORED_STATUS
            && $scoreLabel === 'mardud';
    }
}
