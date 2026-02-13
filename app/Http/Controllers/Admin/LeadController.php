<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Lead::with(['user', 'manager']);

        // Фильтрация по поиску
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('contacts', 'like', "%{$search}%")
                  ->orWhere('service_type', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('volume_stage', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('username', 'like', "%{$search}%")
                         ->orWhere('fio_from_telegram', 'like', "%{$search}%");
                  });
            });
        }

        // Фильтрация по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Если это менеджер (роль 2), показываем только закрепленные за ним заявки
        if ($user->role == 2) {
            $query->where('manager_id', $user->id);
        }

        $leads = $query
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $leads */
        $leads->withQueryString();

        // Список менеджеров для назначения (админы и менеджеры)
        $managers = [];
        if ($user->role == 1) {
            $managers = \App\Models\User::whereIn('role', [1, 2])->get(['id', 'name']);
        }

        return Inertia::render('Admin/Leads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'status']),
            'userRole' => $user->role,
            'managers' => $managers
        ]);
    }

    /**
     * Update the specified lead (status, notes).
     */
    public function update(Request $request, Lead $lead)
    {
        $user = $request->user();
        
        $rules = [
            'status' => 'required|string|in:new,in_progress,rejected,completed',
            'manager_notes' => 'nullable|string',
        ];

        // Только админы могут назначать менеджеров
        if ($user->role == 1) {
            $rules['manager_id'] = 'nullable|exists:users,id';
        }

        $validated = $request->validate($rules);

        $lead->update($validated);

        return back()->with('success', 'Заявка обновлена');
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return back()->with('success', 'Заявка удалена');
    }

    /**
     * Remove all leads.
     */
    public function destroyAll(Request $request)
    {
        // Только админы могут удалять все заявки
        if ($request->user()->role != 1) {
            abort(403, 'Недостаточно прав для выполнения этого действия');
        }

        Lead::truncate();

        return back()->with('success', 'Все заявки удалены');
    }

    /**
     * Export leads to Excel.
     */
    public function export(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LeadsExport($request->only(['search', 'status'])), 
            'leads_' . now()->format('Y-m-d_H-i') . '.xlsx'
        );
    }
}
