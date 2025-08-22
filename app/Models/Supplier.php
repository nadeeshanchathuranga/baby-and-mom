<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'address',
        'image',
        'total_cost',
        'pay',
        'balance',

    ];

    public function numbers()
        {
            return $this->hasMany(SupplierNumber::class);
        }


    public function products()
    {
        return $this->hasMany(Product::class);
    }








}
