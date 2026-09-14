<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Screening extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'location',
        'screening_date',
        'start_time',
        'contact_name',
        'contact_phone',
    ];

    protected $casts = [
        'screening_date' => 'date:Y-m-d',
    ];

    protected $appends = ['is_upcoming'];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * 场次开场的完整日期时间
     */
    public function getStartsAtAttribute(): Carbon
    {
        $date = $this->screening_date ? $this->screening_date->format('Y-m-d') : now()->toDateString();
        $time = $this->start_time ?: '00:00:00';

        return Carbon::parse("{$date} {$time}");
    }

    /**
     * 是否为近期（未过期）场次：过期场次自动归入历史
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->starts_at->gte(now());
    }

    /**
     * 近期（未过期）场次
     */
    public function scopeUpcoming($query)
    {
        $today = now()->toDateString();
        $now = now()->format('H:i:s');

        return $query->where(function ($q) use ($today, $now) {
            $q->where('screening_date', '>', $today)
              ->orWhere(function ($q2) use ($today, $now) {
                  $q2->where('screening_date', $today)
                     ->where('start_time', '>=', $now);
              });
        });
    }

    /**
     * 历史（已过期）场次
     */
    public function scopePast($query)
    {
        $today = now()->toDateString();
        $now = now()->format('H:i:s');

        return $query->where(function ($q) use ($today, $now) {
            $q->where('screening_date', '<', $today)
              ->orWhere(function ($q2) use ($today, $now) {
                  $q2->where('screening_date', $today)
                     ->where('start_time', '<', $now);
              });
        });
    }
}
