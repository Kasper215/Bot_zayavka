<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            $likeOperator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('contacts', $likeOperator, "%{$search}%")
                  ->orWhere('service_type', $likeOperator, "%{$search}%")
                  ->orWhere('client_name', $likeOperator, "%{$search}%")
                  ->orWhere('volume_stage', $likeOperator, "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search, $likeOperator) {
                      $uq->where('name', $likeOperator, "%{$search}%")
                         ->orWhere('username', $likeOperator, "%{$search}%")
                         ->orWhere('fio_from_telegram', $likeOperator, "%{$search}%");
                  });
            });
        }

        // Фильтрация по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Если это менеджер (роль 2), показываем закрепленные за ним ИЛИ новые (незакрепленные) заявки
        if ($user->role == 2) {
            $query->where(function($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereNull('manager_id');
            });
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

    /**
     * Download a file from a lead.
     */
    public function downloadFile(Lead $lead, $filename)
    {
        $path = "leads/{$lead->id}/{$filename}";
        
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Файл не найден');
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download($path, $filename);
    }

    /**
     * Delete a file from a lead.
     */
    public function deleteFile(Lead $lead, $filename)
    {
        $path = "leads/{$lead->id}/{$filename}";
        
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $files = $lead->files ?: [];
        $files = array_filter($files, function($file) use ($filename) {
            return $file['name'] !== $filename;
        });

        $lead->update(['files' => count($files) > 0 ? array_values($files) : null]);

        return back()->with('success', 'Файл удален');
    }
    /**
     * Check for any new leads (for polling).
     */
    public function checkNew(Request $request)
    {
        $latestLead = Lead::orderBy('id', 'desc')->first();

        return response()->json([
            'latest_id' => $latestLead ? $latestLead->id : 0,
            'last_client' => $latestLead ? ($latestLead->client_name ?: $latestLead->contacts) : 'Unknown'
        ]);
    }
}
