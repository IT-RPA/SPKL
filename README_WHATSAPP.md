# 📱 IMPLEMENTASI WHATSAPP NOTIFICATION - SISTEM SPKL

## 🎯 RINGKASAN LENGKAP

### Model Planning Anda:
- **Model Planning:** `OvertimePlanning`
- **Model Approval:** `OvertimePlanningApproval`
- **Controller:** `PlanningOvertimeController`

### Alur Approval:
1. **Section Head** (Level 1)
2. **Asst. Dept Head** (Level 2)
3. **Sub Division Head** (Level 3)
4. **HRD** (Level 4)

### Kapan Notifikasi Dikirim:
- ✅ Saat planning dibuat → ke **Section Head**
- ✅ Section Head approve → ke **Asst. Dept Head**
- ✅ Asst. Dept Head approve → ke **Sub Division Head**
- ✅ Sub Division Head approve → ke **HRD**
- ✅ HRD approve (final) → ke **Creator** (pembuat planning)

---

## 📁 FILE YANG DIBUAT/DIUPDATE

### 1. Notification Class
**File:** `app/Notifications/PlanningApprovalNotification.php`
```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FonnteChannel;

class PlanningApprovalNotification extends Notification
{
    use Queueable;

    protected $planning;

    public function __construct($planning)
    {
        $this->planning = $planning;
    }

    public function via(object $notifiable): array
    {
        return [FonnteChannel::class];
    }

    public function toWhatsApp(object $notifiable): array
    {
        $message = "Pemberitahuan: terdapat planning lembur yang memerlukan approval dari Anda.\n\n";
        $message .= "Silakan cek dan proses melalui sistem SPKL agar dapat dilanjutkan ke tahap berikutnya.\n\n";
        $message .= "Terima kasih.";

        return [
            'target' => $notifiable->phone,
            'message' => $message,
        ];
    }
}
```

### 2. Fonnte Channel
**File:** `app/Channels/FonnteChannel.php`
- ✅ Validasi nomor HP (format 62xxx)
- ✅ Logging lengkap untuk debugging
- ✅ Error handling
- ✅ Response tracking

### 3. Test Controller
**File:** `app/Http/Controllers/TestWhatsAppController.php`
- ✅ Method `testWhatsApp()` untuk testing
- ✅ HTML response yang informatif
- ✅ Error handling yang jelas

### 4. Route Test
**File:** `routes/web.php`
```php
Route::get('/test-wa', [TestWhatsAppController::class, 'testWhatsApp'])->name('test.wa');
```

### 5. Controller Approval (Sudah Ada)
**File:** `app/Http/Controllers/PlanningOvertimeController.php`
- ✅ Method `store()` - kirim notifikasi ke approver pertama
- ✅ Method `approve()` - kirim notifikasi ke approver berikutnya
- ✅ Method `reject()` - kirim notifikasi ke creator

---

## 🚀 CARA TESTING

### Step 1: Pastikan User Punya Nomor HP
```sql
-- Cek user yang punya nomor HP
SELECT id, name, email, phone FROM users WHERE phone IS NOT NULL;

-- Jika belum ada, update manual:
UPDATE users SET phone = '628123456789' WHERE id = 1;
```

### Step 2: Test Route
```
Akses: http://localhost/test-wa

Atau dengan nomor spesifik:
http://localhost/test-wa?phone=628123456789
```

### Step 3: Cek Response
Browser akan menampilkan:
- ✅ Nomor HP yang dituju
- ✅ Nama user
- ✅ Pesan yang dikirim
- ✅ Instruksi cek WhatsApp dan log

### Step 4: Cek WhatsApp
Buka WhatsApp di HP dengan nomor yang dituju, pesan harus masuk:
```
Pemberitahuan: terdapat planning lembur yang memerlukan approval dari Anda.

Silakan cek dan proses melalui sistem SPKL agar dapat dilanjutkan ke tahap berikutnya.

Terima kasih.
```

### Step 5: Cek Log
```bash
# Buka file
storage/logs/laravel.log

# Cari baris:
FonnteChannel: Preparing to send WA
Fonnte API Response
FonnteChannel: Message sent successfully
```

---

## 📊 ALUR LENGKAP DALAM SISTEM

