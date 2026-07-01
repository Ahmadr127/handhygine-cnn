<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Zone extends Model
{
    protected $fillable = [
        'group_id',
        'camera_id',
        'nama_zona',
        'tipe_zona',
        'polygon_points',
    ];

    protected $casts = [
        'polygon_points' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(MonitoringGroup::class, 'group_id');
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function getTipeColorAttribute(): string
    {
        return match($this->tipe_zona) {
            'sanitizer' => '#00e676',
            'wastafel'  => '#ffeb3b',
            default     => '#9e9e9e',
        };
    }
}
