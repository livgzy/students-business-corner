<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'statusApprove',
        'end_date',
        'reasons',
        'is_acknowledged',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
