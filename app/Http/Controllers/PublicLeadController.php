<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use App\Facades\BotMethods;

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

        $extraInfo = !empty($validated['extra']) ? "\nДоп. информация: " . $validated['extra'] : "";
        $fullContacts = $validated['contacts'] . $extraInfo;
            // Формируем описание объема/стадии
            $summaryStage = $validated['volume_stage'];
            if (!empty($validated['calc_data'])) {
                $summaryStage = "🧮 {$validated['calc_data']}\n💰 Оценка: {$validated['calc_price']}";
            }

        try {
            // Создаем заявку (Lead)
            $lead = Lead::create([
                'client_name' => $validated['client_name'],
                'contacts' => $fullContacts,
                'service_type' => $validated['service_type'],
                'volume_stage' => $summaryStage,
                'status' => 'new',
            ]);

            // Обработка файлов
            $uploadedFiles = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->storeAs("leads/{$lead->id}", $originalName, 'public');
                    $uploadedFiles[] = [
                        'name' => $originalName,
                        'path' => $path,
                        'size' => $file->getSize()
                    ];
                }
                $lead->update(['files' => json_encode($uploadedFiles)]);
            }

            try {
                $tgMessage = "#новая_заявка_pwa\n✅ <b>Новая заявка с сайта!</b>\n\n" .
                             "<b>ФИО:</b> {$validated['client_name']}\n" .
                             "<b>Контакт:</b> {$validated['contacts']}\n" .
                             "<b>Услуга:</b> {$validated['service_type']}\n" .
                             "<b>Стадия/Расчет:</b>\n<i>{$summaryStage}</i>";

                if (!empty($validated['extra'])) {
                    $tgMessage .= "\n\n<b>💬 О проекте:</b> {$validated['extra']}";
                }

                if (count($uploadedFiles) > 0) {
                    $tgMessage .= "\n\n📎 <b>Прикреплено файлов: " . count($uploadedFiles) . "</b>";
                }

                BotMethods::bot()->sendMessage(
                    env("TELEGRAM_ADMIN_CHANNEL"),
                    $tgMessage
                );
            } catch (\Exception $e) {
                // Игнорируем ошибку телеграма
                Log::error($e->getMessage());
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
