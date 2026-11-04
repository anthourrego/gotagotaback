<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\MovimientoCaja;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrestamoService
{
    /**
     * Crea el préstamo, genera el plan de cuotas y registra el desembolso en caja.
     * Interés simple: total = monto * (1 + tasa/100).
     */
    public function crear(array $d, User $actor): Prestamo
    {
        return DB::transaction(function () use ($d, $actor) {
            $cliente = Cliente::findOrFail($d['cliente_id']);

            $tieneActivo = Prestamo::where('cliente_id', $cliente->id)->where('estado', 'activo')->exists();
            if ($tieneActivo && empty($d['permitir_multiple'])) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'El cliente ya tiene un préstamo activo.',
                ]);
            }

            $monto = round((float) $d['monto'], 2);
            $total = round($monto * (1 + $d['tasa_interes'] / 100), 2);
            $n = (int) $d['numero_cuotas'];
            $incluyeDomingos = (bool) ($d['incluye_domingos'] ?? false);

            $centavos = (int) round($total * 100);
            $base = intdiv($centavos, $n);
            $resto = $centavos - $base * $n;

            $fecha = Carbon::parse($d['fecha_primera_cuota']);
            $cuotas = [];
            for ($i = 1; $i <= $n; $i++) {
                if ($i > 1) {
                    $fecha = $this->siguienteFecha($fecha, $d['frecuencia']);
                }
                if ($d['frecuencia'] === 'diario' && ! $incluyeDomingos) {
                    while ($fecha->isSunday()) {
                        $fecha = $fecha->copy()->addDay();
                    }
                }
                // La última cuota absorbe el residuo de redondeo.
                $cuotas[] = [
                    'numero' => $i,
                    'fecha_vencimiento' => $fecha->toDateString(),
                    'valor' => ($base + ($i === $n ? $resto : 0)) / 100,
                ];
            }

            $prestamo = Prestamo::create([
                'cliente_id' => $cliente->id,
                'cobrador_id' => $d['cobrador_id'] ?? $actor->id,
                'monto' => $monto,
                'tasa_interes' => $d['tasa_interes'],
                'total_a_pagar' => $total,
                'saldo' => $total,
                'numero_cuotas' => $n,
                'valor_cuota' => round($total / $n, 2),
                'frecuencia' => $d['frecuencia'],
                'incluye_domingos' => $incluyeDomingos,
                'fecha_desembolso' => $d['fecha_desembolso'],
                'fecha_primera_cuota' => $d['fecha_primera_cuota'],
                'fecha_vencimiento' => end($cuotas)['fecha_vencimiento'],
                'estado' => 'activo',
                'notas' => $d['notas'] ?? null,
            ]);

            $prestamo->cuotas()->createMany($cuotas);

            MovimientoCaja::create([
                'user_id' => $prestamo->cobrador_id,
                'tipo' => 'egreso',
                'concepto' => 'desembolso',
                'monto' => $monto,
                'fecha' => $d['fecha_desembolso'],
                'descripcion' => "Desembolso préstamo #{$prestamo->id}",
                'prestamo_id' => $prestamo->id,
            ]);

            return $prestamo->load('cliente', 'cuotas');
        });
    }

    /** Registra un abono y lo aplica a las cuotas más antiguas pendientes. */
    public function registrarPago(Prestamo $prestamo, array $d, User $actor): Pago
    {
        return DB::transaction(function () use ($prestamo, $d, $actor) {
            $prestamo = Prestamo::whereKey($prestamo->id)->lockForUpdate()->firstOrFail();

            if ($prestamo->estado !== 'activo') {
                throw ValidationException::withMessages(['prestamo' => 'El préstamo no está activo.']);
            }

            $monto = round((float) $d['monto'], 2);
            if ($monto > (float) $prestamo->saldo) {
                throw ValidationException::withMessages([
                    'monto' => 'El abono supera el saldo pendiente ('.$prestamo->saldo.').',
                ]);
            }

            $pago = Pago::create([
                'prestamo_id' => $prestamo->id,
                'user_id' => $actor->id,
                'monto' => $monto,
                'fecha' => $d['fecha'] ?? now()->toDateString(),
                'metodo' => $d['metodo'] ?? 'efectivo',
                'nota' => $d['nota'] ?? null,
            ]);

            $restante = (int) round($monto * 100);
            $cuotas = $prestamo->cuotas()->where('estado', '!=', 'pagada')->lockForUpdate()->get();
            foreach ($cuotas as $cuota) {
                if ($restante <= 0) {
                    break;
                }
                $pendiente = (int) round(((float) $cuota->valor - (float) $cuota->valor_pagado) * 100);
                $aplicar = min($pendiente, $restante);
                $cuota->valor_pagado = ((int) round((float) $cuota->valor_pagado * 100) + $aplicar) / 100;
                $cuota->estado = $aplicar === $pendiente ? 'pagada' : 'parcial';
                $cuota->save();
                $restante -= $aplicar;
            }

            $prestamo->saldo = max(0, round((float) $prestamo->saldo - $monto, 2));
            if ((float) $prestamo->saldo <= 0) {
                $prestamo->estado = 'pagado';
            }
            $prestamo->save();

            MovimientoCaja::create([
                'user_id' => $actor->id,
                'tipo' => 'ingreso',
                'concepto' => 'pago',
                'monto' => $monto,
                'fecha' => $pago->fecha,
                'descripcion' => "Abono préstamo #{$prestamo->id}",
                'prestamo_id' => $prestamo->id,
                'pago_id' => $pago->id,
            ]);

            return $pago;
        });
    }

    private function siguienteFecha(Carbon $f, string $frecuencia): Carbon
    {
        return match ($frecuencia) {
            'diario' => $f->copy()->addDay(),
            'semanal' => $f->copy()->addWeek(),
            'quincenal' => $f->copy()->addDays(15),
            'mensual' => $f->copy()->addMonthNoOverflow(),
        };
    }
}
