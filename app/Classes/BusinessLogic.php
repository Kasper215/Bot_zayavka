<?php

namespace App\Classes;

use App\Models\Agent;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;


class BusinessLogic
{
    public function method(): static
    {
        return $this;
    }

    public function truncateTitle($title, $maxLength = 30)
    {
        if (mb_strlen($title) > $maxLength) {
            return mb_substr($title, 0, $maxLength) . '…';
        }
        return $title;
    }

    public function getGeneralSalesSummaryByAgentsAndSuppliers($defaultPercent = 10): object
    {

        // Получаем заказы за интересующий период
        $orders = Sale::with('supplier')
            ->where("status", "completed")
            ->whereNotNull('sale_date') // исключаем заказы без даты продажи
            ->orderBy('sale_date')
            ->get();


        // Набор месяцев для аналитики
        /* $months = [
             'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
             'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
         ];*/

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        // Готовим массив для накопления данных
        $results = [];

        foreach ($orders as $order) {
            // Получаем поставщика и месяц продажи
            $supplier = $order->supplier->name ?? 'Неизвестный поставщик';
            $month = Carbon::parse($order->sale_date)->formatLocalized('%B');

            // Индекс месяца (нужно перевести в числовой формат)
            $monthIndex = array_search($month, $months);

            // Создаём запись для каждого поставщика и месяца
            if (!isset($results[$supplier])) {
                $results[$supplier] = array_fill_keys($months, 0);
            }

            if (is_null($results[$supplier][$month] ?? null))
                $results[$supplier][$month] = 0;

            // Обновляем доходы по этому поставщику и месяцу
            $results[$supplier][$month] += $order->total_price;
            $results[$supplier]["base_percent"] = $order->supplier->percent ?? $defaultPercent;

        }


        $totalSum = 0;
        // Приводим данные к удобному виду
        $formattedResults = [];
        foreach ($results as $supplier => $monthlyData) {

            $basePercent = ($monthlyData["base_percent"] ?? 0) == 0 ? ($defaultPercent / 100) : $monthlyData["base_percent"];

            unset($monthlyData["base_percent"]);
            $income = array_values($monthlyData); // извлекаем массив доходов


            $totalIncome = array_sum($income); // общий доход за год
            $totalSum += $totalIncome;
            // Ваш процент от дохода (примерно 10%)
            $yourPercentage = $totalIncome * $basePercent;

            // Формируем итоговый массив
            $formattedResults[] = [
                'supplier' => $supplier,
                'base_percent' => $basePercent,
                'percentage' => $yourPercentage,
                'january' => $income[0],
                'february' => $income[1],
                'march' => $income[2],
                'april' => $income[3],
                'may' => $income[4],
                'june' => $income[5],
                'july' => $income[6],
                'august' => $income[7],
                'september' => $income[8],
                'october' => $income[9],
                'november' => $income[10],
                'december' => $income[11],
                'year' => $totalIncome,
            ];
        }

        return (object)[
            "details" => $formattedResults,
            "total_sum" => $totalSum,
            "total_percentage" => array_sum(array_column($formattedResults, 'percentage')),
        ];
    }

    public function getMonthlySalesSummaryForAllAgentsByEachSupplier($from = null, $to = null): array
    {
        $fromDate = $from ?: Carbon::now(); // начальная дата
        $toDate = $to ?: Carbon::now()->addMonth();                   // конечная дата

        // Получаем список поставщиков
        $suppliers = Supplier::query()->take(2)->get();

        $finalResult = [];
        foreach ($suppliers as $supplier) {
            $finalResult[] = $this->getMonthlySalesSummaryForAllAgentsByCurrentSupplier($supplier, $fromDate, $toDate);
        }

        return $finalResult;
    }

