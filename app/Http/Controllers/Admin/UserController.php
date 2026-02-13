<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Только админы могут управлять персоналом
        if ($request->user()->role != 1) {
            abort(403);
        }

        $users = User::whereIn('role', [1, 2])
            ->orderBy('role', 'asc')
            ->get(['id', 'name', 'username', 'role', 'created_at']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        if ($request->user()->role != 1) {
            abort(403);
        }

        $validated = $request->validate([
            'role' => 'required|integer|in:0,1,2'
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'Роль пользователя обновлена');
    }
}
