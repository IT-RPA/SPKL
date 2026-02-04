<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FonnteChannel;

class OvertimeRejectedNotification extends Notification
{
    use Queueable;

    protected $overtime;
    protected $rejectorName;

    /**
     * Create a new notification instance.
     */
    public function __construct($overtime, $rejectorName = null)
    {
        $this->overtime = $overtime;
        $this->rejectorName = $rejectorName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [FonnteChannel::class];
    }

    /**
     * WhatsApp message.
     */
    public function toWhatsApp(object $notifiable): array
    {
        $appUrl = config('app.url');

        // FIX: fallback kalau joblevel null
        $levelCode = optional($notifiable->jobLevel)->code
                   ?? optional(optional($notifiable->employee)->jobLevel)->code
                   ?? '';

        $rejectorName = $this->rejectorName ?: 'Approver';

        // FIX: redirect dengan job_level + encode
        $redirectLink = "{$appUrl}/approvals/data?job_level=" . urlencode($levelCode);

        $message = "Halo {$notifiable->name},\n\n" .
            "Pengajuan lembur Anda telah ditolak oleh {$rejectorName}.\n\n" .
            "Silakan cek kembali detailnya melalui sistem SPKL.\n\n" .
            "{$redirectLink}\n\n" .
            "Terima kasih.";

        return [
            'target'  => $notifiable->phone,
            'message' => $message,
        ];
    }
}
