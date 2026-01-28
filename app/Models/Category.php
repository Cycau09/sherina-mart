<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
// ========== MULAI: Import SoftDeletes ==========
use Illuminate\Database\Eloquent\SoftDeletes;
// ========== AKHIR: Import SoftDeletes ==========

class Category extends Model
{
    use HasUuids;
    // ========== MULAI: Aktifkan SoftDeletes ==========
    use SoftDeletes;
    // ========== AKHIR: Aktifkan SoftDeletes ==========
    protected $fillable = ["code", "name"];
    protected $casts = ["code" => "integer", "name" => "string"];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
