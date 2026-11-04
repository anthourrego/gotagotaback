<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Services\PrestamoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrestamoController extends Controller
{
    public function __construct(private PrestamoService $service) {}

    public function index(Request $request)
    {
        $q = Prestamo::with('cliente:id,nombre,documento,telefono')->latest('id');

        if (! $request->user()->esAdmin()) {
            $q->where('cobrador_id', $request->user()->id);
        } elseif ($request->filled('cobrador_id')) {
            $q->where('cobrador_id', $request->query('cobrador_id'));
        }
        if ($request->filled('estado')) {
            $q->where('estado', $request->query('estado'));
        }
        if ($request->filled('cliente_id')) {
            $q->where('cliente_id', $request->query('cliente_id'));
        }
        if ($request->boolean('en_mora')) {
            $q->whereHas('cuotas', fn ($c) => $c->where('estado', '!=', 'pagada')
                ->whereDate('fecha_vencimiento', '<', now()->toDateString()));
        }

        return $q->paginate(30);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'cobrador_id' => 'sometimes|exists:users,id',
            'monto' => 'required|numeric|min:1',
            'tasa_interes' => 'required|numeric|min:0|max:1000',
            'numero_cuotas' => 'required|integer|min:1|max:366',
            'frecuencia' => ['required', Rule::in(['diario', 'semanal', 'quincenal', 'mensual'])],
            'incluye_domingos' => 'sometimes|boolean',
            'fecha_desembolso' => 'required|date',
            'fecha_primera_cuota' => 'required|date|after_or_equal:fecha_desembolso',
            'permitir_multiple' => 'sometimes|boolean',
            'notas' => 'nullable|string',
        ]);

        // Un cobrador solo puede prestar a su propio nombre.
        if (! $request->user()->esAdmin()) {
            unset($d['cobrador_id']);
        }

        return response()->json($this->service->crear($d, $request->user()), 201);
    }

    public function show(Request $request, Prestamo $prestamo)
    {
        $this->autorizar($request, $prestamo);
        $prestamo->load('cliente', 'cuotas', 'pagos');

        $mora = $prestamo->cuotas
            ->where('estado', '!=', 'pagada')
            ->filter(fn ($c) => $c->fecha_vencimiento->lt(today()));

        return array_merge($prestamo->toArray(), [
            'cuotas_en_mora' => $mora->count(),
            'valor_en_mora' => round($mora->sum(fn ($c) => (float) $c->valor - (float) $c->valor_pagado), 2),
        ]);
    }

    public function cancelar(Request $request, Prestamo $prestamo)
    {
        $this->autorizar($request, $prestamo);
        abort_if($prestamo->estado !== 'activo', 422, 'Solo se cancelan préstamos activos.');
        abort_if($prestamo->pagos()->exists(), 422, 'El préstamo ya tiene pagos registrados.');
        $prestamo->update(['estado' => 'cancelado']);

        return $prestamo;
    }

    private function autorizar(Request $request, Prestamo $prestamo): void
    {
        abort_unless($request->user()->esAdmin() || $prestamo->cobrador_id === $request->user()->id, 403);
    }
}
