<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * No seeders.
 *
 * The application has no demo data to seed: members and attendance records come
 * from the church's database, which is restored from a mysqldump (see
 * docs/DATABASE.md §6), not generated.
 *
 * The laravel/laravel skeleton shipped a seeder that created a record in the
 * default `users` table via UserFactory. Both were built against a schema this
 * application does not use — authentication reads `tbl_user` through
 * App\Models\User, whose fillable attributes are username / password / email /
 * member_code, not name / email_verified_at. Running `db:seed` would have
 * failed. Both files have been removed.
 *
 * If you need fixtures, create members explicitly:
 *
 *     App\Models\Member::create([
 *         'state' => App\Models\Member::STATE_ACTIVE,
 *         'code'  => '1001',
 *         'name'  => '測試會友',
 *         'account_type' => App\Models\Member::ACCOUNT_TYPE_MEMBER,
 *     ]);
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
