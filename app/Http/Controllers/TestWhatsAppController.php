<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OvertimePlanning;
use App\Notifications\PlanningApprovalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestWhatsAppController extends Controller
{
    /**
     * Test WhatsApp Notification
     * 
     * Route: GET /test-wa
     * 
     * Cara pakai:
     * 1. Pastikan ada user dengan nomor HP format 628xxx
     * 2. Akses: http://localhost/test-wa
     * 3. Atau dengan nomor spesifik: http://localhost/test-wa?phone=628123456789
     */
    public function testWhatsApp(Request $request)
    {
        try {
            // 1. Cari user berdasarkan phone (bisa dari parameter atau auto)
            if ($request->has('phone')) {
                $user = User::where('phone', $request->phone)->first();

                if (!$user) {
                    return $this->errorResponse("User dengan nomor {$request->phone} tidak ditemukan.");
                }
            } else {
                // Cari user pertama yang punya nomor HP valid
                $user = User::whereNotNull('phone')
                    ->where('phone', 'like', '628%')
                    ->first();

                if (!$user) {
                    return $this->errorResponse(
                        "Tidak ada user dengan nomor HP valid (format 628xxx).<br><br>" .
                        "<strong>Solusi:</strong><br>" .
                        "1. Login ke sistem<br>" .
                        "2. Buka menu Users<br>" .
                        "3. Edit user dan tambahkan nomor HP format: 628xxxxxxxxxx<br>" .
                        "4. Atau akses: /test-wa?phone=628xxxxxxxxxx"
                    );
                }
            }

            // 2. Buat dummy planning untuk testing
            $dummyPlanning = new \stdClass();
            $dummyPlanning->id = 999;
            $dummyPlanning->planning_number = 'TEST-' . date('Ymd') . '-001';
            $dummyPlanning->department = new \stdClass();
            $dummyPlanning->department->name = 'Test Department';
            $dummyPlanning->planned_date = date('Y-m-d');
            $dummyPlanning->work_description = 'Testing WhatsApp Notification';

            // 3. Log sebelum kirim
            Log::info("TestWhatsApp: Sending notification", [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'phone' => $user->phone
            ]);

            // 4. Kirim notifikasi
            $user->notify(new PlanningApprovalNotification($dummyPlanning));

            // 5. Return success response
            return $this->successResponse($user);

        } catch (\Exception $e) {
            Log::error("TestWhatsApp: Error", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                "Error: " . $e->getMessage() . "<br><br>" .
                "<strong>Trace:</strong><br>" .
                "<pre>" . $e->getTraceAsString() . "</pre>"
            );
        }
    }

    /**
     * Success response HTML
     */
    private function successResponse($user)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test WhatsApp - Success</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
                .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .info-label { font-weight: bold; color: #0066cc; }
                .message-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; }
                .instructions { background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px; }
                pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='success'>✅ Pesan WhatsApp Berhasil Dikirim!</div>
                
                <div class='info'>
                    <div><span class='info-label'>Target Nomor:</span> {$user->phone}</div>
                    <div><span class='info-label'>Nama User:</span> {$user->name}</div>
                    <div><span class='info-label'>Email:</span> {$user->email}</div>
                    <div><span class='info-label'>Waktu Kirim:</span> " . now()->format('d/m/Y H:i:s') . "</div>
                </div>

                <div class='message-box'>
                    <strong>Pesan yang dikirim:</strong><br><br>
                    Pemberitahuan: terdapat planning lembur yang memerlukan approval dari Anda.<br><br>
                    Silakan cek dan proses melalui sistem SPKL agar dapat dilanjutkan ke tahap berikutnya.<br><br>
                    Terima kasih.
                </div>

                <div class='instructions'>
                    <strong>📱 Langkah Selanjutnya:</strong><br>
                    1. Cek WhatsApp Anda di nomor: <strong>{$user->phone}</strong><br>
                    2. Cek file log: <code>storage/logs/laravel.log</code><br>
                    3. Cari baris dengan: <code>Fonnte API Response</code><br>
                    4. Lihat response dari Fonnte API
                </div>

                <div style='margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 5px;'>
                    <strong>🔍 Debug Info:</strong><br>
                    <small>User ID: {$user->id} | Phone: {$user->phone} | Time: " . now() . "</small>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Error response HTML
     */
    private function errorResponse($message)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test WhatsApp - Error</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .error { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
                .message { background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; }
                pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='error'>❌ Error</div>
                <div class='message'>{$message}</div>
            </div>
        </body>
        </html>
        ";
    }
}
