<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Lead;

class UserAccountController extends Controller
{
    /**
     * Display the user's personal dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Fetch leads belonging to this user
        $leads = Lead::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Personal/Dashboard', [
            'leads' => $leads,
            'user' => $user
        ]);
    }

    /**
     * Get latest status of a specific lead for real-time updates.
     */
    public function getLeadStatus(Lead $lead)
    {
        if ($lead->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'status' => $lead->status,
            'manager_notes' => $lead->manager_notes
        ]);
    }
}
