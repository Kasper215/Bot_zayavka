<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Только админы могут управлять персоналом
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $users = User::whereIn('role', [1, 2, 3])
            ->orderBy('role', 'desc')
            ->get(['id', 'name', 'username', 'role', 'created_at']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|integer|in:0,1,2,3'
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'Роль пользователя обновлена');
    }

    public function subscribeNotifications(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['success' => false], 401);

        try {
            $user->updatePushSubscription(
                $request->input('endpoint'),
                $request->input('keys.p256dh'),
                $request->input('keys.auth')
            );
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Push Subscription Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
