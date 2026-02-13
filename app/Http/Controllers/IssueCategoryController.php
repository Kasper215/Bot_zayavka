<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IssueCategory;

class IssueCategoryController extends Controller
{
    /**
     * Список всех категорий
     */
    public function index()
    {
        return response()->json(IssueCategory::all());
    }

    /**
     * Просмотр одной категории
     */
    public function show($id)
    {
        $category = IssueCategory::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Создание новой категории
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'nullable|integer',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:255',
            'variants'    => 'nullable|array',
        ]);

        $category = IssueCategory::create([
            'name'        => $validated['name'],
            'type'        => $validated['type'] ?? 0,
            'description' => $validated['description'] ?? null,
            'icon'        => $validated['icon'] ?? null,
            'variants'    => $validated['variants'] ?? null,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Обновление категории
     */
    public function update(Request $request, $id)
    {
        $category = IssueCategory::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'nullable|integer',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:255',
            'variants'    => 'nullable|array',
        ]);

        $category->update([
            'name'        => $validated['name'],
            'type'        => $validated['type'] ?? $category->type,
            'description' => $validated['description'] ?? $category->description,
            'icon'        => $validated['icon'] ?? $category->icon,
            'variants'    => $validated['variants'] ?? $category->variants,
        ]);

        return response()->json($category);
    }

    /**
     * Удаление категории
     */
    public function destroy($id)
    {
        $category = IssueCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Категория удалена']);
    }
}
