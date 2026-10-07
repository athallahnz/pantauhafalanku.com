<?php
namespace Tests\Feature;
use App\Services\AcademicDocuments\RaportSnapshotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class RaportExamSnapshotTest extends TestCase
{
    public function test_exam_snapshot_preserves_attempts_unique_books_owner_and_cutoff(): void
    {
        $original = config('database.default');
        config(['database.default' => 'raport_exam_test', 'database.connections.raport_exam_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]); DB::purge('raport_exam_test');
        try {
            Schema::create('tahsin_exams', function (Blueprint $t) {
                $t->id(); $t->integer('santri_id'); $t->integer('semester_id')->nullable();
                $t->date('tanggal'); $t->string('exam_type'); $t->string('buku');
                $t->integer('attempt_number'); $t->string('grade_label'); $t->string('result'); $t->text('catatan')->nullable();
            });
            $base = ['santri_id' => 536, 'semester_id' => 3, 'tanggal' => '2026-08-01', 'exam_type' => 'promotion',
                'buku' => 'ummi_1', 'attempt_number' => 1, 'grade_label' => 'jayyid', 'result' => 'passed', 'catatan' => "Al-An'am <script>"];
            foreach ([[], ['result' => 'repeat', 'attempt_number' => 2], ['attempt_number' => 3],
                ['exam_type' => 'semester', 'buku' => 'ummi_2'], ['semester_id' => 2, 'tanggal' => '2026-05-01', 'buku' => 'ummi_3'],
                ['tanggal' => '2027-01-01', 'buku' => 'tajwid'], ['santri_id' => 999, 'buku' => 'tajwid']] as $override) {
                DB::table('tahsin_exams')->insert(array_replace($base, $override));
            }
            $method = new \ReflectionMethod(RaportSnapshotService::class, 'buildTahsinExams');
            $result = $method->invoke(app(RaportSnapshotService::class), 536, 3, '2026-12-31');
            $this->assertSame(4, $result['semester_activity']['total']);
            $this->assertSame(3, $result['semester_activity']['passed']);
            $this->assertSame(1, $result['semester_activity']['repeat']);
            $this->assertSame(['ummi_1'], $result['semester_activity']['completed_books']);
            $this->assertSame(2, $result['cumulative_achievement']['completed_count']);
            $this->assertCount(4, $result['history']);
            $this->assertSame("Al-An'am <script>", $result['history'][0]['catatan']);
            $empty = $method->invoke(app(RaportSnapshotService::class), 777, 3, '2026-12-31');
            $this->assertSame(0, $empty['semester_activity']['total']);
            $this->assertSame([], $empty['history']);
        } finally { DB::purge('raport_exam_test'); config(['database.default' => $original]); }
    }
}
