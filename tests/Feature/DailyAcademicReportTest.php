<?php

namespace Tests\Feature;

use App\Exports\AcademicMonitoringExport;
use App\Models\Semester;
use App\Models\User;
use App\Services\DailyAcademicReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DailyAcademicReportTest extends TestCase
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
            ['id' => 2, 'parent_id' => 1, 'nama_kelas' => 'Kelas 8 A', 'kelompok' => 'A'],
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
        $schema->create('tahsins', function (Blueprint $t) {
            $t->id(); $t->integer('santri_id'); $t->integer('semester_id')->nullable(); $t->integer('musyrif_id')->nullable();
            $t->date('tanggal'); $t->string('buku'); $t->integer('halaman')->nullable(); $t->string('status');
            $t->string('nilai_label')->nullable(); $t->text('catatan')->nullable();
        });
        $schema->create('hafalan_templates', function (Blueprint $t) { $t->id(); $t->string('label'); $t->integer('juz'); $t->string('tahap'); });
        $schema->table('tilawahs', function (Blueprint $t) { $t->integer('hafalan_template_id')->nullable(); });
        $schema->create('institution_settings', function (Blueprint $t) { $t->id(); });
        $this->asRole('admin');
    }

    private function asRole(string $role): void
    {
        $user = new User();
        $user->forceFill(['id' => 999, 'role' => $role, 'name' => 'Test Reader', 'is_approved' => true, 'account_status' => 'active']);
        $user->setRelation('profileSetting', null);
        $this->actingAs($user);
    }

    private function daily(array $overrides = []): void
    {
        DB::table('tahsins')->insert(array_replace(['santri_id' => 1, 'semester_id' => 2, 'musyrif_id' => 2,
            'tanggal' => '2026-09-10', 'buku' => 'ummi_1', 'halaman' => 5, 'status' => 'hadir',
            'nilai_label' => 'jayyid', 'catatan' => "Al-An'am =1+1"], $overrides));
    }

    private function reading(array $overrides = []): void
    {
        DB::table('tilawahs')->insert(array_replace(['santri_id' => 1, 'semester_id' => 2, 'musyrif_id' => 2,
            'tanggal' => '2026-09-10', 'entry_type' => 'individual', 'reading_purpose' => 'continuation', 'status' => 'hadir',
            'catatan' => json_encode(['schema' => 'tilawah.individual.juz.v1', 'mode' => 'individual', 'reading_purpose' => 'continuation', 'juz' => 30,
                'from' => ['surah' => 'An-Naba', 'ayat' => 1, 'quran_index' => 5673],
                'to' => ['surah' => 'An-Nas', 'ayat' => 6, 'quran_index' => 6236]])], $overrides));
    }

    private function rows(string $kind = 'tahsin', array $filters = [], int $semester = 2)
    {
        return app(DailyAcademicReportService::class)->report($kind, Semester::findOrFail($semester), array_replace([
            'date_from' => $semester === 2 ? '2026-07-27' : '2026-01-01',
            'date_to' => $semester === 2 ? '2026-12-31' : '2026-06-20',
        ], $filters));
    }

    public function test_lightweight_summary_and_single_student_history_keep_the_same_scope(): void
    {
        $this->daily(); $this->daily(['status' => 'alpha']); $this->reading();
        $service = app(DailyAcademicReportService::class);
        $semester = Semester::findOrFail(2);
        $filters = ['date_from' => '2026-07-27', 'date_to' => '2026-12-31'];
        foreach (['tahsin', 'tilawah'] as $kind) {
            $full = $service->report($kind, $semester, $filters);
            $summary = $service->report($kind, $semester, $filters, false);
            $this->assertSame($full->map(fn ($r) => collect($r)->except('history')->all())->all(), $summary->all());
            $this->assertArrayNotHasKey('history', $summary->firstWhere('id', 1));
            $single = $service->report($kind, $semester, $filters, true, 1);
            $this->assertCount(1, $single);
            $this->assertSame($full->firstWhere('id', 1), $single->first());
        }
        $this->assertCount(0, $service->report('tahsin', $semester, $filters, true, 999999));
    }

    public function test_tahsin_progress_counts_only_present_pages_and_keeps_absences(): void
    {
        $this->daily(); $this->daily(['status' => 'alpha', 'halaman' => 40]);
        $this->daily(['semester_id' => 1, 'tanggal' => '2026-05-01', 'halaman' => 40]);
        $row = $this->rows()->firstWhere('id', 1);
        $this->assertSame(2, $row['total']); $this->assertSame(1, $row['hadir']); $this->assertSame(1, $row['alpha']);
        $this->assertSame(5, $row['books'][0]['halaman']); $this->assertSame(2, $row['percentage']);
        $this->assertCount(2, $row['history']);
        $this->assertSame('-', $row['history'][0]['nilai']);
        $this->assertSame(0, $this->rows('tahsin', ['status' => 'alpha'])->firstWhere('id', 1)['percentage']);
        $this->assertSame(0, $this->rows('tahsin', ['buku' => 'tajwid'])->firstWhere('id', 1)['total']);
    }

    public function test_all_tilawah_types_are_separate_and_juz_are_unique(): void
    {
        $this->reading(); $this->reading(); $this->reading(['reading_purpose' => 'review']);
        $this->reading(['entry_type' => 'group']); $this->reading(['entry_type' => 'catchup']);
        $this->reading(['entry_type' => 'legacy', 'catatan' => 'Catatan lama']);
        $this->reading(['status' => 'izin']);
        $row = $this->rows('tilawah')->firstWhere('id', 1);
        $this->assertSame(7, $row['total']); $this->assertSame(6, $row['hadir']);
        $this->assertSame(1, $row['tilawah']['completed_count']); $this->assertSame(3.3, $row['percentage']);
        $this->assertSame(['continuation' => 3, 'review' => 1, 'group' => 1, 'catchup' => 1, 'legacy' => 1], $row['tilawah']['activity_counts']);
        $this->assertCount(7, $row['history']);
        $group = $this->rows('tilawah', ['entry_type' => 'group'])->firstWhere('id', 1);
        $this->assertSame(1, $group['total']); $this->assertSame(0.0, $group['percentage']);
        $this->assertSame(1, $this->rows('tilawah', ['entry_type' => 'legacy'])->firstWhere('id', 1)['total']);
    }

    public function test_group_coverage_merges_overlaps_and_excludes_non_present_readings(): void
    {
        foreach ([[1, 7, 'hadir'], [4, 10, 'hadir'], [11, 20, 'izin']] as [$from, $to, $status]) {
            $this->reading(['entry_type' => 'group', 'status' => $status, 'catatan' => json_encode([
                'schema' => 'tilawah.v2', 'mode' => 'group', 'reading_purpose' => 'continuation',
                'from' => ['quran_index' => $from, 'surah' => 'Al-Fatihah', 'ayat' => $from],
                'to' => ['quran_index' => $to, 'surah' => 'Al-Fatihah', 'ayat' => $to], 'total_ayat' => $to - $from + 1,
            ])]);
        }
        $row = $this->rows('tilawah')->firstWhere('id', 1);
        $this->assertSame(10, $row['tilawah']['unique_ayat']['group']);
        $this->assertSame(3, $row['tilawah']['activity_counts']['group']);
        $this->assertSame(0, $row['tilawah']['completed_count']);
    }

    public function test_population_honors_historical_placement_and_descendant_classes(): void
    {
        $this->daily(['semester_id' => 1, 'tanggal' => '2026-05-01']);
        $this->daily(['semester_id' => 1, 'tanggal' => '2026-05-01', 'santri_id' => 3]);
        $rows = $this->rows('tahsin', ['kelas_id' => 1, 'musyrif_id' => 1], 1);
        $this->assertSame([1, 3], $rows->pluck('id')->sort()->values()->all());
        $this->assertSame('Kelas 8 A', $rows->firstWhere('id', 1)['kelas']);
        $this->assertSame('Pembina Lama', $rows->firstWhere('id', 1)['musyrif']);
        $this->assertSame(0, $this->rows('tahsin', ['kelas_id' => 3], 1)->count());
        $this->assertSame('Data aktif saat ini', $this->rows()->firstWhere('id', 2)['placement_source']);
        $this->assertSame(0, $this->rows()->firstWhere('id', 2)['total']);
    }

    public function test_date_filters_search_history_and_excel_agree(): void
    {
        $this->daily(); $this->daily(['tanggal' => '2026-09-11']);
        $query = '?semester_id=2&date_from=2026-09-10&date_to=2026-09-10&q=00123';
        $this->getJson('/admin/rekap-tahsin-harian/data'.$query)->assertOk()->assertJsonPath('statistics.total_records', 1)
            ->assertJsonPath('statistics.total_santri', 1)->assertJsonPath('data.0.total', 1);
        $this->getJson('/admin/rekap-tahsin-harian/history/1'.$query)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/admin/rekap-tahsin-harian/history/2'.$query)->assertNotFound();
        $response = $this->get('/admin/rekap-tahsin-harian/export'.$query); $response->assertOk()->assertDownload();
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $this->assertSame(['Parameter', 'Rekap Santri', 'Riwayat'], $book->getSheetNames());
        $this->assertSame(2, $book->getSheetByName('Riwayat')->getHighestRow());
        $this->assertSame('00123', $book->getSheetByName('Rekap Santri')->getCell('C2')->getValue());
        $this->assertSame('s', $book->getSheetByName('Rekap Santri')->getCell('B2')->getDataType());
        $this->assertSame('=1+1', $book->getSheetByName('Rekap Santri')->getCell('B2')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_record_only_historical_student_never_uses_current_placement(): void
    {
        $this->daily(['semester_id' => 1, 'tanggal' => '2026-05-01', 'santri_id' => 2]);
        $row = $this->rows('tahsin', [], 1)->firstWhere('id', 2);
        $this->assertSame('Penempatan belum tersedia', $row['placement_source']);
        $this->assertSame('-', $row['kelas']); $this->assertSame('-', $row['musyrif']);
    }

    public function test_invalid_filters_are_rejected_on_all_endpoints(): void
    {
        foreach (['/data', '/history/1', '/export'] as $path) {
            foreach (['semester_id=999', 'semester_id=2&date_from=2026-01-01', 'semester_id=2&date_from=2026-09-11&date_to=2026-09-10', 'semester_id=2&kelas_id=999', 'semester_id=2&status=bad'] as $query) {
                $this->getJson('/admin/rekap-tahsin-harian'.$path.'?'.$query)->assertUnprocessable();
            }
        }
    }

    public function test_pimpinan_daily_reports_match_admin_and_keep_role_boundaries(): void
    {
        $this->daily(); $this->reading();
        foreach (['rekap-tahsin-harian', 'rekap-seluruh-tilawah'] as $path) {
            $query = '?semester_id=2&date_from=2026-07-27&date_to=2026-12-31';
            $this->asRole('admin');
            $admin = $this->getJson('/admin/'.$path.'/data'.$query)->assertOk()->json();
            $this->asRole('pimpinan');
            $this->get('/pimpinan/'.$path.$query)->assertOk();
            $this->assertSame($admin, $this->getJson('/pimpinan/'.$path.'/data'.$query)->assertOk()->json());
            $this->getJson('/pimpinan/'.$path.'/history/1'.$query)->assertOk();
            $this->get('/pimpinan/'.$path.'/export'.$query)->assertOk();
            $this->post('/pimpinan/'.$path)->assertStatus(405);
            foreach (['santri', 'musyrif', 'admin'] as $role) {
                $this->asRole($role);
                foreach (['', '/data', '/history/1', '/export'] as $suffix) {
                    $this->getJson('/pimpinan/'.$path.$suffix.$query)->assertForbidden();
                }
            }
        }
    }

    public function test_pimpinan_academic_summary_matches_reports_and_period(): void
    {
        $this->daily(); $this->reading();
        $this->asRole('pimpinan');
        $summary = $this->getJson('/pimpinan/academic-summary?semester_id=2&date_from=2026-07-27&date_to=2026-12-31')->assertOk()->json('data');
        foreach (['tahsin' => 'rekap-tahsin-harian', 'tilawah' => 'rekap-seluruh-tilawah', 'exams' => 'rekap-ujian-tahsin', 'mandiri' => 'rekap-tilawah-mandiri'] as $kind => $path) {
            $stats = $this->getJson('/pimpinan/'.$path.'/data?semester_id=2')->assertOk()->json('statistics');
            $this->assertSame($stats['total_santri'], $summary[$kind]['population']);
            $this->assertSame($stats['with_activity'], $summary[$kind]['active']);
            $this->assertSame($stats['without_activity'], $summary[$kind]['inactive']);
            $this->assertSame($stats['total_records'], $summary[$kind]['records']);
        }
        $short = $this->getJson('/pimpinan/academic-summary?semester_id=2&date_from=2026-07-27&date_to=2026-07-28')->assertOk()->json('data');
        $this->assertSame(0, $short['tahsin']['records']);
        $this->assertSame(0, $short['tilawah']['records']);
    }

    public function test_pimpinan_summary_rejects_other_roles_and_invalid_periods(): void
    {
        foreach (['santri', 'musyrif', 'admin'] as $role) {
            $this->asRole($role);
            $this->getJson('/pimpinan/academic-summary?semester_id=2&date_from=2026-07-27&date_to=2026-12-31')->assertForbidden();
        }
        $this->asRole('pimpinan');
        foreach (['semester_id=999&date_from=2026-07-27&date_to=2026-12-31', 'semester_id=2&date_from=2026-01-01&date_to=2026-12-31', 'semester_id=2&date_from=2026-09-11&date_to=2026-09-10'] as $query) {
            $this->getJson('/pimpinan/academic-summary?'.$query)->assertUnprocessable();
        }
        $this->post('/pimpinan/academic-summary')->assertStatus(405);
    }

    public function test_reports_are_admin_only_and_read_only(): void
    {
        foreach (['santri', 'musyrif', 'pimpinan'] as $role) {
            $this->asRole($role);
            foreach (['rekap-tahsin-harian', 'rekap-seluruh-tilawah'] as $path) {
                $this->get('/admin/'.$path)->assertForbidden();
                $this->getJson('/admin/'.$path.'/data?semester_id=2')->assertForbidden();
                $this->getJson('/admin/'.$path.'/export?semester_id=2')->assertForbidden();
            }
        }
        $this->asRole('admin');
        $this->post('/admin/rekap-tahsin-harian')->assertStatus(405);
        $this->withoutExceptionHandling();
        $this->get('/admin/rekap-tahsin-harian')->assertOk();
        $this->get('/admin/rekap-seluruh-tilawah')->assertOk();
    }
}
