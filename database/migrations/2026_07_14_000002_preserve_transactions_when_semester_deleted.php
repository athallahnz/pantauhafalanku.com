<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $transactionTables = [
        'hafalans',
        'tahsins',
        'tilawahs',
    ];

    public function up(): void
    {
        $this->ensureMysqlDriver();

        foreach ($this->transactionTables as $tableName) {
            $this->replaceSemesterForeignKey($tableName, cascade: false);
        }
    }

    public function down(): void
    {
        $this->ensureMysqlDriver();

        foreach ($this->transactionTables as $tableName) {
            $this->replaceSemesterForeignKey($tableName, cascade: true);
        }
    }

    private function replaceSemesterForeignKey(
        string $tableName,
        bool $cascade
    ): void {
        if (
            ! Schema::hasTable($tableName)
            || ! Schema::hasColumn($tableName, 'semester_id')
        ) {
            return;
        }

        $constraint = DB::selectOne(
            <<<'SQL'
                SELECT CONSTRAINT_NAME AS constraint_name
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = 'semester_id'
                  AND REFERENCED_TABLE_NAME = 'semesters'
                LIMIT 1
            SQL,
            [$tableName]
        );

        if ($constraint?->constraint_name) {
            Schema::table(
                $tableName,
                function (Blueprint $table) use ($constraint): void {
                    $table->dropForeign($constraint->constraint_name);
                }
            );
        }

        Schema::table($tableName, function (Blueprint $table) use ($cascade): void {
            $foreign = $table->foreign('semester_id')
                ->references('id')
                ->on('semesters');

            if ($cascade) {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->nullOnDelete();
            }
        });
    }

    private function ensureMysqlDriver(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException(
                'Migration foreign key semester hanya mendukung MySQL/MariaDB.'
            );
        }
    }
};
