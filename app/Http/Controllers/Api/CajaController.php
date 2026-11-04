<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        $fecha = $request->query('fecha', now()->toDateString());
        $q = MovimientoCaja::whereDate('fecha', $fecha);

        if (! $request->user()->esAdmin()) {
            $q->where('user_id', $request->user()->id);
        } elseif ($request->filled('user_id')) {
            $q->where('user_id', $request->query('user_id'));
        }

        $movs = $q->orderBy('id')->get();
        $ingresos = (float) $movs->where('tipo', 'ingreso')->sum('monto');
        $egresos = (float) $movs->where('tipo', 'egreso')->sum('monto');

        return [
            'fecha' => $fecha,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'saldo' => round($ingresos - $egresos, 2),
            'movimientos' => $movs,
        ];
    }

    /** Registro manual: base inicial, gastos u otros. Pagos y desembolsos son automáticos. */
    public function store(Request $request)
    {
        $d = $request->validate([
            'tipo' => ['required', Rule::in(['ingreso', 'egreso'])],
            'concepto' => ['required', Rule::in(['base', 'gasto', 'otro'])],
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'sometimes|date',
            'descripcion' => 'nullable|string|max:255',
        ]);
        $d['fecha'] ??= now()->toDateString();
        $d['user_id'] = $request->user()->id;

        return response()->json(MovimientoCaja::create($d), 201);
    }
}
