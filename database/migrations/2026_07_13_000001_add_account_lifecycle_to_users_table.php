<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasAccountStatus = Schema::hasColumn('users', 'account_status');
        $hasApprovedAt = Schema::hasColumn('users', 'approved_at');
        $hasApprovedBy = Schema::hasColumn('users', 'approved_by');
        $hasSuspendedAt = Schema::hasColumn('users', 'suspended_at');
        $hasSuspendedBy = Schema::hasColumn('users', 'suspended_by');
        $hasSuspensionReason = Schema::hasColumn('users', 'suspension_reason');
        $hasRejectedAt = Schema::hasColumn('users', 'rejected_at');
        $hasRejectedBy = Schema::hasColumn('users', 'rejected_by');
        $hasRejectionReason = Schema::hasColumn('users', 'rejection_reason');
        $hasArchivedAt = Schema::hasColumn('users', 'archived_at');
        $hasArchivedBy = Schema::hasColumn('users', 'archived_by');
        $hasArchiveReason = Schema::hasColumn('users', 'archive_reason');
        $hasDeletedAt = Schema::hasColumn('users', 'deleted_at');

        Schema::table('users', function (Blueprint $table) use (
            $hasAccountStatus,
            $hasApprovedAt,
            $hasApprovedBy,
            $hasSuspendedAt,
            $hasSuspendedBy,
            $hasSuspensionReason,
            $hasRejectedAt,
            $hasRejectedBy,
            $hasRejectionReason,
            $hasArchivedAt,
            $hasArchivedBy,
            $hasArchiveReason,
            $hasDeletedAt
        ): void {
            if (!$hasAccountStatus) {
                $table->string('account_status', 24)
                    ->default('pending')
                    ->index()
                    ->after('is_approved');
            }

            if (!$hasApprovedAt) {
                $table->timestamp('approved_at')->nullable()->after('account_status');
            }

            if (!$hasApprovedBy) {
                $table->unsignedBigInteger('approved_by')->nullable()->index()->after('approved_at');
            }

            if (!$hasSuspendedAt) {
                $table->timestamp('suspended_at')->nullable()->after('approved_by');
            }

            if (!$hasSuspendedBy) {
                $table->unsignedBigInteger('suspended_by')->nullable()->index()->after('suspended_at');
            }

            if (!$hasSuspensionReason) {
                $table->text('suspension_reason')->nullable()->after('suspended_by');
            }

            if (!$hasRejectedAt) {
                $table->timestamp('rejected_at')->nullable()->after('suspension_reason');
            }

            if (!$hasRejectedBy) {
                $table->unsignedBigInteger('rejected_by')->nullable()->index()->after('rejected_at');
            }

            if (!$hasRejectionReason) {
                $table->text('rejection_reason')->nullable()->after('rejected_by');
            }

            if (!$hasArchivedAt) {
                $table->timestamp('archived_at')->nullable()->after('rejection_reason');
            }

            if (!$hasArchivedBy) {
                $table->unsignedBigInteger('archived_by')->nullable()->index()->after('archived_at');
            }

            if (!$hasArchiveReason) {
                $table->text('archive_reason')->nullable()->after('archived_by');
            }

            if (!$hasDeletedAt) {
                $table->softDeletes();
            }
        });

        /*
         * Backfill agar akun lama tetap kompatibel:
         * - is_approved = 1 -> active
         * - is_approved = 0 -> pending
         */
        DB::table('users')
            ->where('is_approved', true)
            ->where(function ($query): void {
                $query->whereNull('account_status')
                    ->orWhere('account_status', 'pending');
            })
            ->update([
                'account_status' => 'active',
                'approved_at' => DB::raw('COALESCE(approved_at, email_verified_at, updated_at, created_at)'),
            ]);

        DB::table('users')
            ->where(function ($query): void {
                $query->where('is_approved', false)
                    ->orWhereNull('is_approved');
            })
            ->whereNull('account_status')
            ->update([
                'account_status' => 'pending',
            ]);
    }

    public function down(): void
    {
        $columns = collect([
            'account_status',
            'approved_at',
            'approved_by',
            'suspended_at',
            'suspended_by',
            'suspension_reason',
            'rejected_at',
            'rejected_by',
            'rejection_reason',
            'archived_at',
            'archived_by',
            'archive_reason',
            'deleted_at',
        ])->filter(
            fn (string $column): bool => Schema::hasColumn('users', $column)
        )->values()->all();

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
