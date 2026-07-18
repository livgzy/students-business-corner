<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'nim',
    'name',
    'prodi',
    'semester',
    'email',
    'password',
    'isTenant',
    'phone',
])]
#[Hidden(['password', 'remember_token'])]
class UserTenant extends Model
{
        /** @use HasFactory<UserFactory> */
        use HasFactory, Notifiable;

        /**
         * Get the attributes that should be cast.
         *
         * @return array<string, string>
         */
        protected function casts(): array
        {
            return [
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
            ];
        }
    
        public function reservation()
        {
            return $this->hasOne(Reservation::class);
        }

}
