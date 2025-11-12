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

            {{-- Hidden fields untuk planning --}}
            <input type="hidden" name="overtime_category" id="overtimeCategory" value="unplanned">
            <input type="hidden" name="planning_id" id="planningId" value="">

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
                        id="overtimeDate"
                        name="date" value="{{ old('date') }}" required>
                    @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- ✅ PLANNING ALERT --}}
            <div id="planningAvailableAlert" class="alert alert-success" style="display:none;">
                <h5><i class="fas fa-calendar-check"></i> Planning Lembur Tersedia!</h5>
                <p>Ditemukan planning lembur untuk tanggal yang Anda pilih:</p>
                <div id="planningDetailsContainer"></div>
                <hr>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="usePlanning()">
                        <i class="fas fa-check"></i> Gunakan Planning Ini
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="skipPlanning()">
                        <i class="fas fa-times"></i> Buat Unplanned
                    </button>
                </div>
            </div>

            {{-- ✅ INFO SETELAH PILIH PLANNING --}}
            <div id="planningSelectedInfo" class="alert alert-info" style="display:none;">
                <h6><i class="fas fa-info-circle"></i> Menggunakan Planning Lembur</h6>
                <div id="selectedPlanningInfo"></div>
                <button type="button" class="btn btn-sm btn-warning mt-2" onclick="changePlanning()">
                    <i class="fas fa-edit"></i> Ubah ke Unplanned
                </button>
            </div>

            <div class="row mb-4">
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

                    <div id="flowJobAlert" class="alert alert-warning mt-2" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Perhatian!</strong> Karyawan yang dipilih tidak memiliki wewenang untuk mengajukan lembur di departemen ini.
                    </div>
                </div>
            </div>

            @if(isset($currentEmployeeData))
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Informasi Hierarki</h6>
                        <p class="mb-1"><strong>Level Anda:</strong> {{ $currentEmployeeData->jobLevel->name ?? 'N/A' }}</p>
                        <p class="mb-0"><small class="text-muted">
                                <i class="fas fa-lightbulb"></i>
                                Anda dapat mengajukan lembur untuk karyawan dengan level yang sama atau di bawah level Anda.
                            </small></p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Detail Section --}}
            <h5>Detail Lembur</h5>
            <div id="detailContainer">
                <div class="detail-row border p-3 mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Nama Karyawan</label>
                            <select class="form-select employee-select" name="details[0][employee_id]" required>
                                <option value="">Pilih Karyawan</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}"
                                    data-level="{{ $employee->jobLevel->name ?? 'N/A' }}"
                                    data-level-order="{{ $employee->jobLevel->level_order ?? 999 }}">
                                    {{ $employee->name }} - {{ $employee->employee_id }} - {{ $employee->plant->name }} ({{ $employee->jobLevel->name ?? 'N/A' }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tipe Lembur</label>
                            <select class="form-select overtime-type-select"
                                name="details[0][overtime_type]"
                                onchange="toggleOvertimeType(this, 0)" required>
                                <option value="">Pilih Tipe</option>
                                <option value="quantitative">Kuantitatif (Dengan Target)</option>
                                <option value="qualitative">Kualitatif (Persentase)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control start-time-input" name="details[0][start_time]" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" class="form-control end-time-input" name="details[0][end_time]" required>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Prioritas Pekerjaan</label>
                            <textarea class="form-control" name="details[0][work_priority]" rows="2" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Proses</label>
                            <select class="form-select process-type-select" name="details[0][process_type_id]" required>
                                <option value="">Pilih Tipe Proses</option>
                                @foreach($processTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 qty-section" id="qtySection0">
                            <label class="form-label">Qty Plan</label>
                            <input type="number" class="form-control qty-plan" name="details[0][qty_plan]" disabled>
                            <small class="text-muted">Hanya untuk tipe kuantitatif</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Qty Actual</label>
                            <input type="number" class="form-control qty-actual" name="details[0][qty_actual]" disabled>
                            <small class="text-muted">Akan diisi setelah lembur selesai</small>
                        </div>
                    </div>

                    <div class="row mt-2 percentage-info" id="percentageInfo0" style="display: none;">
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle"></i>
                                <strong>Tipe Kualitatif:</strong> Persentase realisasi akan dapat diisi setelah semua approval selesai.
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeDetail(this)">
                            <i class="fas fa-trash"></i> Hapus Detail
                        </button>
                    </div>
                </div>
            </div>

            {{-- ✅ QUOTA WARNING --}}
            <div id="quotaWarning" class="alert alert-danger" style="display:none;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Kuota Planning Terlampaui!</strong>
                <p class="mb-0">Jumlah karyawan (<span id="currentEmployeeCount">0</span>) melebihi sisa kuota planning (<span id="remainingQuota">0</span>).
                    Harap kurangi jumlah karyawan atau ubah ke Unplanned.</p>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-success" onclick="addDetail()">
                    <i class="fas fa-plus"></i> Tambah Detail
                </button>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('overtime.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let detailIndex = 0;
    let allEmployees = @json($employees);
    let currentPlannings = [];
    let selectedPlanning = null;

    $(document).ready(function() {
        // Initialize Select2
        $('.employee-select').select2({
            placeholder: 'Pilih Karyawan',
            width: '100%'
        });

        $('#employee_id').select2({
            placeholder: 'Pilih Karyawan Pengaju',
            width: '100%'
        });

        // Event: Check planning saat tanggal berubah
        $('#overtimeDate').on('change', function() {
            const date = $(this).val();
            const departmentId = $('#department_id').val();

            if (date && departmentId) {
                checkAvailablePlanning(date, departmentId);
            }
        });

        $('#department_id').on('change', function() {
            $('#employee_id').val('').trigger('change');
            $('#flowJobAlert').hide();
            $('#submitBtn').prop('disabled', false);
            updateEmployeeOptionsForAll();
            resetPlanningState();
        });

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

        $('#overtimeForm').on('submit', function(e) {
            if ($('#submitBtn').prop('disabled')) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Dapat Submit',
                    text: 'Ada masalah dengan pengajuan Anda.',
                    confirmButtonText: 'OK'
                });
                return false;
            }

            if ($('#overtimeCategory').val() === 'planned' && selectedPlanning) {
                const employeeCount = $('.detail-row').length;
                if (employeeCount > selectedPlanning.remaining_employees) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Kuota Terlampaui',
                        text: `Jumlah karyawan (${employeeCount}) melebihi kuota (${selectedPlanning.remaining_employees})`
                    });
                    return false;
                }
            }
        });
    });

    function addDetail() {
        detailIndex++;
        const container = document.getElementById('detailContainer');
        const firstDetail = container.querySelector('.detail-row');
        const newDetail = firstDetail.cloneNode(true);

        $(newDetail).find('.select2-container').remove();
        $(newDetail).find('.employee-select').removeClass('select2-hidden-accessible');

        newDetail.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/\[0\]/g, `[${detailIndex}]`);
        });

        newDetail.querySelectorAll('[id]').forEach(element => {
            if (element.id) {
                element.id = element.id.replace(/0$/, detailIndex);
            }
        });

        const overtimeTypeSelect = newDetail.querySelector('.overtime-type-select');
        if (overtimeTypeSelect) {
            overtimeTypeSelect.setAttribute('onchange', `toggleOvertimeType(this, ${detailIndex})`);
        }

        newDetail.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });

        newDetail.querySelectorAll('input:not([type="button"]), textarea').forEach(input => {
            if (input.type !== 'time') {
                input.value = '';
            }
        });

        if (selectedPlanning) {
            newDetail.querySelector('.start-time-input').value = selectedPlanning.planned_start_time;
            newDetail.querySelector('.end-time-input').value = selectedPlanning.planned_end_time;
        }

        container.appendChild(newDetail);

        const newEmployeeSelect = newDetail.querySelector('.employee-select');
        const $newSelect = $(newEmployeeSelect);

        $newSelect.empty().append('<option value="">Pilih Karyawan</option>');

        allEmployees.forEach(emp => {
            $newSelect.append(`
            <option value="${emp.id}" 
                    data-level="${emp.job_level ? emp.job_level.name : 'N/A'}" 
                    data-level-order="${emp.job_level ? emp.job_level.level_order : 999}">
                ${emp.name} - ${emp.employee_id} (${emp.job_level ? emp.job_level.name : 'N/A'})
            </option>
        `);
        });

        setTimeout(function() {
            $newSelect.select2({
                placeholder: 'Pilih Karyawan',
                width: '100%'
            });
        }, 100);

        checkQuotaCompliance();
    }

    function removeDetail(button) {
        const detailRows = document.querySelectorAll('.detail-row');
        if (detailRows.length > 1) {
            const rowToRemove = button.closest('.detail-row');
            $(rowToRemove).find('select').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            rowToRemove.remove();
            checkQuotaCompliance();
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

    function checkAvailablePlanning(date, departmentId) {
        $.ajax({
            url: '/api/check-available-planning',
            method: 'GET',
            data: {
                date: date,
                department_id: departmentId
            },
            success: function(response) {
                if (response.has_planning && response.plannings.length > 0) {
                    currentPlannings = response.plannings;
                    displayPlanningAlert(response.plannings);
                } else {
                    resetPlanningState();
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                resetPlanningState();
            }
        });
    }

    function displayPlanningAlert(plannings) {
        let html = '';
        if (plannings.length === 1) {
            const p = plannings[0];
            html = `
            <div class="border rounded p-3 bg-white">
                <p class="mb-1"><strong>No. Planning:</strong> ${p.planning_number}</p>
                <p class="mb-1"><strong>Jam:</strong> ${p.planned_start_time} - ${p.planned_end_time}</p>
                <p class="mb-1"><strong>Sisa Kuota:</strong> <span class="badge bg-success">${p.remaining_employees} orang</span> dari ${p.max_employees} orang</p>
                <p class="mb-0"><strong>Deskripsi:</strong> ${p.work_description}</p>
            </div>
        `;
        }
        $('#planningDetailsContainer').html(html);
        $('#planningAvailableAlert').fadeIn();
    }

    function usePlanning() {
        if (currentPlannings.length > 0) {
            selectedPlanning = currentPlannings[0];
            applyPlanningToForm(selectedPlanning);
        }
    }

    function applyPlanningToForm(planning) {
        $('#overtimeCategory').val('planned');
        $('#planningId').val(planning.id);

        $('.start-time-input').val(planning.planned_start_time);
        $('.end-time-input').val(planning.planned_end_time);

        $('#planningAvailableAlert').fadeOut();

        let infoHtml = `
        <p class="mb-1"><strong>Planning:</strong> ${planning.planning_number}</p>
        <p class="mb-1"><strong>Jam:</strong> ${planning.planned_start_time} - ${planning.planned_end_time}</p>
        <p class="mb-0"><strong>Sisa Kuota:</strong> <span class="badge bg-success">${planning.remaining_employees} orang</span></p>
    `;
        $('#selectedPlanningInfo').html(infoHtml);
        $('#planningSelectedInfo').fadeIn();

        checkQuotaCompliance();

        Swal.fire({
            icon: 'success',
            title: 'Planning Terpilih',
            text: 'Jam lembur diisi dari planning',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }

    function skipPlanning() {
        resetPlanningState();
    }

    function changePlanning() {
        $('#overtimeCategory').val('unplanned');
        $('#planningId').val('');
        selectedPlanning = null;
        $('#planningSelectedInfo').fadeOut();
        $('#quotaWarning').hide();
        $('#submitBtn').prop('disabled', false);
    }

    function resetPlanningState() {
        $('#overtimeCategory').val('unplanned');
        $('#planningId').val('');
        selectedPlanning = null;
        currentPlannings = [];
        $('#planningAvailableAlert').hide();
        $('#planningSelectedInfo').hide();
        $('#quotaWarning').hide();
    }

    function checkQuotaCompliance() {
        if ($('#overtimeCategory').val() !== 'planned' || !selectedPlanning) {
            $('#quotaWarning').hide();
            $('#submitBtn').prop('disabled', false);
            return;
        }

        const employeeCount = $('.detail-row').length;
        const remaining = selectedPlanning.remaining_employees;

        $('#currentEmployeeCount').text(employeeCount);
        $('#remainingQuota').text(remaining);

        if (employeeCount > remaining) {
            $('#quotaWarning').fadeIn();
            $('#submitBtn').prop('disabled', true);
        } else {
            $('#quotaWarning').fadeOut();
            $('#submitBtn').prop('disabled', false);
        }
    }

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
                } else {
                    $('#flowJobAlert').hide();
                    $('#submitBtn').prop('disabled', false);
                }
            }
        });
    }

    function updateEmployeeOptionsForAll() {
        $('.employee-select').each(function() {
            const $select = $(this);
            $select.empty().append('<option value="">Pilih Karyawan</option>');

            allEmployees.forEach(emp => {
                $select.append(`
                <option value="${emp.id}">
                    ${emp.name} - ${emp.employee_id} - ${emp.plant.name}
                </option>
            `);
            });
        });
    }

    function toggleOvertimeType(selectElement, index) {
        const overtimeType = selectElement.value;
        const detailRow = selectElement.closest('.detail-row');
        const qtySection = detailRow.querySelector('.qty-section');
        const percentageInfo = detailRow.querySelector('.percentage-info');
        const qtyPlanInput = qtySection.querySelector('.qty-plan');

        qtyPlanInput.disabled = true;
        qtyPlanInput.required = false;
        qtyPlanInput.value = '';
        percentageInfo.style.display = 'none';

        if (overtimeType === 'quantitative') {
            qtyPlanInput.disabled = false;
            qtyPlanInput.required = true;
        } else if (overtimeType === 'qualitative') {
            percentageInfo.style.display = 'block';
        }
    }
</script>
@endpush