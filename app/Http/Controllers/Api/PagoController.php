<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Services\PrestamoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PagoController extends Controller
{
    public function __construct(private PrestamoService $service) {}

    public function index(Request $request)
    {
        $q = Pago::with('prestamo.cliente:id,nombre', 'usuario:id,name')->latest('id');

        if (! $request->user()->esAdmin()) {
            $q->where('user_id', $request->user()->id);
        }
        if ($request->filled('prestamo_id')) {
            $q->where('prestamo_id', $request->query('prestamo_id'));
        }
        if ($request->filled('desde')) {
            $q->whereDate('fecha', '>=', $request->query('desde'));
        }
        if ($request->filled('hasta')) {
            $q->whereDate('fecha', '<=', $request->query('hasta'));
        }

        return $q->paginate(50);
    }

    public function store(Request $request, Prestamo $prestamo)
    {
        abort_unless($request->user()->esAdmin() || $prestamo->cobrador_id === $request->user()->id, 403);

        $d = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'sometimes|date',
            'metodo' => ['sometimes', Rule::in(['efectivo', 'transferencia'])],
            'nota' => 'nullable|string|max:500',
        ]);

        $pago = $this->service->registrarPago($prestamo, $d, $request->user());

        return response()->json($pago->load('prestamo:id,saldo,estado'), 201);
    }
}
