<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'translated_title',
        'director',
        'writer',
        'actors',
        'year',
        'release_date',
        'country',
        'language',
        'runtime',
        'genre',
        'rating',
        'imdb_rating',
        'imdb_link',
        'douban_link',
        'poster_url',
        'description',
        'awards',
        'screenshots',
    ];

    protected $casts = [
        'year' => 'integer',
        'rating' => 'decimal:1',
        'screenshots' => 'array',
    ];

    /**
     * 全部放映排期（一个影片可有多场放映）
     */
    public function screenings()
    {
        return $this->hasMany(Screening::class);
    }

    /**
     * 近期（未过期）放映排期，按时间升序
     */
    public function upcomingScreenings()
    {
        return $this->hasMany(Screening::class)
            ->upcoming()
            ->orderBy('screening_date')
            ->orderBy('start_time');
    }
}