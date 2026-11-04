<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruta extends Model
{
    protected $table = 'rutas';

    protected $fillable = ['nombre', 'descripcion', 'cobrador_id', 'activa'];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cobrador_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
