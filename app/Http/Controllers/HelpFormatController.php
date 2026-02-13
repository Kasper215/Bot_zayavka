<?php

namespace App\Http\Controllers;

use App\Models\HelpFormat;
use Illuminate\Http\Request;

class HelpFormatController extends Controller
{
    /**
     * Список всех типов помощи
     */
    public function index()
    {
        return response()->json(HelpFormat::all());
    }

    /**
     * Просмотр одного типа помощи
     */
    public function show($id)
    {
        $helpFormat = HelpFormat::findOrFail($id);
        return response()->json($helpFormat);
    }

    /**
     * Создание нового типа помощи
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'config' => 'nullable|array',
        ]);

        $helpFormat = HelpFormat::create([
            'name'   => $validated['name'],
            'config' => $validated['config'] ?? null,
        ]);

        return response()->json($helpFormat, 201);
    }

    /**
     * Обновление типа помощи
     */
    public function update(Request $request, $id)
    {
        $helpFormat = HelpFormat::findOrFail($id);

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'config' => 'nullable|array',
        ]);

        $helpFormat->update([
            'name'   => $validated['name'],
            'config' => $validated['config'] ?? null,
        ]);

        return response()->json($helpFormat);
    }

    /**
     * Удаление типа помощи
     */
    public function destroy($id)
    {
        $helpFormat = HelpFormat::findOrFail($id);
        $helpFormat->delete();

        return response()->json(['message' => 'Тип помощи удалён']);
    }
}
