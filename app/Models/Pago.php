<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = ['prestamo_id', 'user_id', 'monto', 'fecha', 'metodo', 'nota'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fecha' => 'date:Y-m-d'];
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
