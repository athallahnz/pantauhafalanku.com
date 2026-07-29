<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_m')->default(150);
            $table->char('color', 7)->default('#6F42C1');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        DB::table('attendance_locations')->insert([
            [
                'name' => 'Kampus Putra (Masjid Darut Taqwa)',
                'address' => 'Kampus Putra Pesantren Darut Taqwa',
                'latitude' => -7.8186683,
                'longitude' => 111.5244092,
                'radius_m' => 150,
                'color' => '#6F42C1',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kampus Putri (Masjid Darut Taqwa)',
                'address' => 'Pondok Putri Pesantren Darut Taqwa',
                'latitude' => -8.0182556,
                'longitude' => 111.4426800,
                'radius_m' => 150,
                'color' => '#D63384',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_locations');
    }
};
