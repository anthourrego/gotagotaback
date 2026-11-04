<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cuota extends Model
{
    protected $table = 'cuotas';

    protected $fillable = ['prestamo_id', 'numero', 'fecha_vencimiento', 'valor', 'valor_pagado', 'estado'];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'valor_pagado' => 'decimal:2',
            'fecha_vencimiento' => 'date:Y-m-d',
        ];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }
}
