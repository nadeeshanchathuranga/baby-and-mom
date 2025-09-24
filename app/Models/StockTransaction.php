<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'transaction_type',
        'quantity',
        'transaction_date',
        'supplier_id',
        'reason',
        'sale_id',
    ];

     // Relationships
    // Make sure your table has a product_id column
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id'); // adjust FK if different
    }

    // (Optional) If stock_transactions also has supplier_id directly:
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
