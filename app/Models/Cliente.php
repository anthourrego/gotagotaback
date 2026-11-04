<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre', 'documento', 'telefono', 'direccion', 'barrio',
        'referencia_nombre', 'referencia_telefono', 'ruta_id', 'notas', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }
}
