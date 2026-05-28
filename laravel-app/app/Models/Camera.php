<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Camera extends Model
{
    protected $fillable = [
        'group_id',
        'nama_kamera',
        'tipe',
        'source',
        'aktif',
        'zona_config',
    ];

    protected $casts = [
        'aktif'       => 'boolean',
        'zona_config' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(MonitoringGroup::class, 'group_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function monitoringLogs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class);
    }

    public function getTipeIconAttribute(): string
    {
        return match($this->tipe) {
            'usb'  => '🎥',
            'rtsp' => '📡',
            'file' => '📁',
            default => '📷',
        };
    }
}
