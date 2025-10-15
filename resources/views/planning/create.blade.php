@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Buat Planning Lembur</h2>
    <a href="{{ route('planning.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

{{-- ✅ Info Badge untuk Admin --}}
@if(isset($isAdmin) && $isAdmin)
<div class="alert alert-success mb-3">
    <i class="fas fa-crown"></i>
    <strong>Mode Administrator:</strong> Anda dapat membuat planning untuk semua departemen dan memilih mulai approval dari level mana.
</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('planning.store') }}" id="planningForm">
            @csrf
            
            {{-- SECTION 1: DEPARTMENT & APPROVAL LEVEL --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="department_id" class="form-label">
                        Departemen <span class="text-danger">*</span>
                        @if(isset($isAdmin) && $isAdmin)
                            <span class="badge bg-info ms-2">Semua Departemen</span>
                        @endif
                    </label>
                    <select class="form-select @error('department_id') is-invalid @enderror" 
                            name="department_id" 
                            id="department_id" 
                            required>
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
                    @if(!isset($isAdmin) || !$isAdmin)
                        <small class="text-muted">Anda hanya dapat membuat planning untuk departemen Anda</small>
                    @endif
                </div>

                {{-- ✅ DROPDOWN APPROVAL LEVEL (KHUSUS ADMIN) --}}
                @if(isset($isAdmin) && $isAdmin)
                <div class="col-md-6">
                    <label for="start_approval_level_id" class="form-label">
                        Mulai Approval Dari <span class="text-danger">*</span>
                        <i class="fas fa-info-circle text-info" 
                           data-bs-toggle="tooltip" 
                           title="Pilih dari level mana approval dimulai. Approval akan berlanjut sampai akhir flow sesuai department."></i>
                    </label>
                    <select class="form-select @error('start_approval_level_id') is-invalid @enderror" 
                            name="start_approval_level_id" 
                            id="start_approval_level_id" 
                            required>
                        <option value="">Pilih departemen dulu</option>
                    </select>
                    @error('start_approval_level_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted" id="approval-flow-info"></small>
                </div>
                @else
                {{-- NON-ADMIN: Tanggal di sebelah department --}}
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
                @endif
            </div>

            {{-- ADMIN: Tanggal di baris baru --}}
            @if(isset($isAdmin) && $isAdmin)
            <div class="row mb-4">
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
            @endif

            {{-- SECTION 2: KUOTA & JAM --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="max_employees" class="form-label">
                        Kuota Maksimal Karyawan <span class="text-danger">*</span>
                    </label>
                    <input type="number" 
                           class="form-control @error('max_employees') is-invalid @enderror" 
                           name="max_employees" 
                           id="max_employees"
                           value="{{ old('max_employees') }}" 
                           min="1"
                           max="100"
                           placeholder="Contoh: 10"
                           required>
                    @error('max_employees')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Maksimal karyawan yang bisa lembur (1-100)</small>
                </div>

                <div class="col-md-4">
                    <label for="planned_start_time" class="form-label">
                        Jam Mulai <span class="text-danger">*</span>
                    </label>
                    <input type="time" 
                           class="form-control @error('planned_start_time') is-invalid @enderror" 
                           name="planned_start_time" 
                           id="planned_start_time"
                           value="{{ old('planned_start_time') }}" 
                           required>
                    @error('planned_start_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Jam mulai lembur</small>
                </div>

                <div class="col-md-4">
                    <label for="planned_end_time" class="form-label">
                        Jam Selesai <span class="text-danger">*</span>
                    </label>
                    <input type="time" 
                           class="form-control @error('planned_end_time') is-invalid @enderror" 
                           name="planned_end_time" 
                           id="planned_end_time"
                           value="{{ old('planned_end_time') }}" 
                           required>
                    @error('planned_end_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Jam selesai lembur</small>
                </div>
            </div>

            {{-- SECTION 3: DESKRIPSI PEKERJAAN --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="work_description" class="form-label">
                        Deskripsi Pekerjaan <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control @error('work_description') is-invalid @enderror" 
                              name="work_description" 
                              id="work_description"
                              rows="4" 
                              maxlength="1000"
                              placeholder="Jelaskan secara detail pekerjaan apa yang akan dikerjakan saat lembur..."
                              required>{{ old('work_description') }}</textarea>
                    @error('work_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Jelaskan pekerjaan apa yang akan dikerjakan saat lembur</small>
                </div>
            </div>

            {{-- SECTION 4: ALASAN/JUSTIFIKASI --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="reason" class="form-label">
                        Alasan/Justifikasi <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control @error('reason') is-invalid @enderror" 
                              name="reason" 
                              id="reason"
                              rows="4" 
                              maxlength="1000"
                              placeholder="Jelaskan mengapa perlu planning lembur ini, urgensi, dan dampak jika tidak dilakukan..."
                              required>{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Jelaskan mengapa perlu planning lembur ini</small>
                </div>
            </div>

            {{-- SECTION 5: INFO BOX --}}
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Informasi:</strong>
                <ul class="mb-0 mt-2">
                    @if(isset($isAdmin) && $isAdmin)
                        <li><strong>Sebagai Administrator:</strong> Anda dapat memilih mulai approval dari level mana. Approval akan berlanjut sesuai flow hingga selesai.</li>
                        <li>Contoh: Jika pilih "Section Head", maka flow approval: Section Head → Sub Dept Head → Dept Head</li>
                    @else
                        <li>Planning akan masuk ke flow approval sesuai dengan level jabatan Anda</li>
                        <li>Approval akan berlanjut ke level di atas Anda sesuai struktur organisasi</li>
                    @endif
                    <li>Setelah diapprove semua, planning dapat digunakan untuk pengajuan lembur pada tanggal tersebut</li>
                    <li>Reminder akan dikirim H-7 sebelum tanggal lembur kepada Department Head</li>
                    <li>Planning akan expired otomatis H+1 setelah tanggal lembur jika kuota belum habis</li>
                    <li>Status planning dapat dipantau di halaman <strong>Planning Overtime</strong></li>
                </ul>
            </div>
            
            {{-- SECTION 6: BUTTONS --}}
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('planning.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Planning
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    @if(isset($isAdmin) && $isAdmin)
    // ✅ Data approval levels per department dari PHP
    const approvalLevelsByDept = @json($approvalLevelsByDept ?? []);
    
    console.log('Approval Levels Data:', approvalLevelsByDept);
    
    // ✅ Ketika department dipilih, update dropdown approval level
    $('#department_id').on('change', function() {
        const deptId = $(this).val();
        const $approvalSelect = $('#start_approval_level_id');
        const $infoText = $('#approval-flow-info');
        
        $approvalSelect.empty();
        $infoText.text('');
        
        if (deptId && approvalLevelsByDept[deptId]) {
            const levels = approvalLevelsByDept[deptId];
            
            console.log('Levels for dept', deptId, ':', levels);
            
            if (levels.length === 0) {
                $approvalSelect.append('<option value="">Tidak ada flow approval untuk dept ini</option>');
                $infoText.html('<i class="fas fa-exclamation-triangle text-warning"></i> Department ini belum ada flow approval planning!')
                         .addClass('text-warning');
            } else {
                $approvalSelect.append('<option value="">Pilih mulai dari level mana</option>');
                
                levels.forEach((level, index) => {
                    $approvalSelect.append(`<option value="${level.job_level_id}">${level.level_name}</option>`);
                });
                
                // Info text
                const firstLevel = levels[0].level_name;
                const lastLevel = levels[levels.length - 1].level_name;
                
                if (levels.length === 1) {
                    $infoText.html(`<i class="fas fa-route text-info"></i> Flow hanya ada: <strong>${firstLevel}</strong>`)
                             .removeClass('text-warning text-danger')
                             .addClass('text-info');
                } else {
                    $infoText.html(`<i class="fas fa-route text-info"></i> Flow lengkap: <strong>${firstLevel}</strong> → ... → <strong>${lastLevel}</strong>`)
                             .removeClass('text-warning text-danger')
                             .addClass('text-info');
                }
            }
        } else {
            $approvalSelect.append('<option value="">Pilih departemen dulu</option>');
        }
    });

    // ✅ Ketika approval level dipilih, tampilkan preview flow
    $('#start_approval_level_id').on('change', function() {
        const deptId = $('#department_id').val();
        const selectedLevelId = $(this).val();
        const $infoText = $('#approval-flow-info');
        
        if (deptId && selectedLevelId && approvalLevelsByDept[deptId]) {
            const levels = approvalLevelsByDept[deptId];
            const selectedLevel = levels.find(l => l.job_level_id == selectedLevelId);
            
            if (selectedLevel) {
                // Ambil level dari selected sampai akhir
                const flowFromSelected = levels.filter(l => l.step_order >= selectedLevel.step_order);
                const flowNames = flowFromSelected.map(l => l.level_name).join(' → ');
                
                $infoText.html(`<i class="fas fa-check-circle text-success"></i> Approval Flow: <strong>${flowNames}</strong>`)
                         .removeClass('text-danger text-info text-warning')
                         .addClass('text-success');
                         
                console.log('Selected approval flow:', flowNames);
            }
        }
    });
    
    // Trigger change kalau ada old value (untuk form validation error)
    @if(old('department_id'))
        $('#department_id').trigger('change');
        
        @if(old('start_approval_level_id'))
            setTimeout(function() {
                $('#start_approval_level_id').val('{{ old("start_approval_level_id") }}').trigger('change');
            }, 100);
        @endif
    @endif
    @endif

    // ✅ Validation jam (end time harus lebih besar dari start time)
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

    // ✅ Character counter untuk textarea
    $('#work_description, #reason').on('input', function() {
        const maxLength = $(this).attr('maxlength');
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;
        
        let counterElement = $(this).siblings('.char-counter');
        if (counterElement.length === 0) {
            counterElement = $('<small class="text-muted char-counter d-block mt-1"></small>');
            $(this).after(counterElement);
        }
        
        if (remaining < 100) {
            counterElement.addClass('text-warning').removeClass('text-muted');
        } else {
            counterElement.addClass('text-muted').removeClass('text-warning');
        }
        
        counterElement.text(`${remaining} karakter tersisa`);
    });

    // ✅ Form validation sebelum submit
    $('#planningForm').on('submit', function(e) {
        const department = $('#department_id').val();
        const date = $('#planned_date').val();
        const maxEmployees = $('#max_employees').val();
        const startTime = $('#planned_start_time').val();
        const endTime = $('#planned_end_time').val();
        const workDesc = $('#work_description').val().trim();
        const reason = $('#reason').val().trim();
        
        @if(isset($isAdmin) && $isAdmin)
        const approvalLevel = $('#start_approval_level_id').val();
        
        if (!approvalLevel) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: 'Silakan pilih mulai approval dari level mana',
                confirmButtonText: 'OK'
            });
            return false;
        }
        @endif
        
        if (!department || !date || !maxEmployees || !startTime || !endTime || !workDesc || !reason) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: 'Mohon lengkapi semua field yang bertanda * (wajib diisi)',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        if (endTime <= startTime) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Jam Tidak Valid',
                text: 'Jam selesai harus lebih besar dari jam mulai',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Disable button to prevent double submit
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
    });

    // ✅ Initialize tooltips (Bootstrap 5)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // ✅ Trigger character counter on page load
    $('#work_description, #reason').trigger('input');
});
</script>
@endpush

@push('styles')
<style>
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .alert-info {
        background-color: #e7f3ff;
        border-color: #b3d9ff;
        color: #004085;
    }
    
    .alert-info ul {
        padding-left: 1.5rem;
    }
    
    .alert-info li {
        margin-bottom: 0.5rem;
    }
    
    #approval-flow-info {
        display: block;
        margin-top: 0.5rem;
        font-size: 0.9rem;
    }
    
    .char-counter {
        font-size: 0.85rem;
    }
    
    textarea.form-control {
        resize: vertical;
    }
</style>
@endpush
@endsection