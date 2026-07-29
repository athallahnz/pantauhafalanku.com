<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Hafalan\HafalanScorePolicy;
use PHPUnit\Framework\TestCase;

final class HafalanScorePolicyTest extends TestCase
{
    public function test_only_lulus_status_has_score(): void
    {
        self::assertTrue(HafalanScorePolicy::statusHasScore('lulus'));

        foreach (['ulang', 'hadir_tidak_setor', 'alpha', 'izin', 'sakit'] as $status) {
            self::assertFalse(
                HafalanScorePolicy::statusHasScore($status),
                "Status {$status} tidak boleh memiliki nilai."
            );
        }
    }

    public function test_normalize_payload_clears_scores_from_non_lulus(): void
    {
        $payload = HafalanScorePolicy::normalizePayload([
            'status' => 'ulang',
            'nilai_label' => 'jayyid',
            'nilai' => 75,
        ]);

        self::assertNull($payload['nilai_label']);
        self::assertNull($payload['nilai']);
    }

    public function test_normalize_payload_preserves_lulus_score(): void
    {
        $payload = HafalanScorePolicy::normalizePayload([
            'status' => 'lulus',
            'nilai_label' => 'mumtaz',
            'nilai' => 95,
        ]);

        self::assertSame('mumtaz', $payload['nilai_label']);
        self::assertSame(95, $payload['nilai']);
    }

    public function test_numeric_score_is_null_for_ulang_even_with_legacy_label(): void
    {
        self::assertNull(
            HafalanScorePolicy::numericScore('ulang', 'jayyid')
        );
    }

    public function test_numeric_score_maps_lulus_labels_consistently(): void
    {
        self::assertSame(95, HafalanScorePolicy::numericScore('lulus', 'mumtaz'));
        self::assertSame(85, HafalanScorePolicy::numericScore('lulus', 'jayyid_jiddan'));
        self::assertSame(75, HafalanScorePolicy::numericScore('lulus', 'jayyid'));
        self::assertSame(65, HafalanScorePolicy::numericScore('lulus', 'mardud'));
    }

    public function test_mardud_is_legacy_storage_label_not_new_input_option(): void
    {
        self::assertNotContains('mardud', HafalanScorePolicy::INPUT_SCORE_LABELS);
        self::assertContains('mardud', HafalanScorePolicy::STORED_SCORE_LABELS);
    }
}
