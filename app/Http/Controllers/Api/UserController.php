<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return User::orderBy('name')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'cobrador'])],
            'telefono' => 'nullable|string|max:30',
        ]);

        return response()->json(User::create($data), 201);
    }

    public function show(User $user)
    {
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in(['admin', 'cobrador'])],
            'telefono' => 'nullable|string|max:30',
            'activo' => 'sometimes|boolean',
        ]);
        $user->update($data);

        if (isset($data['activo']) && ! $data['activo']) {
            $user->tokens()->delete();
        }

        return $user;
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'No puedes eliminarte a ti mismo.');
        $user->tokens()->delete();
        $user->update(['activo' => false]);

        return response()->noContent();
    }
}
