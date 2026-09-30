<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupPeriod extends Model
{
    protected $table = 'tbl_group_period';

    protected $fillable = ['name', 'description'];

    public function groups()
    {
        return $this->hasMany(Group::class, 'period_id');
    }
}
