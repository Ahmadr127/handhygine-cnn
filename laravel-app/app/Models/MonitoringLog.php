<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringLog extends Model
{
    protected $table = 'monitoring_logs';

    protected $fillable = [
        'person_id',
        'group_id',
        'camera_id',
        'waktu',
        'status',
        'membawa_instrumen',
        'aktivitas_cuci_tangan',
        'snapshot_path',
        'confidence',
    ];

    protected $casts = [
        'waktu'                  => 'datetime',
        'membawa_instrumen'      => 'boolean',
        'aktivitas_cuci_tangan'  => 'boolean',
        'confidence'             => 'decimal:2',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(MonitoringGroup::class, 'group_id');
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status === 'patuh'
            ? '<span class="badge badge-patuh">✅ Patuh</span>'
            : '<span class="badge badge-tidak-patuh">❌ Tidak Patuh</span>';
    }

    public function getSnapshotUrlAttribute(): ?string
    {
        if (!$this->snapshot_path) return null;
        return asset('storage/' . $this->snapshot_path);
    }
}
