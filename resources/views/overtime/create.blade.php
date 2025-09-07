@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Buat Pengajuan Lembur</h2>
    <a href="{{ route('overtime.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('overtime.store') }}" id="overtimeForm">
            @csrf
            
            {{-- Header Section --}}
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
                    <label for="date" class="form-label">Tanggal</label>
                    <input type="date" class="form-control @error('date') is-invalid @enderror" 
                           name="date" value="{{ old('date') }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
               {{-- Ganti bagian dropdown employee_id dengan kode ini --}}
<div class="col-md-6">
    <label for="employee_id" class="form-label">Pengaju (Karyawan)</label>
    <select class="form-select @error('employee_id') is-invalid @enderror" 
            name="employee_id" id="employee_id" required>
        <option value="">Pilih Karyawan Pengaju</option>
        @if(isset($eligibleRequesters))
            @foreach($eligibleRequesters as $requester)
                <option value="{{ $requester->id }}" 
                        {{ (old('employee_id') == $requester->id || (isset($currentEmployeeData) && $currentEmployeeData->id == $requester->id)) ? 'selected' : '' }}>
                    {{ $requester->name }} - {{ $requester->employee_id }} ({{ $requester->jobLevel->name ?? 'N/A' }})
                </option>
            @endforeach
        @endif
    </select>
    @error('employee_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="text-muted">Hanya dapat memilih diri sendiri sebagai pengaju</small>
    
    {{-- Alert untuk flow job validation --}}
    <div id="flowJobAlert" class="alert alert-warning mt-2" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Perhatian!</strong> Karyawan yang dipilih tidak memiliki wewenang untuk mengajukan lembur di departemen ini.
        <br><small>Silakan pilih karyawan lain atau hubungi admin untuk mengatur flow approval.</small>
    </div>
</div>

            {{-- Detail Section --}}
            <h5>Detail Lembur</h5>
            <div id="detailContainer">
                <div class="detail-row border p-3 mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Nama Karyawan</label>
                            <select class="form-select employee-select" name="details[0][employee_id]" required>
                                <option value="">Pilih Karyawan</option>
                            </select>
                        </div>

<div class="col-md-6">
    <label class="form-label">Tipe Lembur</label>
    <select class="form-control overtime-type-select" 
            name="details[0][overtime_type]" 
            onchange="toggleOvertimeType(this, 0)" required>
        <option value="">Pilih Tipe</option>
        <option value="quantitative">Kuantitatif (Dengan Target)</option>
        <option value="qualitative">Kualitatif (Persentase)</option>
    </select>
</div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" name="details[0][start_time]" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" class="form-control" name="details[0][end_time]" required>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Prioritas Pekerjaan</label>
                            <textarea class="form-control" name="details[0][work_priority]" rows="2" required></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Proses</label>
                            <textarea class="form-control" name="details[0][work_process]" rows="2" required></textarea>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
    <div class="col-md-4 qty-section" id="qtySection0">
        <label class="form-label">Qty Plan</label>
        <input type="number" class="form-control qty-plan" name="details[0][qty_plan]" 
               onchange="toggleActual(this)" disabled>
        <small class="text-muted">Hanya untuk tipe kuantitatif</small>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Qty Actual</label>
        <input type="number" class="form-control qty-actual" name="details[0][qty_actual]" disabled>
        <small class="text-muted">Akan diisi setelah lembur selesai</small>
    </div>
    
    <div class="col-md-4">
        <label class="form-label">Keterangan</label>
        <textarea class="form-control" name="details[0][notes]" rows="2"></textarea>
    </div>
</div>

<div class="row mt-2 percentage-info" id="percentageInfo0" style="display: none;">
    <div class="col-md-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Tipe Kualitatif:</strong> Persentase realisasi akan dapat diisi setelah semua approval selesai atau melewati jam lembur oleh atasan minimal 1 tingkat di atas pengaju.
        </div>
    </div>
</div>
                    
                    <div class="mt-2">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeDetail(this)">
                            Hapus Detail
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <button type="button" class="btn btn-success" onclick="addDetail()">
                    Tambah Detail
                </button>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('overtime.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let detailIndex = 0;
let allEmployees = @json($employees);

$(document).ready(function() {
    // Initialize select2 for first employee select
    $('.employee-select').select2({
        placeholder: 'Pilih Karyawan',
        width: '100%'
    });

    $('#employee_id').select2({
        placeholder: 'Pilih Karyawan Pengaju',
        width: '100%'
    });

    // Department change handler
    $('#department_id').on('change', function() {
        const departmentId = $(this).val();
        updateEmployeeOptions(departmentId);
        
        // Reset pengaju dan hide alert
        $('#employee_id').val('').trigger('change');
        $('#flowJobAlert').hide();
        $('#submitBtn').prop('disabled', false);
    });

    // Employee pengaju change handler - validasi flow job
    $('#employee_id').on('change', function() {
        const employeeId = $(this).val();
        const departmentId = $('#department_id').val();
        
        if (employeeId && departmentId) {
            checkFlowJobEligibility(employeeId, departmentId);
        } else {
            $('#flowJobAlert').hide();
            $('#submitBtn').prop('disabled', false);
        }
    });

    // Form submit validation
    $('#overtimeForm').on('submit', function(e) {
        const isSubmitDisabled = $('#submitBtn').prop('disabled');
        if (isSubmitDisabled) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Tidak Dapat Submit',
                text: 'Pengaju yang dipilih tidak memiliki wewenang untuk mengajukan lembur di departemen ini.',
                confirmButtonText: 'OK'
            });
            return false;
        }

        // VALIDASI: Semua tipe lembur harus diisi
        let allTypesFilled = true;
        $('.overtime-type-select').each(function() {
            if (!$(this).val()) {
                allTypesFilled = false;
            }
        });

        if (!allTypesFilled) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Tipe Lembur Belum Lengkap',
                text: 'Silakan pilih tipe lembur untuk semua detail.',
                confirmButtonText: 'OK'
            });
            return false;
        }
    });
});

