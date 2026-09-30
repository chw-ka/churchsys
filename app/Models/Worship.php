<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Worship extends Model
{
    protected $table = 'tbl_worship';

    public $timestamps = false;

    protected $fillable = [
        'state', 'name', 'start_time', 'end_time', 'weekly', 'remarks',
    ];

    const WEEKLY_SUN = 0;
    const WEEKLY_MON = 1;
    const WEEKLY_TUE = 2;
    const WEEKLY_WED = 3;
    const WEEKLY_THU = 4;
    const WEEKLY_FRI = 5;
    const WEEKLY_SAT = 6;

    public static function weeklyList(): array
    {
        return [
            self::WEEKLY_SUN => '週日',
            self::WEEKLY_MON => '週一',
            self::WEEKLY_TUE => '週二',
            self::WEEKLY_WED => '週三',
            self::WEEKLY_THU => '週四',
            self::WEEKLY_FRI => '週五',
            self::WEEKLY_SAT => '週六',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(WorshipAttendance::class, 'worship_id');
    }

    public function scopeActive($query)
    {
        return $query->where('state', 1);
    }

    public function getWeeklyLabelAttribute(): string
    {
        return self::weeklyList()[$this->weekly] ?? '未知';
    }

    public function getWeeklyTextAttribute(): string
    {
        return $this->weekly_label;
    }

    public function getLastAttendanceCountAttribute(): int
    {
        return $this->attendances()
            ->whereRaw('DATE(attendance_date) = (SELECT MAX(DATE(attendance_date)) FROM tbl_worship_attendance WHERE worship_id = ?)', [$this->id])
            ->count();
    }
}
