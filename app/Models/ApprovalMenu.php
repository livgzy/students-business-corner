<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ApprovalMenu extends Model
{
    use HasFactory;
    protected $primaryKey = '';
    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'is_preorder',
        'product_img',
    ];
    
    public function tenant()
    {
        return $this->belongsTo(ApprovalTenant::class, 'tenant_id');
    }
    
    public function category()
    {
        return $this->belongsTo(Categorie::class, 'category_id');
    }
}
