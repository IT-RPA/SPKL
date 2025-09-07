@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Detail Pengajuan Lembur - {{ $overtime->request_number }}</h2>
    <div>
        {{-- ✅ PERBAIKAN: Tambahkan tombol edit jam jika user memiliki wewenang --}}
        @if(isset($canEditTime) && $canEditTime)
            <button class="btn btn-warning btn-sm" onclick="toggleEditMode()">
                <i class="fas fa-edit"></i> Edit Jam
            </button>
        @endif

        @if(isset($canInputPercentage) && $canInputPercentage && $overtime->details()->where('overtime_type', 'qualitative')->exists())
    @php
        $hasPercentageReady = $overtime->details()
            ->where('overtime_type', 'qualitative')
            ->get()
            ->filter(function($detail) {
                try {
                    return $detail->canInputPercentageNow();
                } catch (\Exception $e) {
                    return false;
                }
            })->count() > 0;
    @endphp
    
    @if($hasPercentageReady)
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#updatePercentageModal">
            <i class="fas fa-percentage"></i> Input Persentase
        </button>
    @endif
@endif
        <a href="{{ route('overtime.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>

{{-- ✅ PERBAIKAN: Alert untuk status 'act' --}}
@if($overtime->status == 'act')
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Status: Siap Input Qty Actual</strong> - Semua approval telah selesai. Silakan input qty actual untuk menyelesaikan lembur.
    </div>
@endif