function checkFlowJobEligibility(employeeId, departmentId) {
    $.ajax({
        url: '/check-overtime-eligibility',
        method: 'POST',
        data: {
            employee_id: employeeId,
            department_id: departmentId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (!response.eligible) {
                $('#flowJobAlert').show();
                $('#submitBtn').prop('disabled', true);
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Pengaju Tidak Valid',
                    html: `
                        <p>Karyawan <strong>${response.employee_name}</strong> dengan jabatan <strong>${response.job_level}</strong> tidak memiliki wewenang untuk mengajukan lembur di departemen ini.</p>
                        <hr>
                        <p><small>Hanya karyawan dengan level jabatan tertentu yang dapat mengajukan lembur sesuai dengan flow approval yang telah ditetapkan.</small></p>
                    `,
                    confirmButtonText: 'Mengerti',
                    footer: '<small>Hubungi admin jika ada pertanyaan tentang flow approval</small>'
                });
            } else {
                $('#flowJobAlert').hide();
                $('#submitBtn').prop('disabled', false);
                
                if (response.show_success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pengaju Valid',
                        text: `${response.employee_name} dapat mengajukan lembur untuk departemen ini.`,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            }
        },
        error: function(xhr) {
            console.error('Error checking flow job eligibility:', xhr);
            $('#flowJobAlert').hide();
            $('#submitBtn').prop('disabled', false);
        }
    });
}

function updateEmployeeOptions(departmentId) {
    const filteredEmployees = allEmployees.filter(emp => emp.department_id == departmentId);
    
    // HANYA UPDATE DETAIL EMPLOYEE DROPDOWNS
    $('.employee-select').each(function() {
        const currentValue = $(this).val();
        $(this).empty().append('<option value="">Pilih Karyawan</option>');
        filteredEmployees.forEach(emp => {
            const selected = currentValue == emp.id ? 'selected' : '';
            $(this).append(`<option value="${emp.id}" ${selected}>${emp.name} - ${emp.employee_id}</option>`);
        });
    });
}

// PERBAIKAN UTAMA: Fungsi addDetail() yang benar
function addDetail() {
    detailIndex++; // Increment global counter
    const container = document.getElementById('detailContainer');
    const firstDetail = container.firstElementChild;
    const newDetail = firstDetail.cloneNode(true);
    
    console.log(`Adding detail with index: ${detailIndex}`); // Debug log
    
    // Update SEMUA name attributes dengan index yang benar
    newDetail.querySelectorAll('[name*="details[0]"]').forEach(input => {
        const oldName = input.name;
        const newName = oldName.replace('[0]', `[${detailIndex}]`);
        input.name = newName;
        input.value = ''; // Reset value
        console.log(`Updated name: ${oldName} -> ${newName}`); // Debug log
    });
    
    // Update SEMUA IDs yang mengandung "0" dengan index baru
    newDetail.querySelectorAll('[id*="0"]').forEach(element => {
        if (element.id) {
            const oldId = element.id;
            const newId = oldId.replace('0', detailIndex);
            element.id = newId;
            console.log(`Updated ID: ${oldId} -> ${newId}`); // Debug log
        }
    });
    
    // KUNCI PERBAIKAN: Update onchange attribute dengan index yang benar
    const overtimeTypeSelect = newDetail.querySelector('.overtime-type-select');
    if (overtimeTypeSelect) {
        overtimeTypeSelect.setAttribute('onchange', `toggleOvertimeType(this, ${detailIndex})`);
        console.log(`Set onchange: toggleOvertimeType(this, ${detailIndex})`); // Debug log
    }
    
    // Update onchange untuk qty plan juga
    const qtyPlanInput = newDetail.querySelector('.qty-plan');
    if (qtyPlanInput) {
        qtyPlanInput.setAttribute('onchange', 'toggleActual(this)');
    }
    
    // Reset ke kondisi awal
    resetDetailToInitialState(newDetail);
    
    // Append ke container
    container.appendChild(newDetail);
    
    // Initialize select2 untuk dropdown baru
    initializeNewDetailSelects(newDetail);

    // Update employee options jika department sudah dipilih
    updateNewDetailEmployeeOptions(newDetail);
    
    console.log(`Detail added successfully with index: ${detailIndex}`);
}

