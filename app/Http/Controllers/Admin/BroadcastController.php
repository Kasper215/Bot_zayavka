<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Facades\BotMethods;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Support\Facades\Log;
use Exception;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        // Только админы могут делать рассылку
        if (!$request->user()->isAdmin()) {
            abort(403, 'Недостаточно прав для доступа к рассылке');
        }

        $userCount = User::whereNotNull('telegram_chat_id')->count();
        return Inertia::render('Admin/Broadcast', [
            'userCount' => $userCount
        ]);
    }

    public function send(Request $request)
    {
        // Только админы могут делать рассылку
        if (!$request->user()->isAdmin()) {
            abort(403, 'Недостаточно прав для выполнения рассылки');
        }
        $request->validate([
            'message' => 'required|string|min:5',
            'image' => 'nullable|image|max:10240', // max 10MB
        ]);

        $message = $request->input('message');
        $imageFile = $request->file('image');
        $users = User::whereNotNull('telegram_chat_id')->get();
        
        $successCount = 0;
        $failCount = 0;

        $imagePath = null;
        if ($imageFile) {
            $imagePath = $imageFile->getRealPath();
        }

        foreach ($users as $user) {
            try {
                if ($imagePath) {
                    BotMethods::bot()->sendPhoto(
                        $user->telegram_chat_id, 
                        $message, 
                        InputFile::create($imagePath, $imageFile->getClientOriginalName())
                    );
                } else {
                    BotMethods::bot()->sendMessage($user->telegram_chat_id, $message);
                }
                
                $successCount++;
                // Small sleep to avoid Telegram rate limits
                if ($successCount % 20 === 0) {
                    usleep(500000); 
                }
            } catch (Exception $e) {
                Log::error("Broadcast failed for user {$user->id}: " . $e->getMessage());
                $failCount++;
            }
        }

        return back()->with('success', "Рассылка завершена. Отправлено: {$successCount}, Ошибок: {$failCount}");
    }
}
