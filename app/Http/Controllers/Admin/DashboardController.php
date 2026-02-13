<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Статистика за последние 30 дней
        $last30Days = Carbon::now()->subDays(30);

        // 1. Количество новых заявок по дням (для графика)
        $leadsByDay = Lead::where('created_at', '>=', $last30Days)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 2. Распределение по жанрам (услугам)
        $leadsByService = Lead::selectRaw('service_type, COUNT(*) as count')
            ->groupBy('service_type')
            ->get();

        // 3. Конверсия
        $totalUsers = User::where('role', 0)->count();
        $usersWithLeads = Lead::distinct('user_id')->count('user_id');
        
        $conversionRate = $totalUsers > 0 ? round(($usersWithLeads / $totalUsers) * 100, 2) : 0;

        // 4. Общая статистика
        $stats = [
            'total_leads' => Lead::count(),
            'new_leads' => Lead::where('status', 'new')->count(),
            'total_users' => $totalUsers,
            'conversion_rate' => $conversionRate,
        ];

        return Inertia::render('Admin/Dashboard', [
            'leadsByDay' => $leadsByDay,
            'leadsByService' => $leadsByService,
            'stats' => $stats
        ]);
    }
}