    public function getAdminsMonthlyByAgentRevenue($agent, $startDate, $endDate)
    {


        // Общая сумма продаж
        $totalSales = Sale::query()
            ->where("agent_id", $agent->id)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->sum('total_price');

        // Налог и переводы
        $taxPercent = env('TAX_PERCENT', 8); // %
        $transferPercent = env('TRANSFER_PERCENT', 8); // %

        $taxAmount = $totalSales * ($taxPercent / 100);
        $afterTax = $totalSales - $taxAmount;


        $revenueTotal = 0;
        $revenueWithoutTaxTotal = 0;

        // --- 1. Продажи по датам и поставщикам ---
        $salesByDateSupplier = Sale::query()
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where("agent_id", $agent->id)
            ->orderBy('sale_date')
            ->with('supplier')
            ->get()
            ->map(function ($sale) use ($taxPercent, &$revenueTotal, &$revenueWithoutTaxTotal) {

                $transferPercent = env('TRANSFER_PERCENT', 8); // %

                $percent = $sale->supplier->percent ?? 0;
                $revenueLocal = $sale->total_price * ($percent / 1);
                $taxAmount = $revenueLocal * ($taxPercent / 100);
                $revenueAfterTax = $revenueLocal - $taxAmount;

                $revenueTotal += $revenueLocal;
                $revenueWithoutTaxTotal += $revenueAfterTax;
                return [
                    'date' => $sale->sale_date,
                    'supplier_id' => $sale->supplier_id,
                    'supplier_name' => $sale->supplier->name ?? 'Неизвестный поставщик',
                    'sale_amount' => $sale->total_price,
                    'percent' => $percent,
                    'transfer' => $revenueLocal* ($transferPercent / 100),
                    'revenue_total' => $revenueLocal,
                    'revenue_after_tax' => $revenueAfterTax,
                ];
            });


        // --- 2. Доход админов ---
        $adminsRevenue = User::query()
            ->where('is_work', true)
            ->get()
            ->map(function ($user) use ($revenueTotal, $revenueWithoutTaxTotal) {
                $incomeTotal = $revenueTotal * ($user->percent / 100);
                $incomeAfterTax = $revenueWithoutTaxTotal * ($user->percent / 100);

                return [
                    'admin_id' => $user->id,
                    'admin_name' => $user->name,
                    'percent' => $user->percent,
                    'income_total' => $incomeTotal,
                    'income_after_tax' => $incomeAfterTax,
                ];
            });

        $agentPercent = env("AGENT_PERCENT", 5);

        $transferFromTotal = $revenueTotal * ($transferPercent / 100);
        $transferFromAfterTax = $revenueWithoutTaxTotal * ($transferPercent / 100);

        return [
            'agent' => [
                "id" => $agent->id,
                "name" => $agent->name,
                "salary" => $afterTax * ($agentPercent / 100)
            ],
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'summary' => [
                'total_sales' => $totalSales,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'after_tax' => $afterTax,
                'transfer_percent' => $transferPercent,
                'transfer_from_total' => $transferFromTotal,
                'transfer_from_after_tax' => $transferFromAfterTax,
                'revenue_total' => $revenueTotal,
                'revenue_without_tax_total' => $revenueWithoutTaxTotal,
            ],
            'sales_by_date_supplier' => $salesByDateSupplier,
            'admins' => $adminsRevenue,
        ];
    }

    public function getMonthlySalesSummaryForAllAgentsByCurrentSupplier($supplier, $fromDate, $toDate): array
    {

        $agents = Agent::all();

        $daysOfWeek = [
            'Sunday' => 'Воскресенье',
            'Monday' => 'Понедельник',
            'Tuesday' => 'Вторник',
            'Wednesday' => 'Среда',
            'Thursday' => 'Четверг',
            'Friday' => 'Пятница',
            'Saturday' => 'Суббота'
        ];

        $currentDate = clone $fromDate;

        // Подготовим отчет для текущего поставщика
        $supplierReport = [];
        // Проходим по каждому дню в периоде
        while ($currentDate <= $toDate) {
            // Создаем строку для текущих данных

            $day = [];
            // Пробегаемся по каждому агенту
            foreach ($agents as $agent) {
                // Запрашиваем продажи данного агента за текущий день
                $salesDetails = Sale::query()
                    ->where('supplier_id', $supplier->id)
                    ->where('agent_id', $agent->id)
                    ->where("status", "completed")
                    ->where("total_price", ">", 0)
                    ->whereDate('sale_date', "=", $currentDate->format('Y-m-d'))
                    ->get();


                // Формируем объект сделки
                $saleObject = [
                    'price' => $salesDetails->sum('total_price'),
                    'supplier_id' => $supplier->id,
                    'agent_id' => $agent->id,
                ];

                // Добавляем объект в таблицу
                $day[] = $saleObject;
            }

            // Суммируем продажи за день
            $totalDailySales = array_sum(array_column($day, 'price'));


            $dayName = $daysOfWeek[Carbon::parse($currentDate)->englishDayOfWeek];

            // Добавляем текущую строку в отчет поставщика
            $supplierReport[] = [
                'date' => $currentDate->format('d.m.Y'),
                'total' => $totalDailySales,
                'day_name' => $dayName,
                'day_details' => $day,
            ];;

            // Переходим к следующему дню
            $currentDate->addDay();
        }


        return [
            "id" => $supplier->id,
            "title" => $supplier->name ?? 'Неизвестный поставщик',
            'agents' => $agents,
            "total_sum" => array_sum(array_column($supplierReport, 'total')),
            "period" => $supplierReport,
        ];

    }

