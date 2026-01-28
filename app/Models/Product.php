<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
// ========== MULAI: Import SoftDeletes ==========
use Illuminate\Database\Eloquent\SoftDeletes;
// ========== AKHIR: Import SoftDeletes ==========
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Variety;
use App\Models\Category;
use App\Models\SaleTransaction;

class Product extends Model
{
    use HasUuids;
    // ========== MULAI: Aktifkan SoftDeletes ==========
    use SoftDeletes;
    // ========== AKHIR: Aktifkan SoftDeletes ==========
    
    // ========== MULAI: Perbaikan $fillable - Tambah field yang kurang ==========
    protected $fillable = ["code", "name", "price", "stock", "category_id", "variety_id"];
    // ========== AKHIR: Perbaikan $fillable ==========
    
    // ========== MULAI: Update $casts (Code -> String) ==========
    protected $casts = ["code" => "string", "name" => "string", "price" => "integer", "stock" => "integer"];
    // ========== AKHIR: Update $casts (Code -> String) ==========


    // ========== MULAI: Update Relasi agar tetap membaca data yang di-soft delete ==========
    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }
    // ========== AKHIR: Update Relasi ==========

    public function sale_transactions(): HasMany
    {
        return $this->hasMany(SaleTransaction::class);
    }
}
