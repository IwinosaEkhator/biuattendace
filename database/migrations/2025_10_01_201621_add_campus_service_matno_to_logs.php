<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Add columns if missing
        Schema::table('logs', function (Blueprint $table) {
            if (!Schema::hasColumn('logs', 'campus_id')) {
                $table->foreignId('campus_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('logs', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('campus_id');
            }
            if (!Schema::hasColumn('logs', 'mat_no')) {
                $table->string('mat_no')->nullable()->after('service_id');
            }
        });

        // 2) Add indexes if missing
        $idx = DB::selectOne("
            SELECT COUNT(1) AS c FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name='logs'
              AND index_name='logs_campus_service_matno_idx'
        ");
        if (!$idx || !$idx->c) {
            Schema::table('logs', function (Blueprint $table) {
                $table->index(['campus_id', 'service_id', 'mat_no'], 'logs_campus_service_matno_idx');
            });
        }

        // 3) Add foreign keys only if target tables exist
        Schema::table('logs', function (Blueprint $table) {
            if (Schema::hasTable('campuses') && Schema::hasColumn('logs', 'campus_id')) {
                try {
                    $table->foreign('campus_id')->references('id')->on('campuses')->cascadeOnDelete();
                } catch (\Throwable $e) {
                }
            }
            if (Schema::hasTable('services') && Schema::hasColumn('logs', 'service_id')) {
                try {
                    $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            try {
                $table->dropIndex('logs_campus_service_matno_idx');
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('logs', 'service_id')) {
                try {
                    $table->dropForeign(['service_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('service_id');
            }
            if (Schema::hasColumn('logs', 'campus_id')) {
                try {
                    $table->dropForeign(['campus_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('campus_id');
            }
            if (Schema::hasColumn('logs', 'mat_no')) {
                $table->dropColumn('mat_no');
            }
        });
    }
};
