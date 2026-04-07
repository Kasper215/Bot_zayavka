<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class PublicLeadController extends Controller
{
    public function submitForm(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'service_type' => 'required|string|max:255',
            'volume_stage' => 'required|string|max:255',
            'contacts' => 'required|string|max:255',
            'extra' => 'nullable|string',
            'calc_data' => 'nullable|string',
            'calc_price' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240', // до 10МБ на файл
        ]);

        try {
            // Создаем заявку (Lead)
            $lead = Lead::create([
                'user_id' => auth()->id(),
                'client_name' => $validated['client_name'],
                'contacts' => $validated['contacts'],
                'extra' => $validated['extra'] ?? null,
                'calc_price' => $validated['calc_price'] ?? null,
                'service_type' => $validated['service_type'],
                'volume_stage' => $validated['volume_stage'] . ($validated['calc_data'] ? " (" . $validated['calc_data'] . ")" : ""),
                'status' => 'new',
            ]);

            // Обработка файлов
            $uploadedFiles = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $originalName = $file->getClientOriginalName();
                    // Sanitizing filename: timestamp + random string + original extension
                    $safeName = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs("leads/{$lead->id}", $safeName, 'local');
                    $uploadedFiles[] = [
                        'name' => $originalName,
                        'safe_name' => $safeName,
                        'path' => $path,
                        'size' => $file->getSize()
                    ];
                }
                $lead->update(['files' => json_encode($uploadedFiles)]);
            }

            $priceStr = $validated['calc_price'] ?? '';
            $leftPart = explode('до', $priceStr)[0];
            $numericPrice = (float) preg_replace('/[^0-9]/', '', $leftPart);

            if ($numericPrice > 0) {
                 $lead->update(['status' => 'pending_payment']);
            } else {
                try {
                    // --- ОТПРАВКА ПУШ-УВЕДОМЛЕНИЙ ---
                    // Рассылаем всем Менеджерам (1), Админам (2) и Kasper (3)
                    $staff = \App\Models\User::whereIn('role', [1, 2, 3])->get();
                    Log::info("Notifying staff: " . $staff->count() . " members found.");
                    \Illuminate\Support\Facades\Notification::send($staff, new \App\Notifications\NewLeadNotification($lead));
                    // ---------------------------------
                } catch (\Exception $e) {
                    // Игнорируем ошибку уведомлений
                    Log::error("Notification Error: " . $e->getMessage());
                }

                // --- TG CHANNEL NOTIFICATION ---
                try {
                    $adminChannel = env('TELEGRAM_ADMIN_CHANNEL');
                    if ($adminChannel) {
                        $text = "🆕 <b>НОВАЯ ЗАЯВКА!</b>\n\n" .
                               "👤 Клиент: {$lead->client_name}\n" .
                               "📞 Контакты: {$lead->contacts}\n" .
                               "📚 Услуга: {$lead->service_type}\n" .
                               "📊 Объем: {$lead->volume_stage}\n" .
                               "💰 Оценка: " . ($lead->calc_price ?? 'N/A') . "\n\n" .
                               "📝 Заметки: " . ($lead->extra ?? 'нет');
                        
                        \App\Facades\BotMethods::bot()->sendMessage($adminChannel, $text);
                    }
                } catch (\Exception $e) {
                    Log::error("TG Lead Notification Error: " . $e->getMessage());
                }
                // -------------------------------
            }

            return response()->json([
                'status' => 'ok',
                'message' => 'Ваша заявка успешно отправлена.',
                'lead_id' => $lead->id
            ]);
            
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Ошибка сохранения'], 500);
        }
    }
}
