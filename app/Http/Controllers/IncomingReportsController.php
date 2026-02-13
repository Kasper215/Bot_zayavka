<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IncomingReport;

class IncomingReportsController extends Controller
{
    public function index()
    {
        return response()->json(IncomingReport::all());
    }

    public function show($id)
    {
        return response()->json(IncomingReport::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_id'          => 'required|integer',
            'received_from'      => 'nullable|string',
            'problem_description'=> 'required|string',
            'help_formats'       => 'nullable|array',
            'comment'            => 'nullable|string',
        ]);

        $incoming = IncomingReport::create($validated);
        return response()->json($incoming, 201);
    }

    public function update(Request $request, $id)
    {
        $incoming = IncomingReport::findOrFail($id);

        $validated = $request->validate([
            'report_id'          => 'required|integer',
            'received_from'      => 'nullable|string',
            'problem_description'=> 'required|string',
            'help_formats'       => 'nullable|array',
            'comment'            => 'nullable|string',
        ]);

        $incoming->update($validated);
        return response()->json($incoming);
    }

    public function destroy($id)
    {
        $incoming = IncomingReport::findOrFail($id);
        $incoming->delete();

        return response()->json(['message' => 'Входящий отчёт удалён']);
    }
}
