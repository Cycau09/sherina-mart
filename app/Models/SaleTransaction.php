<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class SaleTransaction extends Model
{
    use HasUuids;
    protected $fillable = ["sale_id", "product_id", "price", "quantity"];
    protected $casts = [
        "sale_id" => "string",
        "product_id" => "string",
        "price" => "integer",
        "quantity" => "integer"
    ];

    // ========== MULAI: Update Relasi agar mendukung data Terhapus (Soft Deletes) ==========
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
    // ========== AKHIR: Update Relasi ==========
}
