<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    protected $fillable = [
        'url',
        'slug',
        'performance_score',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    /**
     * Boot method to auto-generate slug before creating.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Report $report) {
            $report->slug = Str::random(10);
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}