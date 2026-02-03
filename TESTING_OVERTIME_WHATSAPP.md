# Testing Notifikasi WhatsApp Pengajuan Lembur

## 🧪 Cara Testing dengan Tinker

### 1. Masuk ke Tinker
```bash
php artisan tinker
```

### 2. Test OvertimeRequestApprovalNotification (Kirim ke Approver)
```php
// Ambil overtime request terbaru
$overtime = \App\Models\OvertimeRequest::latest()->first();

// Ambil user approver (ganti nama sesuai data Anda)
$user = \App\Models\User::where('name', 'Usman')->first();

// Kirim notifikasi
$user->notify(new \App\Notifications\OvertimeRequestApprovalNotification($overtime));

echo "Notifikasi approval dikirim ke: " . $user->name . " (" . $user->phone . ")";
```

### 3. Test OvertimeFinalApprovalNotification (Kirim ke Pemohon)
```php
// Ambil overtime request terbaru
$overtime = \App\Models\OvertimeRequest::latest()->first();

// Ambil user pemohon
$requester = \App\Models\User::where('id', $overtime->requester_id)->first();

// Kirim notifikasi final approval
$requester->notify(new \App\Notifications\OvertimeFinalApprovalNotification($overtime));

echo "Notifikasi final approval dikirim ke: " . $requester->name . " (" . $requester->phone . ")";
```

### 4. Test OvertimeRejectedNotification (Kirim ke Pemohon)
```php
// Ambil overtime request terbaru
$overtime = \App\Models\OvertimeRequest::latest()->first();

// Ambil user pemohon
$requester = \App\Models\User::where('id', $overtime->requester_id)->first();

// Kirim notifikasi rejection dengan nama approver
$rejectorName = "Budi Santoso"; // Ganti dengan nama approver yang menolak
$requester->notify(new \App\Notifications\OvertimeRejectedNotification($overtime, $rejectorName));

echo "Notifikasi rejection dikirim ke: " . $requester->name . " (" . $requester->phone . ")";
```

## 🔍 Verifikasi Testing

### 1. Cek Log Laravel
```bash
tail -f storage/logs/laravel.log
```

### 2. Cek Format Pesan WhatsApp
Pastikan format pesan sesuai dengan spesifikasi:

**A. OvertimeRequestApprovalNotification:**
```
Halo {nama_approver},

Pemberitahuan: terdapat pengajuan lembur yang memerlukan approval dari Anda.

Silakan cek dan proses melalui sistem SPKL agar dapat dilanjutkan ke tahap berikutnya.

Terima kasih.
```

**B. OvertimeFinalApprovalNotification:**
```
Halo {nama_pemohon},

Pengajuan lembur Anda telah disetujui sepenuhnya.

Silakan lanjutkan proses sesuai prosedur.

Terima kasih.
```

**C. OvertimeRejectedNotification:**
```
Halo {nama_pemohon},

Pengajuan lembur Anda telah ditolak oleh {nama_approver}.

Silakan cek kembali detailnya melalui sistem SPKL.

Terima kasih.
```

## 🚨 Troubleshooting

### 1. Jika Notifikasi Tidak Terkirim
```php
// Cek apakah user punya nomor HP
$user = \App\Models\User::find(1);
echo "Phone: " . $user->phone;

// Cek format nomor HP (harus 62xxxxxxxxxx)
if (!preg_match('/^62[0-9]{9,13}$/', $user->phone)) {
    echo "Format nomor HP salah: " . $user->phone;
}
```

### 2. Cek Token Fonnte
```php
echo "Fonnte Token: " . env('FONNTE_TOKEN');
```

### 3. Test Manual Fonnte Channel
```php
$channel = new \App\Channels\FonnteChannel();
$notification = new \App\Notifications\OvertimeRequestApprovalNotification($overtime);
$user = \App\Models\User::where('phone', '6281234567890')->first();

$channel->send($user, $notification);
```

## ✅ Checklist Testing

- [ ] OvertimeRequestApprovalNotification terkirim ke approver
- [ ] OvertimeFinalApprovalNotification terkirim ke pemohon
- [ ] OvertimeRejectedNotification terkirim ke pemohon
- [ ] Format pesan sesuai spesifikasi
- [ ] Log Laravel menunjukkan notifikasi berhasil
- [ ] Nomor HP dalam format yang benar (62xxxxxxxxxx)
- [ ] Token Fonnte valid dan aktif

## 📱 Test End-to-End

### 1. Buat Pengajuan Lembur Baru
1. Login sebagai user biasa
2. Buat pengajuan lembur baru
3. Cek apakah approver pertama menerima notifikasi

### 2. Approve Pengajuan
1. Login sebagai approver pertama
2. Approve pengajuan
3. Cek apakah approver berikutnya menerima notifikasi (jika ada)
4. Atau pemohon menerima notifikasi final (jika sudah selesai)

### 3. Reject Pengajuan
1. Login sebagai approver
2. Reject pengajuan
3. Cek apakah pemohon menerima notifikasi rejection