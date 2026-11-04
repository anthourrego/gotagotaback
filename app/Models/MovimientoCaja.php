<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    protected $fillable = ['user_id', 'tipo', 'concepto', 'monto', 'fecha', 'descripcion', 'prestamo_id', 'pago_id'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fecha' => 'date:Y-m-d'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
