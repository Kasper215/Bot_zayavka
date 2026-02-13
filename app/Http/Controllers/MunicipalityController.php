<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Municipality;

class MunicipalityController extends Controller
{
    /**
     * Список всех муниципалитетов
     */
    public function index()
    {
        return response()->json(Municipality::all());
    }

    /**
     * Просмотр одного муниципалитета
     */
    public function show($id)
    {
        $municipality = Municipality::findOrFail($id);
        return response()->json($municipality);
    }

    /**
     * Создание нового муниципалитета
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'config' => 'nullable|array',
        ]);

        $municipality = Municipality::create([
            'name'   => $validated['name'],
            'config' => $validated['config'] ?? null,
        ]);

        return response()->json($municipality, 201);
    }

    /**
     * Обновление муниципалитета
     */
    public function update(Request $request, $id)
    {
        $municipality = Municipality::findOrFail($id);

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'config' => 'nullable|array',
        ]);

        $municipality->update([
            'name'   => $validated['name'],
            'config' => $validated['config'] ?? null,
        ]);

        return response()->json($municipality);
    }

    /**
     * Удаление муниципалитета
     */
    public function destroy($id)
    {
        $municipality = Municipality::findOrFail($id);
        $municipality->delete();

        return response()->json(['message' => 'Муниципалитет удалён']);
    }
}
