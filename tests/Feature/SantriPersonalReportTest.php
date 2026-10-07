<?php

namespace Tests\Feature;

use App\Exports\SantriPersonalReportExport;
use App\Models\Santri;
use App\Models\User;
use App\Services\SantriPersonalReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SantriPersonalReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->app->environment('testing')) {
            throw new \RuntimeException('APP_ENV=testing wajib.');
        }
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite wajib untuk database uji dalam memori.');
        }
        config(['database.default' => 'personal_report_test', 'database.connections.personal_report_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('personal_report_test');
        Schema::create('tahun_ajarans', function (Blueprint $t) { $t->id(); $t->string('nama'); });
        Schema::create('semesters', function (Blueprint $t) {
            $t->id(); $t->integer('tahun_ajaran_id'); $t->string('nama'); $t->string('status')->default('closed');
            $t->boolean('is_active')->default(false); $t->date('tanggal_mulai')->nullable(); $t->date('tanggal_selesai')->nullable();
        });
        Schema::create('musyrifs', function (Blueprint $t) { $t->id(); $t->string('nama'); });
        Schema::create('hafalan_templates', function (Blueprint $t) { $t->id(); $t->integer('juz'); $t->string('label'); $t->string('tahap'); });
        foreach (['hafalans', 'tahsins', 'tilawahs', 'tahsin_exams'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id(); $t->integer('santri_id'); $t->integer('semester_id')->nullable(); $t->integer('musyrif_id')->nullable();
                $t->text('catatan')->nullable(); $t->timestamps();
                if ($table === 'tahsin_exams') {
                    $t->date('tanggal'); $t->string('exam_type'); $t->string('buku'); $t->integer('attempt_number');
                    $t->string('grade_label'); $t->string('result'); $t->string('next_book')->nullable();
                } else {
                    $t->date($table === 'hafalans' ? 'tanggal_setoran' : 'tanggal'); $t->string('status');
                    $t->string('nilai_label')->nullable();
                    if ($table === 'tahsins') { $t->string('buku'); $t->integer('halaman')->nullable(); }
                    else { $t->integer('hafalan_template_id')->nullable(); }
                    if ($table === 'tilawahs') { $t->string('entry_type')->nullable(); $t->string('reading_purpose')->nullable(); }
                }
            });
        }
        DB::table('tahun_ajarans')->insert(['id' => 1, 'nama' => '2026/2027']);
        DB::table('semesters')->insert([['id' => 1, 'tahun_ajaran_id' => 1, 'nama' => 'Genap'], ['id' => 2, 'tahun_ajaran_id' => 1, 'nama' => 'Ganjil']]);
        DB::table('musyrifs')->insert(['id' => 1, 'nama' => 'Pembina']);
        DB::table('hafalan_templates')->insert(['id' => 1, 'juz' => 1, 'label' => "Al-An'am", 'tahap' => 'harian']);
        foreach ([1, 2, null] as $semester) {
            foreach (['hafalans', 'tahsins', 'tilawahs', 'tahsin_exams'] as $table) {
                foreach ([1, 2] as $owner) {
                    $row = ['santri_id' => $owner, 'semester_id' => $semester, 'musyrif_id' => 1,
                        'catatan' => $owner === 2 ? 'PRIVATE OTHER OWNER' : "Al-An'am <script>alert(1)</script>"];
                    if ($table === 'tahsin_exams') {
                        $row += ['tanggal' => '2026-05-01', 'exam_type' => 'promotion', 'buku' => 'ummi_1',
                            'attempt_number' => 1, 'grade_label' => 'jayyid', 'result' => 'passed', 'next_book' => 'ummi_2'];
                    } else {
                        $row += [$table === 'hafalans' ? 'tanggal_setoran' : 'tanggal' => '2026-05-01',
                            'status' => $table === 'hafalans' ? 'lulus' : 'hadir', 'nilai_label' => 'jayyid'];
                        if ($table === 'tahsins') { $row += ['buku' => 'ummi_1', 'halaman' => 5]; }
                        else { $row += ['hafalan_template_id' => 1]; }
                        if ($table === 'tilawahs') { $row += ['entry_type' => 'group', 'reading_purpose' => null]; }
                    }
                    DB::table($table)->insert($row);
                }
            }
        }
        $user = new User();
        $user->forceFill(['id' => 100, 'role' => 'santri', 'name' => 'Santri Test', 'is_approved' => true, 'account_status' => 'active']);
        $santri = new Santri(); $santri->forceFill(['id' => 1, 'nama' => 'Santri Test', 'nis' => '000123']);
        $user->setRelation('santri', $santri);
        $this->actingAs($user);
    }

    public function test_all_histories_are_scoped_and_owned_including_legacy(): void
    {
        $service = app(SantriPersonalReportService::class);
        foreach ($service->histories(1, 1) as $rows) {
            $this->assertCount(1, $rows); $this->assertStringNotContainsString('PRIVATE OTHER OWNER', json_encode($rows, JSON_UNESCAPED_SLASHES));
        }
        foreach ($service->histories(1, null) as $rows) {
            $this->assertCount(3, $rows); $this->assertStringContainsString('Data lama / tanpa semester', json_encode($rows, JSON_UNESCAPED_SLASHES));
        }
        $this->assertSame(1, $service->examSummary(1, 1)['passed']);
        $this->assertSame(3, $service->examSummary(1, null)['total']);
        $this->assertSame(0, $service->examSummary(99, null)['total']);
    }

    public function test_exam_timeline_ignores_owner_override_and_escapes_once(): void
    {
        $response = $this->getJson('/santri/ujian-tahsin/timeline?scope=semester&semester_id=1&santri_id=2&draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertOk()->assertJsonPath('recordsTotal', 1);
        $note = $response->json('data.0.catatan');
        $this->assertStringContainsString('Al-An&#039;am', $note);
        $this->assertStringNotContainsString('&amp;#039;', $note);
        $this->assertStringContainsString('&lt;script&gt;', $note);
        $this->assertStringNotContainsString('PRIVATE OTHER OWNER', $response->getContent());
        $this->getJson('/santri/ujian-tahsin/timeline?scope=semester&semester_id=999', ['X-Requested-With' => 'XMLHttpRequest'])->assertUnprocessable();
    }

    public function test_repeat_attempts_remain_separate_from_daily_tahsin_progress(): void
    {
        DB::table('tahsin_exams')->insert(['santri_id' => 1, 'semester_id' => 1, 'musyrif_id' => 1,
            'tanggal' => '2026-05-02', 'exam_type' => 'semester', 'buku' => 'ummi_1',
            'attempt_number' => 2, 'grade_label' => 'mardud', 'result' => 'repeat', 'catatan' => 'Mengulang']);
        $service = app(SantriPersonalReportService::class);
        $this->assertSame(['total' => 2, 'passed' => 1, 'repeat' => 1, 'promotion' => 1, 'semester' => 1], $service->examSummary(1, 1));
        $this->assertCount(2, $service->histories(1, 1)['Ujian Tahsin']);
        $this->assertCount(1, $service->histories(1, 1)['Tahsin']);
        $this->assertSame(5, DB::table('tahsins')->where('santri_id', 1)->where('semester_id', 1)->value('halaman'));
    }

    public function test_excel_has_four_histories_and_preserves_text_and_nis(): void
    {
        $histories = app(SantriPersonalReportService::class)->histories(1, 1);
        $export = new SantriPersonalReportExport([['NIS', '000123'], ['Catatan', '=1+1']], $histories);
        $path = tempnam(sys_get_temp_dir(), 'personal-report-');
        try {
            file_put_contents($path, Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));
            $book = IOFactory::load($path);
            $this->assertSame(['Ringkasan', 'Hafalan', 'Tahsin', 'Tilawah', 'Ujian Tahsin'], $book->getSheetNames());
            $this->assertSame('000123', $book->getSheet(0)->getCell('B2')->getValue());
            $this->assertSame('s', $book->getSheet(0)->getCell('B3')->getDataType());
            $this->assertSame('=1+1', $book->getSheet(0)->getCell('B3')->getValue());
            $this->assertSame(2, $book->getSheetByName('Ujian Tahsin')->getHighestRow());
            $book->disconnectWorksheets();
        } finally { @unlink($path); }
    }

    public function test_personal_exports_require_scope_and_ignore_owner_override(): void
    {
        $this->getJson('/santri/laporan-pribadi/pdf')->assertUnprocessable();
        $this->getJson('/santri/laporan-pribadi/excel?scope=semester&semester_id=999')->assertUnprocessable();
        $excel = $this->get('/santri/laporan-pribadi/excel?scope=semester&semester_id=1&santri_id=2');
        $excel->assertOk()->assertDownload();
        $book = IOFactory::load($excel->baseResponse->getFile()->getPathname());
        $this->assertSame('Santri Test', $book->getSheet(0)->getCell('B2')->getValue());
        foreach (['Hafalan', 'Tahsin', 'Tilawah', 'Ujian Tahsin'] as $name) {
            $this->assertSame(2, $book->getSheetByName($name)->getHighestRow());
            $this->assertStringNotContainsString('PRIVATE OTHER OWNER', json_encode($book->getSheetByName($name)->toArray()));
        }
        $book->disconnectWorksheets();
        $response = $this->get('/santri/laporan-pribadi/pdf?scope=semester&semester_id=1&santri_id=2');
        $response->assertOk()->assertDownload();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_html_escapes_notes_and_keeps_all_sections(): void
    {
        $histories = app(SantriPersonalReportService::class)->histories(1, 1);
        $html = view('santri.hafalan.personal-report-pdf', ['santri' => (object) ['nama' => 'Test', 'nis' => '000123'],
            'scopeLabel' => 'Genap', 'parameters' => [['NIS', '000123']], 'histories' => $histories])->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('PRIVATE OTHER OWNER', $html);
        foreach (array_keys($histories) as $name) { $this->assertStringContainsString($name.' — 1 record', $html); }
    }

    public function test_guest_and_missing_profile_cannot_read_reports(): void
    {
        $this->app['auth']->forgetGuards();
        $this->get('/santri/laporan-pribadi/pdf?scope=cumulative')->assertRedirect();
        $user = new User();
        $user->forceFill(['id' => 200, 'role' => 'santri', 'is_approved' => true, 'account_status' => 'active']);
        $user->setRelation('santri', null); $this->actingAs($user);
        $this->getJson('/santri/laporan-pribadi/pdf?scope=cumulative')->assertForbidden();
    }
}
