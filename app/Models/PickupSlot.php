<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PickupSlot extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'dayPickup',
        'start_time',
        'end_time',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    
    public function getFormattedRangeAttribute(): string
    {
        $start = date('H:i', strtotime($this->start_time));
        $end = date('H:i', strtotime($this->end_time));
        
        return "{$start} - {$end}";
    }
}
