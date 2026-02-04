<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FonnteChannel;

class OvertimeRequestApprovalNotification extends Notification
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

        // FIX: fallback kalau user/jobLevel null → WA TETAP TERKIRIM
        $levelCode = optional($notifiable->jobLevel)->code
                   ?? optional(optional($notifiable->employee)->jobLevel)->code
                   ?? '';

        $redirectLink = "{$appUrl}/approvals/data?job_level=" . urlencode($levelCode);

        $message = "Halo {$notifiable->name},\n\n" .
            "Pemberitahuan: terdapat pengajuan lembur yang memerlukan approval dari Anda.\n\n" .
            "Silakan cek dan proses melalui sistem SPKL agar dapat dilanjutkan ke tahap berikutnya.\n\n" .
            "{$redirectLink}\n\n" .
            "Terima kasih.";

        return [
            'target'  => $notifiable->phone,
            'message' => $message,
        ];
    }
}