    public function getPersonalAgentSales(int $agentId, $fromDate = null, $toDate = null)
    {

        $fromDate = $fromDate ?: Carbon::now(); // начальная дата
        $toDate = $toDate ?: Carbon::now()->addMonth();

        // Получаем все продажи агента за указанный период
        $sales = Sale::with('supplier')
            ->where('agent_id', $agentId)
            ->where("status", "completed")
            ->whereBetween('sale_date', [$fromDate, $toDate])
            ->get();

        // Массив для хранения итоговых данных
        $result = [];

        foreach ($sales as $sale) {
            // Извлекаем данные о поставщике
            $supplier = $sale->supplier;

            // Собираем данные для строки отчета
            $result[] = [
                'sale_date' => Carbon::parse($sale->sale_date)->format('d.m.Y'), // дата продажи
                'supplier_name' => $supplier->name ?? 'Неизвестный поставщик',               // название поставщика
                'total_price' => $sale->total_price,              // сумма продажи
                'percent' => $supplier->percent,              // процент с продажи
                'commission' => $supplier->percent * $sale->total_price, // выручка (комиссия)
            ];
        }

        return $result;
    }

    public function getSalesGroupedByStatus(int $createdById, string $dateFrom, string $dateTo): array
    {
        // выборка по created_by_id и периоду
        $query = Sale::query()->where('created_by_id', $createdById)
            ->whereBetween('due_date', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay()
            ])
            ->orderBy('due_date', 'asc');

        $sales = $query->get();


        // формируем массив групп
        $result = [
            'all' => [],
            'pending' => [],
            'assigned' => [],
            'delivered' => [],
            'completed' => [],
            'rejected' => [],
        ];

        foreach ($sales as $sale) {
            // все заказы
            $result['all'][] = $sale;

            // по статусу
            $result[$sale->status][] = $sale;
        }

        return $result;
    }

    public function getProducts()
    {
        $products = Product::query()
            ->with('category', 'supplier')
            ->get();

        return $products;
    }

    public function getSuppliers()
    {
        $suppliers = Supplier::query()
            ->get();

        return $suppliers;
    }

    public function birthdaysNext($fromDate, $toDate)
    {
        $start = $fromDate->format('m-d');;
        $end = $toDate->format('m-d');;


        // Функция фильтрации по диапазону (учитывает переход через Новый год)
        $filterByRange = function ($query) use ($start, $end) {
            if ($start <= $end) {
                // Обычный диапазон
                return $query->whereRaw("DATE_FORMAT(birthday, '%m-%d') BETWEEN ? AND ?", [$start, $end]);
            } else {
                // Диапазон пересекает Новый год
                return $query->where(function ($q) use ($start, $end) {
                    $q->whereRaw("DATE_FORMAT(birthday, '%m-%d') >= ?", [$start])
                        ->orWhereRaw("DATE_FORMAT(birthday, '%m-%d') <= ?", [$end]);
                });
            }
        };

        // Пользователи
        $users = $filterByRange(User::query())
            ->get()
            ->map(function ($u) {
                $b = Carbon::parse($u->birthday);

                return [
                    'name' => $u->name,
                    'date' => $b->format('Y-m-d'),
                    'weekday' => Carbon::create(now()->year, $b->month, $b->day)
                        ->locale('ru')
                        ->dayName,
                    'type' => 'пользователь',
                ];
            });

        // Поставщики
        $suppliers = $filterByRange(Supplier::query())
            ->get()
            ->map(function ($s) {
                $b = Carbon::parse($s->birthday);

                return [
                    'name' => $s->name,
                    'date' => $b->format('Y-m-d'),
                    'weekday' => Carbon::create(now()->year, $b->month, $b->day)
                        ->locale('ru')
                        ->dayName,
                    'type' => 'поставщик',

                ];
            });

        // Объединяем и сортируем по MM-DD (игнорируя год)
        return collect($users->toArray())
            ->merge($suppliers->toArray())
            ->sortBy(function ($item) {
                return Carbon::parse($item['date'])->format('m-d');
            })
            ->values();


    }
}
