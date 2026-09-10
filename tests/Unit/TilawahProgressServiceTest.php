<?php

namespace Tests\Unit;

use App\Models\Tilawah;
use App\Services\TilawahProgressService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TilawahProgressServiceTest extends TestCase
{
    public function test_individual_continuation_payload_contributes_to_progress(): void
    {
        $service = new TilawahProgressService();
        $payload = $service->buildPayload(
            $this->point(1),
            $this->point(7),
            'Bacaan mandiri',
            Tilawah::ENTRY_TYPE_INDIVIDUAL,
            null,
            Tilawah::PURPOSE_CONTINUATION
        );

        $this->assertSame('tilawah.v2', $payload['schema']);
        $this->assertSame('individual', $payload['mode']);
        $this->assertSame('continuation', $payload['reading_purpose']);
        $this->assertSame(7, $payload['total_ayat']);
        $this->assertTrue($service->contributesToProgress($payload));
    }

    public function test_individual_review_payload_is_history_only(): void
    {
        $service = new TilawahProgressService();
        $payload = $service->buildPayload(
            $this->point(10),
            $this->point(15),
            null,
            Tilawah::ENTRY_TYPE_INDIVIDUAL,
            null,
            Tilawah::PURPOSE_REVIEW
        );
        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->assertFalse($service->contributesToProgress($payload));
        $this->assertStringContainsString(
            'Mandiri Murojaah',
            $service->display($encoded)
        );
    }

    public function test_legacy_payload_defaults_to_group_continuation(): void
    {
        $service = new TilawahProgressService();
        $payload = $service->parse(json_encode([
            'schema' => 'tilawah.v1',
            'mode' => 'lanjut',
            'from' => $this->point(1),
            'to' => $this->point(2),
        ], JSON_THROW_ON_ERROR));

        $this->assertSame('group', $payload['mode']);
        $this->assertSame('continuation', $payload['reading_purpose']);
        $this->assertTrue($service->contributesToProgress($payload));
    }

    public function test_group_payload_cannot_be_marked_as_review(): void
    {
        $this->expectException(ValidationException::class);

        (new TilawahProgressService())->buildPayload(
            $this->point(1),
            $this->point(2),
            null,
            Tilawah::ENTRY_TYPE_GROUP,
            null,
            Tilawah::PURPOSE_REVIEW
        );
    }

    public function test_juz_payload_exposes_unique_juz_progress(): void
    {
        $service = new TilawahProgressService();
        $encoded = json_encode([
            'schema' => 'tilawah.individual.juz.v1',
            'mode' => 'individual',
            'reading_purpose' => Tilawah::PURPOSE_CONTINUATION,
            'juz' => 7,
            'from' => $this->point(1),
            'to' => $this->point(7),
            'total_ayat' => 7,
            'note' => 'Selesai penuh',
        ], JSON_THROW_ON_ERROR);
        $payload = $service->parse($encoded);

        $this->assertIsArray($payload);
        $this->assertTrue($service->isJuzPayload($payload));
        $this->assertSame(7, $service->juzNumber($encoded));
        $this->assertTrue($service->contributesToProgress($payload));
        $this->assertSame('Juz 7', $service->rangeLabel($encoded));
        $this->assertSame(
            'Mandiri Lanjut · Juz 7 | Selesai penuh',
            $service->display($encoded)
        );
    }

    public function test_juz_review_payload_remains_history_only(): void
    {
        $service = new TilawahProgressService();
        $encoded = json_encode([
            'schema' => 'tilawah.individual.juz.v1',
            'mode' => 'individual',
            'reading_purpose' => Tilawah::PURPOSE_REVIEW,
            'juz' => 12,
            'from' => $this->point(1),
            'to' => $this->point(7),
        ], JSON_THROW_ON_ERROR);
        $payload = $service->parse($encoded);

        $this->assertIsArray($payload);
        $this->assertFalse($service->contributesToProgress($payload));
        $this->assertStringContainsString(
            'Mandiri Murojaah · Juz 12',
            $service->display($encoded)
        );
    }

    /**
     * @return array{surah_id:int,surah:string,ayat:int,quran_index:int}
     */
    private function point(int $index): array
    {
        return [
            'surah_id' => 1,
            'surah' => 'Al-Fatihah',
            'ayat' => $index,
            'quran_index' => $index,
        ];
    }
}
