<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $q = Cliente::with('ruta:id,nombre')->orderBy('nombre');

        if ($s = $request->query('buscar')) {
            $q->where(fn ($w) => $w->where('nombre', 'like', "%$s%")
                ->orWhere('documento', 'like', "%$s%")
                ->orWhere('telefono', 'like', "%$s%"));
        }
        if ($request->filled('ruta_id')) {
            $q->where('ruta_id', $request->query('ruta_id'));
        }
        if (! $request->user()->esAdmin()) {
            $q->whereIn('ruta_id', $request->user()->rutasIds());
        }

        return $q->paginate(30);
    }

    public function store(Request $request)
    {
        $datos = $this->datos($request);
        abort_unless(
            $request->user()->esAdmin() || in_array($datos['ruta_id'] ?? null, $request->user()->rutasIds(), true),
            403,
            'Debes asignar el cliente a una de tus rutas.'
        );
        $cliente = Cliente::create($datos);

        return response()->json($cliente, 201);
    }

    public function show(Request $request, Cliente $cliente)
    {
        $this->autorizar($request, $cliente);

        return $cliente->load('ruta:id,nombre', 'prestamos');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $this->autorizar($request, $cliente);
        $cliente->update($this->datos($request, $cliente));

        return $cliente;
    }

    public function destroy(Cliente $cliente)
    {
        abort_if($cliente->prestamos()->where('estado', 'activo')->exists(), 422, 'El cliente tiene préstamos activos.');
        $cliente->delete();

        return response()->noContent();
    }

    private function autorizar(Request $request, Cliente $cliente): void
    {
        abort_unless(
            $request->user()->esAdmin() || in_array($cliente->ruta_id, $request->user()->rutasIds(), true),
            403
        );
    }

    private function datos(Request $request, ?Cliente $cliente = null): array
    {
        $req = $cliente ? 'sometimes|required' : 'required';

        return $request->validate([
            'nombre' => "$req|string|max:255",
            'documento' => [$cliente ? 'sometimes' : 'required', 'string', 'max:30',
                Rule::unique('clientes')->ignore($cliente?->id)],
            'telefono' => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:255',
            'barrio' => 'nullable|string|max:255',
            'referencia_nombre' => 'nullable|string|max:255',
            'referencia_telefono' => 'nullable|string|max:30',
            'ruta_id' => 'nullable|exists:rutas,id',
            'notas' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);
    }
}
