<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestamo extends Model
{
    protected $table = 'prestamos';

    protected $fillable = [
        'cliente_id', 'cobrador_id', 'monto', 'tasa_interes', 'total_a_pagar', 'saldo',
        'numero_cuotas', 'valor_cuota', 'frecuencia', 'incluye_domingos',
        'fecha_desembolso', 'fecha_primera_cuota', 'fecha_vencimiento', 'estado', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'tasa_interes' => 'decimal:2',
            'total_a_pagar' => 'decimal:2',
            'saldo' => 'decimal:2',
            'valor_cuota' => 'decimal:2',
            'incluye_domingos' => 'boolean',
            'fecha_desembolso' => 'date:Y-m-d',
            'fecha_primera_cuota' => 'date:Y-m-d',
            'fecha_vencimiento' => 'date:Y-m-d',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function cobrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cobrador_id');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class)->orderBy('numero');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
