<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'tbl_member';

    public $timestamps = false;

    protected $fillable = [
        'state', 'code', 'name', 'remarks', 'english_name', 'photo',
        'gender', 'birthday', 'email', 'believe', 'believe_date',
        'baptized', 'baptized_date', 'account_type', 'new_card',
        'arrived_date', 'create_date', 'creator_id', 'modifier_id',
        'address_district', 'address_estate', 'address_house', 'address_flat',
        'contact_home', 'contact_mobile', 'contact_office', 'contact_others',
    ];

    const STATE_DELETED = 0;
    const STATE_ACTIVE = 1;
    const STATE_DECEASED = 2;

    const GENDER_FEMALE = 1;
    const GENDER_MALE = 2;
    const GENDER_UNKNOWN = 3;

    const ACCOUNT_TYPE_NEW_MEMBER = 0;
    const ACCOUNT_TYPE_MEMBER = 1;
    const ACCOUNT_TYPE_CO_MEMBER = 2;

    // Relationships
    public function worshipAttendances()
    {
        return $this->hasMany(WorshipAttendance::class, 'member_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'tbl_group_member', 'member_id', 'group_id');
    }

    public function groupAttendances()
    {
        return $this->hasMany(GroupAttendance::class, 'member_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('state', self::STATE_ACTIVE);
    }

    // Accessors
    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            self::GENDER_MALE => '男',
            self::GENDER_FEMALE => '女',
            default => '未知',
        };
    }

    public function getAccountTypeLabelAttribute(): string
    {
        return match ($this->account_type) {
            self::ACCOUNT_TYPE_NEW_MEMBER => '新朋友',
            self::ACCOUNT_TYPE_MEMBER => '會友',
            self::ACCOUNT_TYPE_CO_MEMBER => '同工',
            default => '未知',
        };
    }

    public static function genderList(): array
    {
        return [
            self::GENDER_FEMALE => '女',
            self::GENDER_MALE => '男',
            self::GENDER_UNKNOWN => '未知',
        ];
    }

    public static function accountTypeList(): array
    {
        return [
            self::ACCOUNT_TYPE_NEW_MEMBER => '新朋友',
            self::ACCOUNT_TYPE_MEMBER => '會友',
            self::ACCOUNT_TYPE_CO_MEMBER => '同工',
        ];
    }

    public function getPhotoUrlAttribute(): string
    {
        $path = public_path("storage/file/member/{$this->code}.jpg");
        if (file_exists($path)) {
            return asset("storage/file/member/{$this->code}.jpg");
        }
        return asset('images/anonymous.gif');
    }

    // Worship attendance stats
    public function getLastAttendanceDateAttribute()
    {
        return $this->worshipAttendances()->max('attendance_date');
    }

    public function getAttendanceCount2MonthAttribute(): int
    {
        return $this->worshipAttendances()
            ->where('attendance_date', '>=', now()->subMonths(2))
            ->count();
    }

    public function getAttendanceCount6MonthAttribute(): int
    {
        return $this->worshipAttendances()
            ->where('attendance_date', '>=', now()->subMonths(6))
            ->count();
    }

    public function getAttendanceCountYearAttribute(): int
    {
        return $this->worshipAttendances()
            ->where('attendance_date', '>=', now()->subMonths(12))
            ->count();
    }
}
