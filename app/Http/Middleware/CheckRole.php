<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    protected $rolesHierarchy = [
        'user' => 0,
        'manager' => 1,
        'admin' => 2,
    ];

    protected $numericToNamed = [
        0 => 'user',
        2 => 'manager',
        1 => 'admin',
    ];

    public function handle(Request $request, Closure $next, $role = null)
    {
        // Проверяем авторизацию через web (для админ-панели) или через бот
        $user = $request->user() ?? $request->botUser ?? null;

        if (is_null($user)) {
            if ($request->expectsJson()) {
                abort(403, 'Нет доступа');
            }
            return redirect()->route('login');
        }

        if (!is_null($user->blocked_at ?? null)) {
            abort(403, 'Нет доступа');
        }

        // Получаем роль как строку (manager, admin, user)
        $userRoleValue = $user->role;
        $userRole = $this->numericToNamed[$userRoleValue] ?? 'user';

        // Если роль не указана, пропускаем (доступ для всех авторизованных)
        if (is_null($role)) {
            return $next($request);
        }

        // Если роль пользователя выше или равна требуемой
        $userScore = $this->rolesHierarchy[$userRole] ?? 0;
        $requiredScore = $this->rolesHierarchy[$role] ?? 0;

        if ($userScore >= $requiredScore) {
            return $next($request);
        }

        abort(403, 'Недостаточно прав');
    }
}
