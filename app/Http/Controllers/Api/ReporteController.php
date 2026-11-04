<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\MovimientoCaja;
use App\Models\Pago;
use App\Models\Prestamo;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    /** Resumen general de la cartera. */
    public function resumen(Request $request)
    {
        $prestamos = Prestamo::query()->when(
            ! $request->user()->esAdmin(),
            fn ($q) => $q->where('cobrador_id', $request->user()->id)
        );

        $activos = (clone $prestamos)->where('estado', 'activo');

        return [
            'prestamos_activos' => (clone $activos)->count(),
            'capital_prestado_activo' => (float) (clone $activos)->sum('monto'),
            'cartera_por_cobrar' => (float) (clone $activos)->sum('saldo'),
            'total_prestado_historico' => (float) (clone $prestamos)->where('estado', '!=', 'cancelado')->sum('monto'),
            'prestamos_pagados' => (clone $prestamos)->where('estado', 'pagado')->count(),
        ];
    }

    /** Cuotas vencidas sin pagar completas. */
    public function mora(Request $request)
    {
        $cuotas = Cuota::with('prestamo.cliente:id,nombre,telefono,direccion')
            ->where('estado', '!=', 'pagada')
            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
            ->whereHas('prestamo', fn ($p) => $p->where('estado', 'activo')
                ->when(! $request->user()->esAdmin(), fn ($q) => $q->where('cobrador_id', $request->user()->id)))
            ->orderBy('fecha_vencimiento')
            ->get();

        $porPrestamo = $cuotas->groupBy('prestamo_id')->map(function ($grupo) {
            $p = $grupo->first()->prestamo;

            return [
                'prestamo_id' => $p->id,
                'cliente' => $p->cliente,
                'cuotas_vencidas' => $grupo->count(),
                'valor_vencido' => round($grupo->sum(fn ($c) => (float) $c->valor - (float) $c->valor_pagado), 2),
                'dias_mora' => (int) $grupo->first()->fecha_vencimiento->diffInDays(today()),
                'saldo' => $p->saldo,
            ];
        })->values();

        return ['total_vencido' => round($porPrestamo->sum('valor_vencido'), 2), 'prestamos' => $porPrestamo];
    }

    /** Cuotas que vencen hoy (o en la fecha indicada) y las atrasadas: la "lista de cobro". */
    public function cobroDelDia(Request $request)
    {
        $fecha = $request->query('fecha', now()->toDateString());

        $cuotas = Cuota::with('prestamo.cliente:id,nombre,telefono,direccion,barrio,ruta_id')
            ->where('estado', '!=', 'pagada')
            ->whereDate('fecha_vencimiento', '<=', $fecha)
            ->whereHas('prestamo', fn ($p) => $p->where('estado', 'activo')
                ->when(! $request->user()->esAdmin(), fn ($q) => $q->where('cobrador_id', $request->user()->id))
                ->when($request->filled('ruta_id'), fn ($q) => $q->whereHas('cliente',
                    fn ($c) => $c->where('ruta_id', $request->query('ruta_id')))))
            ->orderBy('fecha_vencimiento')
            ->get();

        return $cuotas->groupBy('prestamo_id')->map(function ($grupo) {
            $p = $grupo->first()->prestamo;

            return [
                'prestamo_id' => $p->id,
                'cliente' => $p->cliente,
                'valor_a_cobrar' => round($grupo->sum(fn ($c) => (float) $c->valor - (float) $c->valor_pagado), 2),
                'cuotas_pendientes' => $grupo->count(),
            ];
        })->values();
    }

    /** Ganancia = pagos recibidos - capital desembolsado en el rango (flujo de caja). */
    public function periodo(Request $request)
    {
        $d = $request->validate(['desde' => 'required|date', 'hasta' => 'required|date|after_or_equal:desde']);
        $admin = $request->user()->esAdmin();
        $uid = $request->user()->id;

        $pagos = Pago::whereBetween('fecha', [$d['desde'], $d['hasta']])
            ->when(! $admin, fn ($q) => $q->where('user_id', $uid));
        $mov = MovimientoCaja::whereBetween('fecha', [$d['desde'], $d['hasta']])
            ->when(! $admin, fn ($q) => $q->where('user_id', $uid));

        $cobrado = (float) (clone $pagos)->sum('monto');
        $desembolsado = (float) (clone $mov)->where('concepto', 'desembolso')->sum('monto');
        $gastos = (float) (clone $mov)->where('concepto', 'gasto')->sum('monto');

        return [
            'desde' => $d['desde'],
            'hasta' => $d['hasta'],
            'cobrado' => $cobrado,
            'desembolsado' => $desembolsado,
            'gastos' => $gastos,
            'flujo_neto' => round($cobrado - $desembolsado - $gastos, 2),
        ];
    }
}
