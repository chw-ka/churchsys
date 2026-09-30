<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel framework tables: cache, cache_locks, jobs, job_batches, failed_jobs.
 *
 * Every table is created only when absent, which makes this migration safe to run
 * on the production database — those tables were added there manually on
 * 2026-09-29 (see deploy/sql/02_laravel_framework_tables.sql) before this
 * migration existed.
 *
 * This deliberately does NOT create Laravel's default `users`,
 * `password_reset_tokens` or `sessions` tables. Those came from the
 * laravel/laravel skeleton and are unused here:
 *
 *   - Authentication reads `tbl_user` through App\Models\User (see config/auth.php).
 *   - Sessions are file-backed (SESSION_DRIVER=file).
 *   - Cache is file-backed (CACHE_STORE=file) — the `cache` table below is a
 *     fallback for when the store is switched to `database`.
 *
 * Creating them would silently add empty tables to a production schema that the
 * application never touches, which is exactly what happened once before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedSmallInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('connection');
                $table->string('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();

                $table->index(['connection', 'queue', 'failed_at']);
            });
        }
    }

    /**
     * Deliberately empty: these tables are shared framework infrastructure and
     * `migrate:rollback` on a live database must not drop them.
     */
    public function down(): void
    {
        // no-op
    }
};
