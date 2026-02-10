<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_musyrif_id_to_santris_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->foreignId('musyrif_id')
                ->nullable()
                ->constrained('musyrifs')
                ->nullOnDelete(); // kalau musyrif dihapus, santri tetap ada tapi musyrif_id null
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropConstrainedForeignId('musyrif_id');
        });
    }
};

