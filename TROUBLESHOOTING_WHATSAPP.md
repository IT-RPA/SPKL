# 🔧 TROUBLESHOOTING GUIDE - WhatsApp Notification Fonnte

## 📋 Checklist Jika WA Tidak Masuk

### 1. CEK TOKEN FONNTE
```bash
# Buka file .env
FONNTE_TOKEN=maQPdVQ8JqJcAz6Ufa97

# Pastikan tidak ada spasi sebelum/sesudah token
# Restart server setelah ubah .env
```

### 2. CEK NOMOR HP DI DATABASE
```sql
-- Cek format nomor HP user
SELECT id, name, phone FROM users WHERE phone IS NOT NULL;

-- Format yang BENAR: 628xxxxxxxxxx
-- Format yang SALAH: 
--   - 08xxxxxxxxxx (harus 628xxx)
--   - +628xxxxxxxxxx (tanpa +)
--   - 62 8xxxxxxxxxx (tanpa spasi)
```

### 3. CEK LOG LARAVEL
```bash
# Lokasi file log
storage/logs/laravel.log

# Cari baris ini (urutan):
[timestamp] local.INFO: FonnteChannel: Preparing to send WA
[timestamp] local.INFO: Fonnte API Response
[timestamp] local.INFO: FonnteChannel: Message sent successfully

# Jika ada WARNING:
[timestamp] local.WARNING: FonnteChannel: No phone number for user
[timestamp] local.WARNING: FonnteChannel: Invalid phone format

# Jika ada ERROR:
[timestamp] local.ERROR: FonnteChannel: Failed to send message
[timestamp] local.ERROR: FonnteChannel: Exception occurred
```

### 4. TEST MANUAL DENGAN ROUTE
```bash
# Akses route test
http://localhost/test-wa

# Atau dengan nomor spesifik
http://localhost/test-wa?phone=628123456789

# Lihat response di browser
# Cek WhatsApp
# Cek log file
```

### 5. CEK RESPONSE FONNTE API
```json
// Response SUKSES dari Fonnte:
{
  "status": true,
  "message": "Message sent successfully"
}

// Response GAGAL:
{
  "status": false,
  "reason": "Invalid token" // atau alasan lain
}
```

---

## 🐛 ERROR UMUM DAN SOLUSI

### Error 1: "No phone number for user"
**Penyebab:** User tidak punya nomor HP di database
**Solusi:**
1. Login ke sistem
2. Buka menu Users
3. Edit user yang akan menerima notifikasi
4. Tambahkan nomor HP format: `628xxxxxxxxxx`
5. Save

### Error 2: "Invalid phone format"
**Penyebab:** Format nomor HP salah
**Solusi:**
- ✅ BENAR: `628123456789` (12-15 digit)
- ❌ SALAH: `08123456789` (harus 628xxx)
- ❌ SALAH: `+628123456789` (tanpa +)
- ❌ SALAH: `62 8123456789` (tanpa spasi)

### Error 3: "Invalid token"
**Penyebab:** Token Fonnte salah atau expired
**Solusi:**
1. Cek file `.env`
2. Pastikan: `FONNTE_TOKEN=maQPdVQ8JqJcAz6Ufa97`
3. Restart server: `php artisan serve` (atau restart Laragon)
4. Test lagi

### Error 4: "Notification doesn't have toWhatsApp method"
**Penyebab:** Notification class tidak punya method `toWhatsApp()`
**Solusi:**
- File sudah diperbaiki: `app/Notifications/PlanningApprovalNotification.php`
- Pastikan method `toWhatsApp()` ada

### Error 5: WA masuk tapi pesan kosong
**Penyebab:** Message tidak ter-format dengan benar
**Solusi:**
- Cek file: `app/Notifications/PlanningApprovalNotification.php`
- Pastikan return array punya key `message`

---

## 🔍 CARA DEBUG STEP BY STEP

### Step 1: Test Route Dulu
```bash
1. Akses: http://localhost/test-wa
2. Lihat response di browser
3. Jika error, baca pesan error
4. Jika sukses, lanjut step 2
```

### Step 2: Cek Log File
```bash
1. Buka: storage/logs/laravel.log
2. Scroll ke bawah (log terbaru)
3. Cari: "FonnteChannel: Preparing to send WA"
4. Lihat response dari Fonnte API
5. Jika status 200 = sukses
6. Jika status lain = ada masalah
```

