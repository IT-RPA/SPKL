<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteChannel
{
    /**
     * Send the given notification via Fonnte WhatsApp API.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // 1. Cek apakah notification punya method toWhatsApp
        if (!method_exists($notification, 'toWhatsApp')) {
            Log::warning("FonnteChannel: Notification doesn't have toWhatsApp method", [
                'notification_class' => get_class($notification)
            ]);
            return;
        }

        // 2. Ambil data dari notification
        $data = $notification->toWhatsApp($notifiable);

        // 3. Validasi nomor HP ada
        if (empty($data['target'])) {
            Log::warning("FonnteChannel: No phone number for user", [
                'user_id' => $notifiable->id ?? 'unknown',
                'user_name' => $notifiable->name ?? 'unknown'
            ]);
            return;
        }

        // 4. Validasi format nomor HP (harus 62xxxxxxxxxx)
        if (!preg_match('/^62[0-9]{9,13}$/', $data['target'])) {
            Log::warning("FonnteChannel: Invalid phone format", [
                'user_id' => $notifiable->id,
                'phone' => $data['target'],
                'expected_format' => '62xxxxxxxxxx (9-13 digits after 62)'
            ]);
            return;
        }

        // 5. Validasi pesan tidak kosong
        if (empty($data['message'])) {
            Log::warning("FonnteChannel: Empty message", [
                'user_id' => $notifiable->id
            ]);
            return;
        }

        try {
            // 6. Log sebelum kirim
            Log::info("FonnteChannel: Preparing to send WA", [
                'target' => $data['target'],
                'user_id' => $notifiable->id,
                'user_name' => $notifiable->name
            ]);

            // 7. Kirim ke Fonnte API
            $response = Http::withHeaders([
                'Authorization' => env('FONNTE_TOKEN'),
            ])->post('https://api.fonnte.com/send', [
                        'target' => $data['target'],
                        'message' => $data['message'],
                    ]);

            // 8. Log response detail
            Log::info("Fonnte API Response", [
                "target" => $data['target'],
                "message" => $data['message'],
                "response_body" => $response->body(),
                "status_code" => $response->status(),
                "success" => $response->successful()
            ]);

            // 9. Cek apakah sukses
            if ($response->successful()) {
                Log::info("FonnteChannel: Message sent successfully", [
                    'target' => $data['target'],
                    'user_name' => $notifiable->name
                ]);
            } else {
                Log::error("FonnteChannel: Failed to send message", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'target' => $data['target']
                ]);
            }

        } catch (\Exception $e) {
            // 10. Log error jika ada exception
            Log::error("FonnteChannel: Exception occurred", [
                'error_message' => $e->getMessage(),
                'target' => $data['target'] ?? 'unknown',
                'user_id' => $notifiable->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
