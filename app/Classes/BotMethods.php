<?php

namespace App\Classes;

use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class BotMethods
{
    protected $bot;

    /**
     * Get the Telegram Bot API instance
     */
    public function bot()
    {
        if (!$this->bot) {
            $token = env('TELEGRAM_BOT_TOKEN');
            if (!$token) {
                Log::error("TELEGRAM_BOT_TOKEN is missing in .env");
            }
            $this->bot = new Api($token);
        }
        return $this->bot;
    }

    /**
     * Send a simple text message
     */
    public function sendMessage($chatId, $text, $parseMode = 'HTML')
    {
        try {
            if (!$chatId) {
                Log::warning("Telegram sendMessage: chat_id is empty");
                return false;
            }
            
            return $this->bot()->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram sendMessage Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a document to a chat
     */
    public function sendDocument($chatId, $caption, $document)
    {
        try {
            return $this->bot()->sendDocument([
                'chat_id' => $chatId,
                'caption' => $caption,
                'document' => $document,
            ]);
        } catch (\Exception $e) {
            Log::error("Telegram sendDocument Error: " . $e->getMessage());
            return false;
        }
    }
}