### Step 3: Cek WhatsApp
```bash
1. Buka WhatsApp di HP
2. Cek pesan masuk dari Fonnte
3. Jika tidak ada, tunggu 1-2 menit
4. Jika masih tidak ada, cek log lagi
```

### Step 4: Test Real Approval
```bash
1. Login sebagai Department Head
2. Buat Planning Lembur baru
3. Submit planning
4. Cek log: apakah notifikasi terkirim ke approver pertama?
5. Login sebagai approver pertama
6. Approve planning
7. Cek log: apakah notifikasi terkirim ke approver kedua?
```

---

## 📱 VALIDASI NOMOR HP

### Format yang DITERIMA:
```
628123456789   ✅ (12 digit)
6281234567890  ✅ (13 digit)
62812345678901 ✅ (14 digit)
```

### Format yang DITOLAK:
```
08123456789    ❌ (harus 628xxx)
+628123456789  ❌ (tanpa +)
62 8123456789  ❌ (tanpa spasi)
628            ❌ (terlalu pendek)
```

---

## 🔄 ALUR NOTIFIKASI LENGKAP

### Saat Planning Dibuat:
```
1. User (Dept Head) buat planning
2. Planning masuk ke approval step 1
3. Sistem cari approver step 1 (Section Head)
4. Sistem cari User berdasarkan employee_id
5. Sistem cek: apakah user punya nomor HP?
6. Jika YA: kirim WA via Fonnte
7. Jika TIDAK: skip (log warning)
```

### Saat Approval Level 1:
```
1. Section Head approve
2. Planning naik ke approval step 2
3. Sistem cari approver step 2 (Asst. Dept Head)
4. Sistem cari User berdasarkan employee_id
5. Sistem cek: apakah user punya nomor HP?
6. Jika YA: kirim WA via Fonnte
7. Jika TIDAK: skip (log warning)
```

### Dan seterusnya sampai level terakhir...

---

## 📊 MONITORING LOG

### Log yang NORMAL (Sukses):
```
[2026-02-02 13:41:00] local.INFO: FonnteChannel: Preparing to send WA {"target":"628123456789","user_id":5,"user_name":"John Doe"}
[2026-02-02 13:41:01] local.INFO: Fonnte API Response {"target":"628123456789","message":"Pemberitahuan: terdapat...","response_body":"{\"status\":true}","status_code":200,"success":true}
[2026-02-02 13:41:01] local.INFO: FonnteChannel: Message sent successfully {"target":"628123456789","user_name":"John Doe"}
```

### Log yang BERMASALAH:
```
[2026-02-02 13:41:00] local.WARNING: FonnteChannel: No phone number for user {"user_id":5,"user_name":"John Doe"}
// SOLUSI: Tambahkan nomor HP ke user

[2026-02-02 13:41:00] local.WARNING: FonnteChannel: Invalid phone format {"user_id":5,"phone":"08123456789"}
// SOLUSI: Ubah format jadi 628123456789

[2026-02-02 13:41:01] local.ERROR: FonnteChannel: Failed to send message {"status":401,"body":"Invalid token"}
// SOLUSI: Cek token di .env
```

---

## 🎯 QUICK FIX COMMANDS

### Clear Cache Laravel:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Restart Server:
```bash
# Jika pakai php artisan serve
Ctrl+C (stop)
php artisan serve (start lagi)

# Jika pakai Laragon
Stop All → Start All
```

### Cek Log Real-time:
```bash
# Windows (PowerShell)
Get-Content storage/logs/laravel.log -Wait -Tail 50

# Linux/Mac
tail -f storage/logs/laravel.log
```

---

## ✅ CHECKLIST AKHIR

Sebelum production, pastikan:
- [ ] Token Fonnte sudah benar di `.env`
- [ ] Semua approver punya nomor HP di database
- [ ] Format nomor HP: `628xxxxxxxxxx`
- [ ] Test route `/test-wa` berhasil
- [ ] Log menunjukkan "Message sent successfully"
- [ ] WhatsApp menerima pesan dengan format yang benar
- [ ] Approval flow mengirim notifikasi ke level berikutnya
- [ ] Tidak ada error di `storage/logs/laravel.log`

---

## 📞 KONTAK SUPPORT

Jika masih bermasalah setelah ikuti semua langkah:
1. Screenshot error di browser
2. Copy log dari `storage/logs/laravel.log`
3. Screenshot nomor HP di database
4. Kirim ke developer

---

**Last Updated:** 2026-02-02
**Version:** 1.0
