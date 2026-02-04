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
     * 
     * Format pesan PERSIS seperti yang diminta:
     * - Pengajuan lembur Anda telah disetujui sepenuhnya.
     * - Silakan lanjutkan proses sesuai prosedur.
     * - Terima kasih.
     */
    public function toWhatsApp(object $notifiable): array
    {
        $appUrl = config('app.url');

        $message = "Halo {$notifiable->name},\n\n" .
            "Pengajuan lembur Anda telah disetujui sepenuhnya.\n\n" .
            "Silakan lanjutkan proses sesuai prosedur.\n\n" .
            "{$appUrl}/login?redirect=/approvals/data?job_level={$notifiable->job_level->code}\n\n" .
            "Terima kasih.";

        return [
            'target'  => $notifiable->phone,
            'message' => $message,
        ];
    }
}
