<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'is_available',
        'is_preorder',
        // 'dayPreorder',
        'product_img',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price' => 'decimal:2',
        // 'dayPreorder' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class,  "tenant_id");
    }

    public function category()
    {
        return $this->belongsTo(Categorie::class,  "category_id");
    }

    public function orderItems()
    {
        return $this->belongsToMany(Order::class);
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
