<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringGroup extends Model
{
    protected $table = 'monitoring_groups';

    protected $fillable = [
        'nama_grup',
        'deskripsi',
        'lokasi',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class, 'group_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'group_id');
    }

    public function monitoringLogs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class, 'group_id');
    }
}