{{-- Header Information Card --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Informasi Pengajuan</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>No. SPK:</strong><br>
                <span class="text-primary">{{ $overtime->request_number }}</span>
            </div>
            <div class="col-md-3">
                <strong>Tanggal:</strong><br>
                {{ $overtime->date->format('d/m/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Departemen:</strong><br>
                {{ $overtime->department->name }}
            </div>
            <div class="col-md-3">
                <strong>Tingkatan:</strong><br>
                <span class="badge bg-info">{{ $overtime->requesterEmployee->jobLevel->name ?? $overtime->requester_level }}</span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <strong>Pemohon:</strong><br>
                {{ $overtime->requesterEmployee->name ?? $overtime->requester->name }}
            </div>
            <div class="col-md-3">
                <strong>Status:</strong><br>
                <span class="status-badge status-{{ $overtime->status_color }}">
                    {{ $overtime->status_display ?? ucfirst($overtime->status) }}
                </span>
            </div>
            <div class="col-md-3">
                <strong>Dibuat:</strong><br>
                {{ $overtime->created_at->format('d/m/Y H:i') }}
            </div>
            <div class="col-md-3">
                <strong>Diperbarui:</strong><br>
                {{ $overtime->updated_at->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</div>

{{-- Detail Lembur Card --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Detail Karyawan Lembur</h5>
        {{-- ✅ PERBAIKAN: Button update actual hanya muncul jika status 'act' --}}
        @if(isset($canInputActual) && $canInputActual && $overtime->details()->whereNotNull('qty_plan')->whereNull('qty_actual')->count() > 0)
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#updateActualModal">
                <i class="fas fa-edit"></i> Update Qty Actual
            </button>
        @endif
    </div>
    <div class="card-body">
        {{-- ✅ PERBAIKAN: Form edit jam (hidden by default) --}}
        <form id="timeForm" method="POST" action="{{ route('overtime.update-time', $overtime) }}" style="display: none;">
            @csrf
            @method('PUT')
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Mode Edit Jam:</strong> Anda sedang mengedit jam lembur. Pastikan perubahan sudah benar sebelum menyimpan.
            </div>
            
            @foreach($overtime->details as $index => $detail)
                <div class="border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Karyawan:</strong><br>
                            {{ $detail->employee->name }} - {{ $detail->employee->employee_id }}
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" 
                                   name="details[{{ $detail->id }}][start_time]" 
                                   value="{{ $detail->start_time }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" class="form-control" 
                                   name="details[{{ $detail->id }}][end_time]" 
                                   value="{{ $detail->end_time }}" required>
                        </div>
                    </div>
                </div>
            @endforeach
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan Jam
                </button>
                <button type="button" class="btn btn-secondary" onclick="toggleEditMode()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>

        {{-- Display Mode (Default) --}}
        <div id="displayMode">
            @foreach($overtime->details as $index => $detail)
                <div class="border rounded p-3 mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Karyawan:</strong><br>
                            {{ $detail->employee->name }} - {{ $detail->employee->employee_id }}
                        </div>

                        <div class="col-md-3">
    <strong>Tipe:</strong><br>
    <span class="badge bg-{{ $detail->overtime_type == 'quantitative' ? 'primary' : 'info' }}">
        {{ ucfirst($detail->overtime_type) }}
    </span>
</div>

                        <div class="col-md-3">
                            <strong>Jam Mulai:</strong><br>
                            {{ $detail->start_time }}
                        </div>
                        <div class="col-md-3">
                            <strong>Jam Selesai:</strong><br>
                            {{ $detail->end_time }}
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Prioritas Pekerjaan:</strong><br>
                            <p class="mb-0">{{ $detail->work_priority }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Proses:</strong><br>
                            <p class="mb-0">{{ $detail->work_process }}</p>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        @if($detail->overtime_type == 'quantitative')
    <div class="col-md-3">
        <strong>Qty Plan:</strong><br>
        {{ $detail->qty_plan ?? '-' }}
    </div>
    <div class="col-md-3">
        <strong>Qty Actual:</strong><br>
        @if($detail->qty_plan)
            @if(isset($canInputActual) && $canInputActual)
                <span class="badge {{ $detail->qty_actual ? 'bg-success' : 'bg-info' }}">
                    {{ $detail->qty_actual ?? 'Siap diisi' }}
                </span>
            @else
                <span class="badge {{ $detail->qty_actual ? 'bg-success' : 'bg-warning' }}">
                    {{ $detail->qty_actual ?? 'Menunggu approval' }}
                </span>
            @endif
        @else
            -
        @endif
    </div>
@else
    <div class="col-md-3">
        <strong>Persentase Realisasi:</strong><br>
        @if($detail->percentage_realization !== null)
            <span class="badge bg-success">{{ $detail->percentage_realization }}%</span>
        @elseif($overtime->canInputPercentage(Auth::id()) && $detail->canInputPercentageNow())
            <span class="badge bg-info">Siap diisi</span>
        @else
            <span class="badge bg-warning">Menunggu</span>
        @endif
    </div>
    <div class="col-md-3">
        <strong>Status:</strong><br>
        <span class="badge bg-info">Kualitatif</span>
    </div>
@endif
                        </div>
                        <div class="col-md-6">
                            <strong>Keterangan:</strong><br>
                            {{ $detail->notes ?? '-' }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Approval Status Card --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Status Persetujuan</h5>
    </div>
    <div class="card-body">
        @if($overtime->approvals->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Step</th>
                            <th>Level</th>
                            <th>Approver</th>
                            <th>Status</th>
                            <th>Tanggal Approval</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overtime->approvals->sortBy('step_order') as $approval)
                        <tr>
                            <td>{{ $approval->step_name }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $approval->approver_level }}</span>
                            </td>
                            <td>{{ $approval->approverEmployee->name ?? 'Belum ditentukan' }}</td>
                            <td>
                                @switch($approval->status)
                                    @case('pending')
                                        <span class="badge bg-warning">Menunggu</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-success">Disetujui</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">Ditolak</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-secondary">Dibatalkan</span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark">{{ ucfirst($approval->status) }}</span>
                                @endswitch
                            </td>
                            <td>
                                {{ $approval->approved_at ? $approval->approved_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td>{{ $approval->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Tidak ada data persetujuan.</p>
        @endif
    </div>
</div>

{{-- Modal Input Persentase --}}
@if(isset($canInputPercentage) && $canInputPercentage && $overtime->details()->where('overtime_type', 'qualitative')->exists())
<div class="modal fade" id="updatePercentageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-percentage"></i> Input Persentase Realisasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('overtime.update-percentage', $overtime) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Silakan isi persentase realisasi sesuai dengan hasil kerja lembur kualitatif yang telah dilaksanakan.
                    </div>
                    
                    @foreach($overtime->details->where('overtime_type', 'qualitative') as $detail)
                    @if($detail->canInputPercentageNow())
                    <div class="border rounded p-3 mb-3">
                        <h6>{{ $detail->employee->name }} - {{ $detail->employee->employee_id }}</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Jam Lembur</label>
                                <div class="form-control-plaintext">{{ $detail->start_time }} - {{ $detail->end_time }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Persentase Realisasi (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" 
                                           name="details[{{ $detail->id }}][percentage_realization]" 
                                           value="{{ $detail->percentage_realization }}" 
                                           min="0" max="100" step="0.01"
                                           placeholder="0-100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe</label>
                                <div class="form-control-plaintext">
                                    <span class="badge bg-info">Kualitatif</span>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <label class="form-label">Prioritas Pekerjaan</label>
                                <p class="form-control-plaintext small">{{ $detail->work_priority }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Proses</label>
                                <p class="form-control-plaintext small">{{ $detail->work_process }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Persentase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif


{{-- ✅ PERBAIKAN: Modal Update Actual hanya untuk status 'act' --}}
@if(isset($canInputActual) && $canInputActual && $overtime->details()->whereNotNull('qty_plan')->whereNull('qty_actual')->count() > 0)
<div class="modal fade" id="updateActualModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> Update Qty Actual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('overtime.update-actual', $overtime) }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Silakan isi qty actual sesuai dengan hasil kerja lembur yang telah dilaksanakan.
                    </div>
                    
                    @foreach($overtime->details->where('qty_plan', '!=', null) as $detail)
                    <div class="border rounded p-3 mb-3">
                        <h6>{{ $detail->employee->name }} - {{ $detail->employee->employee_id }}</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Qty Plan</label>
                                <input type="number" class="form-control" value="{{ $detail->qty_plan }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Qty Actual <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" 
                                       name="details[{{ $detail->id }}][qty_actual]" 
                                       value="{{ $detail->qty_actual }}" 
                                       min="0"
                                       placeholder="Masukkan qty actual" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Proses</label>
                                <p class="form-control-plaintext">{{ $detail->work_process }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Qty Actual
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@push('scripts')
<script>
function toggleEditMode() {
    const timeForm = document.getElementById('timeForm');
    const displayMode = document.getElementById('displayMode');
    
    if (timeForm.style.display === 'none') {
        timeForm.style.display = 'block';
        displayMode.style.display = 'none';
    } else {
        timeForm.style.display = 'none';
        displayMode.style.display = 'block';
    }
}

// SweetAlert untuk konfirmasi dan notifikasi
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '{{ session('success') }}',
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            confirmButtonText: 'OK'
        });
    @endif

    // ✅ TAMBAHAN: Handle form submission dengan AJAX
    const timeForm = document.getElementById('timeForm');
    if (timeForm) {
        timeForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent form submit biasa
            
            const formData = new FormData(this);
            
            // Tampilkan loading
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Sedang mengupdate jam lembur',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload halaman untuk menampilkan data terbaru
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message || 'Terjadi kesalahan saat mengupdate jam',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan jaringan',
                    confirmButtonText: 'OK'
                });
            });
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@push('styles')
<style>
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-red {
    background-color: #dc3545;
    color: white;
}

.status-yellow {
    background-color: #ffc107;
    color: black;
}

.status-green {
    background-color: #198754;
    color: white;
}

.status-act {
    background-color: #0dcaf0;
    color: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.timeline-item {
    border-left: 3px solid #dee2e6;
    padding-left: 1rem;
    margin-bottom: 1rem;
}
</style>
@endpush
@endsection