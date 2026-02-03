<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FonnteChannel;

class PlanningApprovalNotification extends Notification
{
    use Queueable;

    protected $planning;

    /**
     * Create a new notification instance.
     */
    public function __construct($planning)
    {
        $this->planning = $planning;
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

    // Ambil LEVEL CODE (bukan ID)
    $levelCode = $notifiable->jobLevel->code ?? '';

    // Redirect link dengan CODE
    $redirectLink = "{$appUrl}/login?redirect=/approvals/data?job_level={$levelCode}";

    $message = "Halo {$notifiable->name},\n\n" .
        "Pemberitahuan: terdapat planning lembur yang memerlukan approval dari Anda.\n\n" .
        "Silakan cek dan proses melalui sistem SPKL melalui link berikut:\n" .
        "{$redirectLink}\n\n" .
        "Terima kasih.";

    return [
        'target'  => $notifiable->phone,
        'message' => $message,
    ];
}



}
