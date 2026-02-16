<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FonnteChannel;

class OvertimeFinalApprovalNotification extends Notification
{
    use Queueable;

    protected $overtime;

    public function __construct($overtime)
    {
        $this->overtime = $overtime;
    }

    public function via(object $notifiable): array
    {
        return [FonnteChannel::class];
    }

    public function toWhatsApp(object $notifiable): array
    {
        $appUrl = config('app.url');

        // Aman kalau relasi kosong
        $requestNumber = $this->overtime->request_number ?? '-';
        $date = optional($this->overtime->date)->format('d/m/Y') ?? '-';
        $departmentName = optional($this->overtime->department)->name ?? '-';

        // Direct ke detail overtime
        $redirectLink = "{$appUrl}/overtime/{$this->overtime->id}";

        $message = "Halo {$notifiable->name},\n\n" .
            "✅ Pengajuan lembur Anda telah disetujui oleh seluruh approver.\n\n" .
            "Detail Pengajuan:\n" .
            "No. SPK : {$requestNumber}\n" .
            "Tanggal : {$date}\n" .
            "Departemen : {$departmentName}\n\n" .
            "Status : APPROVED\n\n" .
            "Silakan cek detail pengajuan melalui link berikut:\n" .
            "{$redirectLink}\n\n" .
            "Terima kasih.";

        return [
            'target'  => $notifiable->phone,
            'message' => $message,
        ];
    }
}
