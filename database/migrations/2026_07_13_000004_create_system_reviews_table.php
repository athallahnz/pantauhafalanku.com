<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_reviews', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('musyrif_id')
                ->nullable()
                ->constrained('musyrifs')
                ->nullOnDelete();

            $table->string('display_name', 150);
            $table->string('role_label', 80)->default('Musyrif');

            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('review');

            $table->boolean('is_anonymous')->default(false);

            $table->string('status', 20)
                ->default('pending')
                ->index();

            $table->unsignedInteger('sort_order')
                ->default(0)
                ->index();

            $table->timestamp('published_at')->nullable();

            $table->foreignId('moderated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('moderated_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'sort_order', 'published_at']);
            $table->index(['rating', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_reviews');
    }
};