function resetDetailToInitialState(detailElement) {
    // Reset semua select ke pilihan kosong
    detailElement.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });
    
    // Reset semua input text/number/textarea
    detailElement.querySelectorAll('input, textarea').forEach(input => {
        if (input.type !== 'button') {
            input.value = '';
        }
    });
    
    // Reset qty plan ke disabled
    const qtyPlan = detailElement.querySelector('.qty-plan');
    if (qtyPlan) {
        qtyPlan.disabled = true;
        qtyPlan.required = false;
    }
    
    // Reset qty actual ke disabled
    const qtyActual = detailElement.querySelector('.qty-actual');
    if (qtyActual) {
        qtyActual.disabled = true;
    }
    
    // Hide percentage info
    const percentageInfo = detailElement.querySelector('.percentage-info');
    if (percentageInfo) {
        percentageInfo.style.display = 'none';
    }
}

function initializeNewDetailSelects(detailElement) {
    const employeeSelect = detailElement.querySelector('.employee-select');
    if (employeeSelect) {
        $(employeeSelect).select2({
            placeholder: 'Pilih Karyawan',
            width: '100%'
        });
    }
}

function updateNewDetailEmployeeOptions(detailElement) {
    const departmentId = $('#department_id').val();
    if (departmentId) {
        const filteredEmployees = allEmployees.filter(emp => emp.department_id == departmentId);
        const employeeSelect = $(detailElement.querySelector('.employee-select'));
        
        employeeSelect.empty().append('<option value="">Pilih Karyawan</option>');
        filteredEmployees.forEach(emp => {
            employeeSelect.append(`<option value="${emp.id}">${emp.name} - ${emp.employee_id}</option>`);
        });
    }
}

function removeDetail(button) {
    const detailRows = document.querySelectorAll('.detail-row');
    if (detailRows.length > 1) {
        button.closest('.detail-row').remove();
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Tidak Dapat Menghapus',
            text: 'Minimal harus ada satu detail lembur',
            timer: 2000,
            showConfirmButton: false
        });
    }
}

function toggleActual(planInput) {
    const actualInput = planInput.closest('.row').querySelector('.qty-actual');
    if (planInput.value && planInput.value > 0) {
        actualInput.disabled = true;
        actualInput.value = '';
    } else {
        actualInput.disabled = true; // Tetap disabled, akan diisi setelah approve
    }
}

// PERBAIKAN UTAMA: Fungsi toggleOvertimeType dengan logging yang jelas
function toggleOvertimeType(selectElement, index) {
    const overtimeType = selectElement.value;
    
    console.log(`toggleOvertimeType called with index: ${index}, type: ${overtimeType}`);
    
    // Cari elemen berdasarkan index yang diterima
    const qtySection = document.getElementById(`qtySection${index}`);
    const percentageInfo = document.getElementById(`percentageInfo${index}`);
    
    if (!qtySection) {
        console.error(`qtySection${index} not found!`);
        return;
    }
    
    if (!percentageInfo) {
        console.error(`percentageInfo${index} not found!`);
        return;
    }
    
    const qtyPlanInput = qtySection.querySelector('.qty-plan');
    
    if (!qtyPlanInput) {
        console.error(`qty-plan input not found in qtySection${index}`);
        return;
    }
    
    console.log(`Found elements for index ${index}:`, {
        qtySection: qtySection ? 'OK' : 'MISSING',
        percentageInfo: percentageInfo ? 'OK' : 'MISSING',
        qtyPlanInput: qtyPlanInput ? 'OK' : 'MISSING'
    });
    
    // Reset state terlebih dahulu
    qtyPlanInput.disabled = true;
    qtyPlanInput.required = false;
    qtyPlanInput.value = '';
    percentageInfo.style.display = 'none';
    
    if (overtimeType === 'quantitative') {
        console.log(`Setting quantitative for index ${index}`);
        qtyPlanInput.disabled = false;
        qtyPlanInput.required = true;
        percentageInfo.style.display = 'none';
    } else if (overtimeType === 'qualitative') {
        console.log(`Setting qualitative for index ${index}`);
        qtyPlanInput.disabled = true;
        qtyPlanInput.required = false;
        qtyPlanInput.value = '';
        percentageInfo.style.display = 'block';
    }
    
    console.log(`Finished toggle for index ${index}. Final state:`, {
        type: overtimeType,
        planDisabled: qtyPlanInput.disabled,
        planRequired: qtyPlanInput.required,
        percentageVisible: percentageInfo.style.display
    });
}
</script>

{{-- SweetAlert2 CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
@endsection