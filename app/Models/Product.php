<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Variety;
use App\Models\Category;
use App\Models\SaleTransaction;

class Product extends Model
{
    use HasUuids;
    
    // ========== MULAI: Perbaikan $fillable - Tambah field yang kurang ==========
    protected $fillable = ["code", "name", "price", "stock", "category_id", "variety_id"];
    // ========== AKHIR: Perbaikan $fillable ==========
    
    // ========== MULAI: Tambah stock ke $casts ==========
    protected $casts = ["code" => "integer", "name" => "string", "price" => "integer", "stock" => "integer"];
    // ========== AKHIR: Tambah stock ke $casts ==========


    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sale_transactions(): HasMany
    {
        return $this->hasMany(SaleTransaction::class);
    }
}
