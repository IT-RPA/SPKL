@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Buat Planning Lembur</h2>
    <a href="{{ route('planning.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('planning.store') }}" id="planningForm">
            @csrf
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="department_id" class="form-label">Departemen</label>
                    <select class="form-select @error('department_id') is-invalid @enderror" 
                            name="department_id" id="department_id" required>
                        <option value="">Pilih Departemen</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6">
                    <label for="planned_date" class="form-label">Tanggal Lembur <span class="text-danger">*</span></label>
                    <input type="date" 
                           class="form-control @error('planned_date') is-invalid @enderror" 
                           name="planned_date" 
                           id="planned_date"
                           value="{{ old('planned_date') }}" 
                           min="{{ date('Y-m-d') }}"
                           required>
                    @error('planned_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Tanggal kapan lembur akan dilaksanakan</small>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="max_employees" class="form-label">Kuota Maksimal Karyawan <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control @error('max_employees') is-invalid @enderror" 
                           name="max_employees" 
                           id="max_employees"
                           value="{{ old('max_employees') }}" 
                           min="1"
                           max="100"
                           required>
                    @error('max_employees')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Maksimal karyawan yang bisa lembur</small>
                </div>

                <div class="col-md-4">
                    <label for="planned_start_time" class="form-label">Jam Mulai <span class="text-danger">*</span></label>
                    <input type="time" 
                           class="form-control @error('planned_start_time') is-invalid @enderror" 
                           name="planned_start_time" 
                           id="planned_start_time"
                           value="{{ old('planned_start_time') }}" 
                           required>
                    @error('planned_start_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="planned_end_time" class="form-label">Jam Selesai <span class="text-danger">*</span></label>
                    <input type="time" 
                           class="form-control @error('planned_end_time') is-invalid @enderror" 
                           name="planned_end_time" 
                           id="planned_end_time"
                           value="{{ old('planned_end_time') }}" 
                           required>
                    @error('planned_end_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="work_description" class="form-label">Deskripsi Pekerjaan <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('work_description') is-invalid @enderror" 
                              name="work_description" 
                              id="work_description"
                              rows="3" 
                              maxlength="1000"
                              required>{{ old('work_description') }}</textarea>
                    @error('work_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Jelaskan pekerjaan apa yang akan dikerjakan saat lembur</small>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="reason" class="form-label">Alasan/Justifikasi <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('reason') is-invalid @enderror" 
                              name="reason" 
                              id="reason"
                              rows="3" 
                              maxlength="1000"
                              required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Jelaskan mengapa perlu planning lembur ini</small>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Informasi:</strong>
                <ul class="mb-0 mt-2">
                    <li>Planning akan masuk ke flow approval sesuai dengan level jabatan Anda</li>
                    <li>Setelah diapprove, planning dapat digunakan untuk pengajuan lembur pada tanggal tersebut</li>
                    <li>Reminder akan dikirim H-7 sebelum tanggal lembur</li>
                    <li>Planning akan expired otomatis H+1 jika kuota belum habis</li>
                </ul>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('planning.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Planning
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Validation jam
    $('#planned_end_time').on('change', function() {
        const startTime = $('#planned_start_time').val();
        const endTime = $(this).val();
        
        if (startTime && endTime && endTime <= startTime) {
            Swal.fire({
                icon: 'error',
                title: 'Jam Tidak Valid',
                text: 'Jam selesai harus lebih besar dari jam mulai',
                confirmButtonText: 'OK'
            });
            $(this).val('');
        }
    });

    // Character counter
    $('#work_description, #reason').on('input', function() {
        const maxLength = $(this).attr('maxlength');
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;
        
        let counterElement = $(this).next('.char-counter');
        if (counterElement.length === 0) {
            counterElement = $('<small class="text-muted char-counter"></small>');
            $(this).after(counterElement);
        }
        
        counterElement.text(`${remaining} karakter tersisa`);
    });
});
</script>
@endpush
@endsection