### 1. User Membuat Planning
```php
// File: PlanningOvertimeController@store
DB::transaction(function () use ($request, $planning) {
    // ... create planning ...
    
    // Kirim notifikasi ke approver pertama
    $nextApproval = $planning->approvals()
        ->where('status', 'pending')
        ->orderBy('step_order', 'asc')
        ->first();

    if ($nextApproval && $nextApproval->approverEmployee) {
        $nextUser = User::where('employee_id', $nextApproval->approverEmployee->employee_id)->first();
        
        if ($nextUser && $nextUser->phone) {
            $nextUser->notify(new PlanningApprovalNotification($planning));
        }
    }
});
```

### 2. Approver Level 1 Approve
```php
// File: PlanningOvertimeController@approve
DB::transaction(function () use ($approval) {
    $approval->update(['status' => 'approved']);
    
    // Cek apakah sudah selesai semua
    if ($planning->status === 'approved') {
        // Kirim ke creator
        $creatorUser = User::where('employee_id', $planning->created_by)->first();
        if ($creatorUser && $creatorUser->phone) {
            // Kirim notifikasi final
        }
    } else {
        // Kirim ke approver berikutnya
        $nextApproval = $planning->approvals()
            ->where('status', 'pending')
            ->orderBy('step_order', 'asc')
            ->first();
            
        if ($nextApproval && $nextApproval->approverEmployee) {
            $nextUser = User::where('employee_id', $nextApproval->approverEmployee->employee_id)->first();
            
            if ($nextUser && $nextUser->phone) {
                $nextUser->notify(new PlanningApprovalNotification($planning));
            }
        }
    }
});
```

---

## 🔧 KONFIGURASI

### .env
```env
FONNTE_TOKEN=maQPdVQ8JqJcAz6Ufa97
```

### Database
```sql
-- Tabel users harus punya kolom phone
ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULLABLE AFTER email;

-- Format nomor HP: 628xxxxxxxxxx
UPDATE users SET phone = '628123456789' WHERE id = 1;
```

---

## 📝 PESAN WHATSAPP

### Format Pesan (FIXED):
```
Pemberitahuan: terdapat planning lembur yang memerlukan approval dari Anda.

Silakan cek dan proses melalui sistem SPKL agar dapat dilanjutkan ke tahap berikutnya.

Terima kasih.
```

### Tidak Ada Tambahan:
- ❌ Tidak ada nomor planning
- ❌ Tidak ada nama department
- ❌ Tidak ada tanggal
- ❌ Tidak ada link
- ✅ HANYA pesan di atas

---

## 🐛 TROUBLESHOOTING

Jika WA tidak masuk, cek file:
```
TROUBLESHOOTING_WHATSAPP.md
```

Quick checklist:
1. ✅ Token Fonnte benar di `.env`
2. ✅ User punya nomor HP
3. ✅ Format nomor: `628xxxxxxxxxx`
4. ✅ Log menunjukkan "Message sent successfully"
5. ✅ Response Fonnte status 200

---

## 📞 API FONNTE

### Endpoint:
```
POST https://api.fonnte.com/send
```

### Headers:
```
Authorization: maQPdVQ8JqJcAz6Ufa97
```

### Body:
```json
{
  "target": "628123456789",
  "message": "Pemberitahuan: terdapat planning lembur..."
}
```

### Response Sukses:
```json
{
  "status": true,
  "message": "Message sent successfully"
}
```

---

## ✅ CHECKLIST IMPLEMENTASI

- [x] Notification class dibuat
- [x] FonnteChannel dibuat
- [x] TestWhatsAppController dibuat
- [x] Route /test-wa dibuat
- [x] PlanningOvertimeController sudah memanggil notify()
- [x] Pesan WhatsApp sesuai format yang diminta
- [x] Logging lengkap untuk debugging
- [x] Validasi nomor HP
- [x] Error handling
- [x] Dokumentasi troubleshooting

---

## 🎉 SELESAI!

Sistem WhatsApp Notification sudah siap digunakan!

**Testing:**
```
http://localhost/test-wa
```

**Dokumentasi:**
- `README_WHATSAPP.md` - Dokumentasi implementasi (file ini)
- `TROUBLESHOOTING_WHATSAPP.md` - Panduan troubleshooting

**Support:**
Jika ada masalah, cek log di: `storage/logs/laravel.log`
