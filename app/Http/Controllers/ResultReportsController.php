<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResultReport;

class ResultReportsController extends Controller
{
    public function index()
    {
        return response()->json(ResultReport::all());
    }

    public function show($id)
    {
        return response()->json(ResultReport::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_id'   => 'required|integer',
            'topic'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'actions'     => 'nullable|array',
            'result'      => 'nullable|array',
            'difficulties'=> 'nullable|array',
        ]);

        $result = ResultReport::create($validated);
        return response()->json($result, 201);
    }

    public function update(Request $request, $id)
    {
        $result = ResultReport::findOrFail($id);

        $validated = $request->validate([
            'report_id'   => 'required|integer',
            'topic'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'actions'     => 'nullable|array',
            'result'      => 'nullable|array',
            'difficulties'=> 'nullable|array',
        ]);

        $result->update($validated);
        return response()->json($result);
    }

    public function destroy($id)
    {
        $result = ResultReport::findOrFail($id);
        $result->delete();

        return response()->json(['message' => 'Итоговый отчёт удалён']);
    }
}
