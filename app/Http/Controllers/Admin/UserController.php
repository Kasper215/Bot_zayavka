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
            ->paginate(12);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $users */
        $users->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'is_blocked' => $user->blocked_at !== null,
            ];
        });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    public function toggleRole(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) abort(403);
        
        // Переключаем между Менеджер (1) и Админ (2)
        $newRole = (int)$user->role === 1 ? 2 : 1;
        $user->update(['role' => $newRole]);

        return back()->with('success', 'Роль сотрудника изменена');
    }

    public function block(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) abort(403);
        $user->update(['blocked_at' => now()]);
        return back()->with('success', 'Сотрудник заблокирован');
    }

    public function unblock(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) abort(403);
        $user->update(['blocked_at' => null]);
        return back()->with('success', 'Сотрудник разблокирован');
    }

    public function destroy(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) abort(403);
        $user->delete();
        return back()->with('success', 'Сотрудник удален из системы');
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
