<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteHelper
{
    /**
     * Send WhatsApp message using Fonnte API
     *
     * @param string $target Phone number
     * @param string $message Message content
     * @return bool|array
     */
    public static function send($target, $message)
    {
        if (empty($target)) {
            Log::warning('FonnteHelper: Target phone number is empty.');
            return false;
        }

        try {
            $token = env('FONNTE_TOKEN');

            if (empty($token)) {
                Log::error('FonnteHelper: FONNTE_TOKEN is not set in .env');
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                        'target' => $target,
                        'message' => $message,
                    ]);

            $result = $response->json();

            if ($response->successful()) {
                Log::info("FonnteHelper: Message sent to {$target}", ['response' => $result]);
                return $result;
            } else {
                Log::error("FonnteHelper: Failed to send message to {$target}", ['response' => $result]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("FonnteHelper: Exception - " . $e->getMessage());
            return false;
        }
    }
}
