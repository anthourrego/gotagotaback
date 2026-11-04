<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use Illuminate\Http\Request;

class RutaController extends Controller
{
    public function index(Request $request)
    {
        $q = Ruta::with('cobrador:id,name')->withCount('clientes');
        if (! $request->user()->esAdmin()) {
            $q->where('cobrador_id', $request->user()->id);
        }

        return $q->orderBy('nombre')->paginate(30);
    }

    public function store(Request $request)
    {
        return response()->json(Ruta::create($this->datos($request)), 201);
    }

    public function show(Request $request, Ruta $ruta)
    {
        abort_unless($request->user()->esAdmin() || $ruta->cobrador_id === $request->user()->id, 403);

        return $ruta->load('cobrador:id,name', 'clientes');
    }

    public function update(Request $request, Ruta $ruta)
    {
        $ruta->update($this->datos($request, true));

        return $ruta;
    }

    public function destroy(Ruta $ruta)
    {
        $ruta->delete();

        return response()->noContent();
    }

    private function datos(Request $request, bool $parcial = false): array
    {
        return $request->validate([
            'nombre' => ($parcial ? 'sometimes|' : '').'required|string|max:255',
            'descripcion' => 'nullable|string',
            'cobrador_id' => 'nullable|exists:users,id',
            'activa' => 'sometimes|boolean',
        ]);
    }
}
