<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventRequest;

class EventRequestsController extends Controller
{
    public function index()
    {
        return response()->json(EventRequest::all());
    }

    public function show($id)
    {
        return response()->json(EventRequest::findOrFail($id));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_id'         => 'required|integer',
            'event_date'        => 'required|date',
            'description'       => 'required|string',
            'target_audience'   => 'required|string',
            'participants_count'=> 'required|integer',
            'help_formats'      => 'nullable|array',
            'comment'           => 'nullable|string',
        ]);

        $event = EventRequest::create($validated);
        return response()->json($event, 201);
    }

    public function update(Request $request, $id)
    {
        $event = EventRequest::findOrFail($id);

        $validated = $request->validate([
            'report_id'         => 'required|integer',
            'event_date'        => 'required|date',
            'description'       => 'required|string',
            'target_audience'   => 'required|string',
            'participants_count'=> 'required|integer',
            'help_formats'      => 'nullable|array',
            'comment'           => 'nullable|string',
        ]);

        $event->update($validated);
        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = EventRequest::findOrFail($id);
        $event->delete();

        return response()->json(['message' => 'Заявка на мероприятие удалена']);
    }
}
