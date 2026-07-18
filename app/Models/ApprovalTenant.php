<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalTenant extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_code',
        'reservation_id',
        'store_name',
        'slug',
        'description',
        'phone',
        'tenant_img',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }
    
    public function menus()
    {
        return $this->hasMany(ApprovalMenu::class, 'tenant_id');
    }
}
