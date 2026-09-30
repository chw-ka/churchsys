<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorshipAttendance extends Model
{
    protected $table = 'tbl_worship_attendance';

    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'worship_id', 'member_id', 'attendance_date',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function worship()
    {
        return $this->belongsTo(Worship::class, 'worship_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('attendance_date', $date);
    }

    public function scopeForWorship($query, int $worshipId)
    {
        return $query->where('worship_id', $worshipId);
    }

    /**
     * Get attendance grouped by week for reporting
     */
    public static function getWeeklySummary(int $year, ?int $worshipId = null)
    {
        $query = static::selectRaw('
            YEAR(attendance_date) as year,
            WEEKOFYEAR(attendance_date) as week,
            MIN(DATE(attendance_date)) as week_start
        ');

        if ($worshipId) {
            $query->where('worship_id', $worshipId);
        }

        $query->whereYear('attendance_date', $year)
            ->groupByRaw('YEAR(attendance_date), WEEKOFYEAR(attendance_date)')
            ->orderBy('week_start', 'desc');

        return $query->get();
    }
}
