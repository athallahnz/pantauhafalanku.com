<?php
namespace Tests\Feature;

use App\Services\QuranExecutiveMetricsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExecutivePopulationTest extends TestCase
{
    public function test_population_excludes_missing_profiles_and_keeps_inactive_students(): void
    {
        $original = config('database.default');
        config(['database.default' => 'executive_population_test', 'database.connections.executive_population_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]]);
        DB::purge('executive_population_test');
        try {
            Schema::create('santris', function (Blueprint $table) {
                $table->id(); $table->string('status');
            });
            Schema::create('santri_semester_placements', function (Blueprint $table) {
                $table->id(); $table->unsignedInteger('santri_id'); $table->unsignedInteger('semester_id');
            });
            $table = (new \App\Models\Santri)->getTable();
            $this->assertSame('santris', $table);
            DB::table($table)->insert([['id' => 1, 'status' => 'aktif'], ['id' => 2, 'status' => 'lulus']]);
            DB::table('santri_semester_placements')->insert([
                ['santri_id' => 1, 'semester_id' => 3], ['santri_id' => 2, 'semester_id' => 3],
                ['santri_id' => 779, 'semester_id' => 3], ['santri_id' => 1, 'semester_id' => 2],
            ]);
            $method = new \ReflectionMethod(QuranExecutiveMetricsService::class, 'validPlacementsQuery');
            $query = $method->invoke(app(QuranExecutiveMetricsService::class));
            $this->assertSame([1, 2], $query->where('sp.semester_id', 3)->orderBy('sp.santri_id')->pluck('sp.santri_id')->map(fn ($id) => (int) $id)->all());
            $this->assertSame(4, DB::table('santri_semester_placements')->count());
        } finally {
            DB::purge('executive_population_test'); config(['database.default' => $original]);
        }
    }
}
