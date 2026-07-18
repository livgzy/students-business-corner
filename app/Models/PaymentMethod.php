<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PaymentMethod extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'type',
        'name_payment',
        'account_number',
        'account_name',
        'qr_img',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tenant_id' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

