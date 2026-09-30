<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Models\User;
use App\Models\Worship;
use PHPUnit\Framework\TestCase;

/**
 * Pure domain rules — no database, no container.
 *
 * These encode the enums the application relies on. If someone renumbers them,
 * historical records silently change meaning, so the tests pin the values down.
 */
class DomainTest extends TestCase
{
    public function test_member_state_constants(): void
    {
        $this->assertSame(1, Member::STATE_ACTIVE);
        $this->assertSame(0, Member::STATE_DELETED);
        $this->assertSame(2, Member::STATE_DECEASED);
    }

    public function test_member_account_type_constants(): void
    {
        $this->assertSame(0, Member::ACCOUNT_TYPE_NEW_MEMBER);
        $this->assertSame(1, Member::ACCOUNT_TYPE_MEMBER);
        $this->assertSame(2, Member::ACCOUNT_TYPE_CO_MEMBER);

        $this->assertSame(
            [0 => '新朋友', 1 => '會友', 2 => '同工'],
            Member::accountTypeList()
        );
    }

    public function test_member_gender_constants(): void
    {
        $this->assertSame(1, Member::GENDER_FEMALE);
        $this->assertSame(2, Member::GENDER_MALE);
        $this->assertSame(3, Member::GENDER_UNKNOWN);

        $this->assertSame(
            [1 => '女', 2 => '男', 3 => '未知'],
            Member::genderList()
        );
    }

    public function test_gender_label_accessor(): void
    {
        $this->assertSame('女', (new Member(['gender' => Member::GENDER_FEMALE]))->gender_label);
        $this->assertSame('男', (new Member(['gender' => Member::GENDER_MALE]))->gender_label);
        $this->assertSame('未知', (new Member(['gender' => Member::GENDER_UNKNOWN]))->gender_label);
    }

    public function test_account_type_label_accessor(): void
    {
        $this->assertSame('新朋友', (new Member(['account_type' => 0]))->account_type_label);
        $this->assertSame('會友', (new Member(['account_type' => 1]))->account_type_label);
        $this->assertSame('同工', (new Member(['account_type' => 2]))->account_type_label);
    }

    /**
     * `weekly` on tbl_worship is the day of the week, not a "repeats weekly" flag.
     * 0 is Sunday.
     */
    public function test_worship_weekly_is_a_day_of_week_starting_at_sunday(): void
    {
        $this->assertSame(0, Worship::WEEKLY_SUN);
        $this->assertSame(6, Worship::WEEKLY_SAT);

        $list = Worship::weeklyList();
        $this->assertCount(7, $list);
        $this->assertSame('週日', $list[0]);
        $this->assertSame('週六', $list[6]);
    }

    public function test_worship_weekly_label_accessor(): void
    {
        $this->assertSame('週日', (new Worship(['weekly' => 0]))->weekly_label);
        $this->assertSame('週六', (new Worship(['weekly' => 6]))->weekly_label);
        $this->assertSame('未知', (new Worship(['weekly' => 99]))->weekly_label);
    }

    public function test_a_32_character_password_is_detected_as_legacy_md5(): void
    {
        $this->assertTrue((new User(['password' => md5('secret')]))->isMd5Password());
        $this->assertTrue((new User(['password' => str_repeat('a', 32)]))->isMd5Password());
    }

    public function test_a_bcrypt_password_is_not_treated_as_md5(): void
    {
        $this->assertFalse((new User(['password' => bcrypt('secret')]))->isMd5Password());
    }

    public function test_bcrypt_password_is_verified(): void
    {
        $user = new User(['password' => bcrypt('secret123')]);

        $this->assertTrue($user->verifyPassword('secret123'));
        $this->assertFalse($user->verifyPassword('wrong'));
    }

    // The MD5 -> bcrypt upgrade path persists the new hash, so it is covered by
    // AuthenticationTest (Feature) where a database is available.
}
