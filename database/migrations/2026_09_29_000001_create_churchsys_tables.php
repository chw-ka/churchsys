<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline schema for the four tables the application actually reads and writes.
 *
 * Purpose
 *   - Fresh local installs (`php artisan migrate`) get a working schema without
 *     needing a copy of the production database.
 *   - The test suite builds its in-memory SQLite schema from here.
 *
 * Production safety
 *   The real database was migrated from the legacy Yii system and already contains
 *   these tables, so `up()` is a no-op whenever `tbl_member` is present. This keeps
 *   `php artisan migrate` safe to run on the live server.
 *
 * This is a *minimal* mirror. The authoritative full schema (30 tables, including
 * the legacy group/course/hymn tables) is `deploy/sql/00_schema.sql`.
 * SQLite-compatible: ON UPDATE clauses and zero-date defaults are omitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Never touch an existing (production / migrated) database.
        if (Schema::hasTable('tbl_member') || Schema::hasTable('tbl_worship')) {
            return;
        }

        Schema::create('tbl_user', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username', 128)->unique();
            $table->string('password', 255);
            $table->string('email', 128);
            $table->string('member_code', 4);
            $table->datetime('create_time');
            $table->datetime('update_time');
            $table->string('password_reset_token', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('tbl_member', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('state');
            $table->string('code', 10);
            $table->string('name');
            $table->text('remarks')->nullable();
            $table->string('english_name')->nullable();
            $table->string('photo')->nullable();
            $table->tinyInteger('gender')->default(3);
            $table->date('birthday')->nullable();
            $table->string('email')->nullable();
            $table->string('believe')->nullable();
            $table->string('believe_date')->nullable();
            $table->string('baptized')->nullable();
            $table->string('baptized_date')->nullable();
            $table->integer('account_type')->default(1);
            $table->integer('new_card')->default(0);
            $table->date('arrived_date')->nullable();
            $table->timestamp('create_date')->nullable();
            $table->timestamp('modify_date')->nullable();
            $table->integer('creator_id')->default(0);
            $table->integer('modifier_id')->default(0);
            $table->string('address_district')->nullable();
            $table->string('address_estate')->nullable();
            $table->string('address_house')->nullable();
            $table->string('address_flat')->nullable();
            $table->string('contact_home')->nullable();
            $table->string('contact_mobile')->nullable();
            $table->string('contact_office')->nullable();
            $table->string('contact_others')->nullable();
        });

        Schema::create('tbl_worship', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('state');
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('weekly');
            $table->text('remarks')->nullable();
        });

        Schema::create('tbl_worship_attendance', function (Blueprint $table) {
            $table->integer('worship_id');
            $table->integer('member_id');
            $table->datetime('attendance_date');
            $table->primary(['worship_id', 'member_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_worship_attendance');
        Schema::dropIfExists('tbl_worship');
        Schema::dropIfExists('tbl_member');
        Schema::dropIfExists('tbl_user');
    }
};
