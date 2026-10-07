<?php

// Tests the real calculation service without booting Laravel or accessing a database.
require_once __DIR__ . '/../app/Services/TilawahProgressService.php';
require_once __DIR__ . '/../app/Services/TilawahReportService.php';

$service = new App\Services\TilawahReportService(new App\Services\TilawahProgressService());
$assertions = 0;
$check = function ($expected, $actual, string $message) use (&$assertions): void {
    $assertions++;
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': ' . json_encode(['expected' => $expected, 'actual' => $actual]));
    }
};
$record = function (int $juz, string $type = 'individual', string $purpose = 'continuation', string $status = 'hadir', int $from = 1, int $to = 7): object {
    return (object) [
        'entry_type' => $type, 'reading_purpose' => $purpose, 'status' => $status,
        'catatan' => json_encode([
            'schema' => $type === 'individual' ? 'tilawah.individual.juz.v1' : 'tilawah.v2',
            'mode' => $type, 'reading_purpose' => $purpose, 'juz' => $juz,
            'from' => ['quran_index' => $from], 'to' => ['quran_index' => $to],
        ], JSON_THROW_ON_ERROR),
    ];
};
$r = $service->summarize([$record(30)]);
$check(1, $r['completed_count'], 'Juz 30 is only one completed juz');
$check(3.3, $r['percentage'], 'High bookmark is not 100%');
$r = $service->summarize([$record(30), $record(30), $record(2, purpose: 'review'), $record(3, status: 'alpha'), $record(4, type: 'group'), $record(5, type: 'catchup')]);
$check([30], $r['completed'], 'Duplicates, review, absent, group and catchup do not earn juz');
$check(['continuation' => 3, 'review' => 1, 'group' => 1, 'catchup' => 1, 'legacy' => 0], $r['activity_counts'], 'All statuses remain activity');
$r = $service->summarize([$record(1, type: 'group', from: 1, to: 10), $record(1, type: 'group', from: 5, to: 15), $record(1, type: 'group', from: 2, to: 3), $record(1, type: 'catchup', from: 1, to: 10)]);
$check(15, $r['unique_ayat']['group'], 'Overlapping group ranges are a union');
$check(10, $r['unique_ayat']['catchup'], 'Catchup coverage is separate');
$check(0, $r['completed_count'], 'Ayat groups are not complete-juz achievements');
$legacy = $record(30); $legacy->catatan = 'Catatan lama'; $legacy->entry_type = null;
$malformed = $record(30); $malformed->catatan = '{broken';
$perAyat = $record(30); $payload = json_decode($perAyat->catatan, true); $payload['schema'] = 'tilawah.v2'; $perAyat->catatan = json_encode($payload);
$r = $service->summarize([$legacy, $malformed, $perAyat]);
$check(0, $r['completed_count'], 'Legacy, invalid and individual per-ayat notes do not imply full juz');
$check(1, $r['activity_counts']['legacy'], 'Legacy retained');
$check(7, $r['unique_ayat']['individual'], 'Individual per-ayat coverage remains visible');
$r = $service->summarize([$record(0), $record(31)]);
$check(0, $r['completed_count'], 'Out-of-range juz rejected');
$r = $service->summarize(array_map(fn (int $juz) => $record($juz), range(1, 30)));
$check(30, $r['completed_count'], 'All thirty distinct juz');
$check(100.0, $r['percentage'], 'Complete progress is 100%');
$r = $service->summarize([]);
$check(0.0, $r['percentage'], 'Empty scope starts at zero');
$check(0, $r['max_juz'], 'Empty scope has no maximum');
echo "PASS: {$assertions} calculation assertions\n";
