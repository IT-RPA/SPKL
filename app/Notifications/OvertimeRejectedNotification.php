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
     * Get the WhatsApp representation of the notification.
     * 
     * Format pesan PERSIS seperti yang diminta:
     * - Pengajuan lembur Anda telah ditolak oleh {nama_approver}.
     * - Silakan cek kembali detailnya melalui sistem SPKL.
     * - Terima kasih.
     */
    public function toWhatsApp(object $notifiable): array
    {
        $appUrl = config('app.url');

        $rejectorName = $this->rejectorName ?: 'Approver';
        
        $message = "Halo {$notifiable->name},\n\n" .
                "Pengajuan lembur Anda telah ditolak oleh {$rejectorName}.\n\n" .
                "Silakan cek kembali detailnya melalui sistem SPKL.\n\n" .
                "{$appUrl}/login?redirect=/approvals/data\n\n" .
                "Terima kasih.";

        return [
            'target'  => $notifiable->phone,
            'message' => $message,
        ];
    }
}