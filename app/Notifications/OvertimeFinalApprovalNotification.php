<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FonnteChannel;

class OvertimeFinalApprovalNotification extends Notification
{
    use Queueable;

    protected $overtime;

    /**
     * Create a new notification instance.
     */
    public function __construct($overtime)
    {
        $this->overtime = $overtime;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [FonnteChannel::class];
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): array
    {
        $appUrl = config('app.url');

        // FIX: fallback kalau user/jobLevel null -> WA tetap terkirim
        $levelCode = optional($notifiable->jobLevel)->code
                   ?? optional(optional($notifiable->employee)->jobLevel)->code
                   ?? '';

        $redirectLink = "{$appUrl}/approvals/data?job_level=" . urlencode($levelCode);

        $message = "Halo {$notifiable->name},\n\n" .
            "Pengajuan lembur Anda telah disetujui sepenuhnya.\n\n" .
            "Silakan lanjutkan proses sesuai prosedur.\n\n" .
            "{$redirectLink}\n\n" .
            "Terima kasih.";

        return [
            'target'  => $notifiable->phone,
            'message' => $message,
        ];
    }
}
