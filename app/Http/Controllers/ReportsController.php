<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;

class ReportsController extends Controller
{
    public function index()
    {
        return response()->json(Report::all());
    }

    public function show($id)
    {
        return response()->json(Report::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'            => 'nullable|integer',
            'from_user_id'    => 'nullable|integer',
            'to_user_id'      => 'required|integer',
            'municipality_id' => 'required|integer',
            'received_at'     => 'required|string',
            'documents'       => 'nullable|array',
        ]);

        $report = Report::create($validated);
        return response()->json($report, 201);
    }

    public function update(Request $request, $id)
    {
        $report = Report::findOrFail($id);

        $validated = $request->validate([
            'type'            => 'nullable|integer',
            'from_user_id'    => 'nullable|integer',
            'to_user_id'      => 'required|integer',
            'municipality_id' => 'required|integer',
            'received_at'     => 'required|string',
            'documents'       => 'nullable|array',
        ]);

        $report->update($validated);
        return response()->json($report);
    }

    public function destroy($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Отчёт удалён']);
    }
}
