<?php

namespace Tests\Feature;

use App\Exports\AcademicMonitoringExport;
use App\Models\Semester;
use App\Models\User;
use App\Services\AcademicMonitoringService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AcademicMonitoringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->app->environment('testing')) {
            throw new \RuntimeException('Jalankan hanya dengan APP_ENV=testing.');
        }
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Aktifkan pdo_sqlite untuk database uji di memori.');
        }
        // No RefreshDatabase and no migrations against the configured application DB.
        config(['database.default' => 'monitoring_test', 'database.connections.monitoring_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('monitoring_test');
        $schema = Schema::connection('monitoring_test');
        $schema->create('tahun_ajarans', function (Blueprint $t) { $t->id(); $t->string('nama'); });
        $schema->create('semesters', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('tahun_ajaran_id'); $t->string('nama'); $t->string('status');
            $t->boolean('is_active'); $t->date('tanggal_mulai'); $t->date('tanggal_selesai');
        });
        $schema->create('kelas', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('parent_id')->nullable(); $t->string('nama_kelas'); $t->string('kelompok')->nullable();
        });
        $schema->create('musyrifs', function (Blueprint $t) { $t->id(); $t->string('nama'); });
        $schema->create('santris', function (Blueprint $t) {
            $t->id(); $t->string('nama'); $t->string('nis')->nullable(); $t->unsignedBigInteger('kelas_id')->nullable();
            $t->unsignedBigInteger('musyrif_id')->nullable(); $t->string('status');
        });
        $schema->create('santri_semester_placements', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('semester_id'); $t->unsignedBigInteger('santri_id');
            $t->unsignedBigInteger('kelas_id')->nullable(); $t->unsignedBigInteger('musyrif_id')->nullable();
        });
        $schema->create('tahsin_exams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('semester_id')->nullable(); $t->unsignedBigInteger('santri_id');
            $t->unsignedBigInteger('musyrif_id')->nullable(); $t->date('tanggal'); $t->string('exam_type');
            $t->string('buku'); $t->string('grade_label'); $t->string('result'); $t->integer('attempt_number'); $t->text('catatan')->nullable();
        });
        $schema->create('tilawahs', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('semester_id')->nullable(); $t->unsignedBigInteger('santri_id');
            $t->unsignedBigInteger('musyrif_id')->nullable(); $t->date('tanggal'); $t->string('entry_type');
            $t->string('reading_purpose'); $t->string('status'); $t->text('catatan')->nullable();
        });
        DB::table('tahun_ajarans')->insert(['id' => 1, 'nama' => '2026/2027']);
        DB::table('semesters')->insert([
            ['id' => 1, 'tahun_ajaran_id' => 1, 'nama' => 'Genap', 'status' => 'closed', 'is_active' => 0, 'tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-06-20'],
            ['id' => 2, 'tahun_ajaran_id' => 1, 'nama' => 'Ganjil', 'status' => 'active', 'is_active' => 1, 'tanggal_mulai' => '2026-07-27', 'tanggal_selesai' => '2026-12-31'],
        ]);
        DB::table('kelas')->insert([
            ['id' => 1, 'parent_id' => null, 'nama_kelas' => 'Kelas 8', 'kelompok' => null],
            ['id' => 2, 'parent_id' => 1, 'nama_kelas' => 'Kelas 8', 'kelompok' => 'A'],
            ['id' => 3, 'parent_id' => null, 'nama_kelas' => 'Kelas 9', 'kelompok' => 'C'],
        ]);
        DB::table('musyrifs')->insert([['id' => 1, 'nama' => 'Pembina Lama'], ['id' => 2, 'nama' => 'Pembina Baru']]);
        DB::table('santris')->insert([
            ['id' => 1, 'nama' => '=1+1', 'nis' => '00123', 'kelas_id' => 3, 'musyrif_id' => 2, 'status' => 'aktif'],
            ['id' => 2, 'nama' => 'Belum Ada Catatan', 'nis' => null, 'kelas_id' => 3, 'musyrif_id' => 2, 'status' => 'aktif'],
            ['id' => 3, 'nama' => 'Alumni', 'nis' => '003', 'kelas_id' => null, 'musyrif_id' => null, 'status' => 'lulus'],
        ]);
        DB::table('santri_semester_placements')->insert([
            ['semester_id' => 1, 'santri_id' => 1, 'kelas_id' => 2, 'musyrif_id' => 1],
            ['semester_id' => 1, 'santri_id' => 3, 'kelas_id' => 2, 'musyrif_id' => 1],
            ['semester_id' => 2, 'santri_id' => 1, 'kelas_id' => 3, 'musyrif_id' => 2],
        ]);
        $this->asRole('admin');
    }

    private function asRole(string $role): void
    {
        $user = new User();
        $user->forceFill(['id' => 999, 'role' => $role, 'name' => 'Test Reader', 'is_approved' => true, 'account_status' => 'active']);
        $this->actingAs($user);
    }

    private function exam(array $overrides = []): void
    {
        DB::table('tahsin_exams')->insert(array_replace([
            'semester_id' => 2, 'santri_id' => 1, 'musyrif_id' => 2, 'tanggal' => '2026-09-10',
            'exam_type' => 'promotion', 'buku' => 'ummi_1', 'grade_label' => 'jayyid', 'result' => 'passed',
            'attempt_number' => 1, 'catatan' => '=HYPERLINK("https://example.com")',
        ], $overrides));
    }

    private function tilawah(int $juz, array $overrides = []): void
    {
        DB::table('tilawahs')->insert(array_replace([
            'semester_id' => 2, 'santri_id' => 1, 'musyrif_id' => 2, 'tanggal' => '2026-09-10',
            'entry_type' => 'individual', 'reading_purpose' => 'continuation', 'status' => 'hadir',
            'catatan' => json_encode(['schema' => 'tilawah.individual.juz.v1', 'mode' => 'individual',
                'juz' => $juz, 'from' => [], 'to' => [], 'note' => 'Catatan uji']),
        ], $overrides));
    }

    public function test_routes_are_read_only_and_forbidden_for_other_roles(): void
    {
        foreach (['admin', 'pimpinan'] as $role) {
            foreach (['exams', 'tilawah'] as $kind) {
                foreach (['index', 'data', 'history', 'export'] as $action) {
                    $route = app('router')->getRoutes()->getByName("{$role}.monitoring.{$kind}.{$action}");
                    $this->assertNotNull($route);
                    $this->assertSame(['GET', 'HEAD'], $route->methods());
                    $this->assertContains('role:' . $role, $route->middleware());
                    $this->asRole('musyrif');
                    $this->getJson(route($route->getName(), ['santri' => 1, 'semester_id' => 2]))->assertForbidden();
                }
            }
        }
        $this->asRole('admin');
        $this->getJson('/pimpinan/rekap-ujian-tahsin/data?semester_id=2')->assertForbidden();
        $this->asRole('pimpinan');
        $this->getJson('/admin/rekap-tilawah-mandiri/data?semester_id=2')->assertForbidden();
        $this->getJson('/pimpinan/rekap-ujian-tahsin/data?semester_id=2')->assertOk();
    }

    public function test_closed_semester_uses_historical_placement_and_includes_alumni(): void
    {
        $this->exam(['semester_id' => 1, 'tanggal' => '2026-06-01', 'musyrif_id' => 1]);
        $this->exam(['buku' => 'ummi_2']);
        $data = $this->getJson('/admin/rekap-ujian-tahsin/data?semester_id=1&kelas_id=1&musyrif_id=1')
            ->assertOk()->json('data');
        $this->assertCount(2, $data);
        $row = collect($data)->firstWhere('id', 1);
        $this->assertSame('Kelas 8 A', $row['kelas']);
        $this->assertSame('Pembina Lama', $row['musyrif']);
        $this->assertSame(1, $row['completed_count']);
        $this->assertSame(0, collect($data)->firstWhere('id', 3)['total']);
    }

    public function test_active_student_without_placement_is_shown_as_not_examined(): void
    {
        $this->exam();
        $response = $this->getJson('/admin/rekap-ujian-tahsin/data?semester_id=2&state=not_examined')->assertOk();
        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 2)
            ->assertJsonPath('data.0.placement_source', 'Data aktif saat ini');
    }

    public function test_missing_historical_placement_does_not_use_current_class(): void
    {
        $this->exam(['santri_id' => 2, 'semester_id' => 1, 'tanggal' => '2026-06-02']);
        $rows = $this->getJson('/admin/rekap-ujian-tahsin/data?semester_id=1')->assertOk()->json('data');
        $row = collect($rows)->firstWhere('id', 2);
        $this->assertSame('-', $row['kelas']);
        $this->assertSame('-', $row['musyrif']);
        $this->assertSame('Penempatan belum tersedia', $row['placement_source']);
    }

    public function test_latest_result_filter_keeps_retakes_and_semester_results_out_of_promotion_progress(): void
    {
        $this->exam();
        $this->exam(['attempt_number' => 2]);
        $this->exam(['exam_type' => 'semester', 'grade_label' => 'mardud', 'result' => 'repeat']);
        $this->getJson('/admin/rekap-ujian-tahsin/data?semester_id=2&state=passed')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/admin/rekap-ujian-tahsin/data?semester_id=2&state=repeat')
            ->assertOk()->assertJsonPath('data.0.completed_count', 1)->assertJsonPath('data.0.total', 3);
        $this->getJson('/admin/rekap-ujian-tahsin/history/1?semester_id=2&state=repeat')
            ->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/admin/rekap-ujian-tahsin/data?semester_id=2&exam_type=promotion&state=passed')
            ->assertOk()->assertJsonPath('data.0.total', 2)->assertJsonPath('data.0.completed_count', 1);
    }

    public function test_tilawah_counts_unique_continuation_only_and_excludes_group_catchup_other_semesters(): void
    {
        $this->tilawah(1); $this->tilawah(1); $this->tilawah(2, ['reading_purpose' => 'review']);
        $this->tilawah(3, ['entry_type' => 'group']); $this->tilawah(4, ['entry_type' => 'catchup']);
        $this->tilawah(5, ['semester_id' => 1, 'tanggal' => '2026-06-02']);
        $this->tilawah(6, ['status' => 'izin']);
        $this->getJson('/admin/rekap-tilawah-mandiri/data?semester_id=2&purpose=review')
            ->assertOk()->assertJsonPath('data.0.completed_count', 1)->assertJsonPath('data.0.total', 1);
        $this->getJson('/admin/rekap-tilawah-mandiri/history/1?semester_id=2')
            ->assertOk()->assertJsonCount(4, 'data');
        $this->getJson('/admin/rekap-tilawah-mandiri/data?semester_id=2&state=no_activity')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', 2);
    }

    public function test_date_range_search_and_history_use_the_same_scope(): void
    {
        $this->exam(['tanggal' => '2026-09-01']);
        $this->exam(['tanggal' => '2026-09-10', 'buku' => 'ummi_2']);
        $query = '?semester_id=2&date_from=2026-09-10&date_to=2026-09-10&q=00123';
        $this->getJson('/admin/rekap-ujian-tahsin/data' . $query)->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.completed_count', 1)->assertJsonPath('data.0.total', 1);
        $this->getJson('/admin/rekap-ujian-tahsin/history/1' . $query)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.materi', 'Ummi 2');
        $this->getJson('/admin/rekap-ujian-tahsin/history/2' . $query)->assertNotFound();
    }

    public function test_invalid_period_and_ids_are_rejected_on_data_history_and_export(): void
    {
        foreach (['data', 'history/1', 'export'] as $endpoint) {
            foreach (['semester_id=999', 'semester_id=2&date_from=2026-07-01',
                'semester_id=2&date_from=2026-09-10&date_to=2026-09-01', 'semester_id=2&kelas_id=999'] as $query) {
                $this->getJson('/admin/rekap-ujian-tahsin/' . $endpoint . '?' . $query)->assertUnprocessable();
            }
        }
    }

    public function test_export_has_matching_rows_and_preserves_nis_and_literal_text(): void
    {
        $this->exam();
        $rows = app(AcademicMonitoringService::class)->report('exams', Semester::findOrFail(2), ['q' => '00123']);
        $export = new AcademicMonitoringExport($rows, 'exams', [['Semester', 'Ganjil']]);
        $sheets = $export->sheets();
        $this->assertCount(3, $sheets);
        $this->assertCount(1, $sheets[1]->array());
        $this->assertCount(1, $sheets[2]->array());
        $binary = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $file = tempnam(sys_get_temp_dir(), 'monitoring-export-');
        try {
            file_put_contents($file, $binary);
            $book = IOFactory::load($file);
            $this->assertSame('00123', $book->getSheetByName('Rekap Santri')->getCell('D2')->getValue());
            $this->assertSame('s', $book->getSheetByName('Rekap Santri')->getCell('C2')->getDataType());
            $this->assertSame('=1+1', $book->getSheetByName('Rekap Santri')->getCell('C2')->getValue());
            $this->assertSame('s', $book->getSheetByName('Riwayat')->getCell('N2')->getDataType());
            $book->disconnectWorksheets();
        } finally {
            unlink($file);
        }
        Excel::fake();
        $this->get('/admin/rekap-ujian-tahsin/export?semester_id=2&q=00123')->assertOk();
        $this->assertSame(1, DB::table('tahsin_exams')->count());
    }
}
