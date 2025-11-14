@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Approval {{$joblevel->name}}</h2>
</div>

{{-- TAMBAHAN: Alert untuk percentage yang perlu diinput --}}
@php
$currentEmployee = \App\Models\Employee::where('email', Auth::user()->email)->first();
$pendingPercentageCount = 0;

if ($currentEmployee) {
$pendingPercentageCount = \App\Models\OvertimeRequest::whereHas('details', function($query) {
$query ->where('overtime_type', 'qualitative')
->where('is_rejected', false)
->whereNull('percentage_realization');
})
->whereHas('approvals', function($query) use ($currentEmployee) {
$query->where('approver_employee_id', $currentEmployee->id)
->where('status', 'approved');
})
->where('status', 'approved')
->count();
}
@endphp

@if($pendingPercentageCount > 0)
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="fas fa-percentage"></i>
    <strong>Perhatian!</strong>
    Ada {{ $pendingPercentageCount }} pengajuan lembur kualitatif yang memerlukan input persentase realisasi dari Anda.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="approvalsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Pengaju</th>
                        <th>Level Pengaju</th>
                        <th>Departemen</th>
                        <th>Tanggal</th>
                        <th>Step</th>
                        <th>Status</th>
                        <th>Persentase</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $approval)
                    <tr>
                        <td>{{ $approval->overtimeRequest->request_number }}</td>
                        <td>{{ $approval->overtimeRequest->requesterEmployee->name ?? $approval->overtimeRequest->requester->name }}</td>
                        <td>{{ $approval->overtimeRequest->requesterEmployee->jobLevel->name ?? $approval->overtimeRequest->requester_level }}</td>
                        <td>{{ $approval->overtimeRequest->department->name }}</td>
                        <td>{{ $approval->overtimeRequest->date->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-info">{{ $approval->step_name }}</span>
                        </td>
                        <td>
                            @if(isset($approval->needs_percentage_input) && $approval->needs_percentage_input)
                            <span class="badge bg-warning">
                                <i class="fas fa-percentage"></i> Perlu Input %
                            </span>
                            @else
                            <span class="badge bg-{{ $approval->status == 'approved' ? 'success' : ($approval->status == 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($approval->status) }}
                            </span>
                            @endif
                            @if($approval->notes)
                            <br><small class="text-muted">{{ $approval->notes }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                            $qualitativeDetails = $approval->overtimeRequest->details->where('overtime_type', 'qualitative') ->where('is_rejected', false);
                            $totalQualitative = $qualitativeDetails->count();
                            $filledPercentage = $qualitativeDetails->whereNotNull('percentage_realization')->count();
                            $needsPercentage = $totalQualitative - $filledPercentage;
                            @endphp

                            @if($totalQualitative > 0)
                            @if($needsPercentage > 0)
                            <span class="badge bg-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ $needsPercentage }} belum diisi
                            </span>
                            @else
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i>
                                Semua terisi ({{ $filledPercentage }})
                            </span>
                            @endif
                            @else
                            <span class="badge bg-secondary">
                                <i class="fas fa-minus"></i>
                                Tidak ada kualitatif
                            </span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">

                                @if(isset($approval->needs_percentage_input) && $approval->needs_percentage_input)
                                <button class="btn btn-sm btn-warning"
                                    onclick="showDetailModal({{ $approval->id }}, '{{ $approval->overtimeRequest->request_number }}')"
                                    title="Input Persentase">
                                    <i class="fas fa-percentage"></i> Input %
                                </button>
                                @else
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick="showDetailModal({{ $approval->id }}, '{{ $approval->overtimeRequest->request_number }}')"
                                    title="Lihat Detail">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                                @endif

                                @if($approval->status == 'pending' && auth()->user()->can('edit-overtime'))
                                <button class="btn btn-sm btn-outline-warning"
                                    onclick="editOvertimeModal({{ $approval->overtimeRequest->id }})"
                                    title="Edit Overtime">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                @endif

                                @if(auth()->user()->can('delete-overtime'))
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete({{ $approval->overtimeRequest->id }}, '{{ $approval->overtimeRequest->request_number }}')"
                                    title="Hapus Overtime">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                @endif
                            </div>

                            @if($approval->status == 'pending')
                            @php
                            $currentEmployee = \App\Models\Employee::where('email', Auth::user()->email)->first();
                            $isCurrentUserApprover = ($approval->approver_employee_id === $currentEmployee->id);
                            $previousPendingExists = \App\Models\OvertimeApproval::where('overtime_request_id', $approval->overtime_request_id)
                            ->where('step_order', '<', $approval->step_order)
                                ->where('status', 'pending')
                                ->exists();
                                $canApproveNow = $isCurrentUserApprover && !$previousPendingExists;
                                @endphp

                                @if($canApproveNow)
                                <br><small class="text-success"><i class="fas fa-clock"></i> <strong>Giliran Anda</strong></small>
                                @elseif($isCurrentUserApprover && $previousPendingExists)
                                <br><small class="text-warning"><i class="fas fa-hourglass-half"></i> Menunggu Approval Sebelumnya</small>
                                @endif
                                @elseif(isset($approval->needs_percentage_input) && $approval->needs_percentage_input)
                                <br><small class="text-warning"><i class="fas fa-percentage"></i> <strong>Perlu Input Persentase</strong></small>
                                @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada data untuk disetujui</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan Lembur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <div id="approvalButtons" style="display: none;">
                    <button type="button" class="btn btn-danger me-2" onclick="showRejectModal()">
                        <i class="fas fa-times"></i> Reject All
                    </button>
                    <button type="button" class="btn btn-success" onclick="approveRequest()">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Pengajuan Lembur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editModalBody">
                <!-- Edit form will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveEditChanges()">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal (Reject All) -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alasan Penolakan Keseluruhan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="Masukkan alasan penolakan..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitReject()">
                    <i class="fas fa-times"></i> Tolak Semua
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Detail Modal (Reject Per-Orang) -->
<div class="modal fade" id="rejectDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-times"></i> Tolak Karyawan Lembur
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Anda akan menolak karyawan: <strong id="rejectDetailEmployeeName"></strong>
                </div>
                <form id="rejectDetailForm">
                    <div class="mb-3">
                        <label for="detailRejectionReason" class="form-label">
                            Alasan Penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control"
                            id="detailRejectionReason"
                            rows="4"
                            required
                            minlength="10"
                            placeholder="Masukkan alasan penolakan minimal 10 karakter..."></textarea>
                        <small class="text-muted">Minimal 10 karakter</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitRejectDetail()">
                    <i class="fas fa-user-times"></i> Tolak Karyawan Ini
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pengajuan lembur dengan nomor: <strong id="deleteRequestNumber"></strong>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="deleteOvertime()" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentApprovalId = null;
    let currentOvertimeId = null;
    let deleteOvertimeId = null;
    let currentDetailId = null; // ✅ UNTUK REJECT PER-ORANG

    $(document).ready(function() {
        $('#approvalsTable').DataTable({
            responsive: true,
            pageLength: 10,
            ordering: true,
            language: {
                "processing": "Sedang memproses...",
                "lengthMenu": "Tampilkan _MENU_ entri",
                "zeroRecords": "Tidak ada data yang sesuai",
                "emptyTable": "Tidak ada data tersedia dalam tabel",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "infoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    });

    function showDetailModal(approvalId, requestNumber) {
        currentApprovalId = approvalId;

        document.getElementById('modalBody').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
        new bootstrap.Modal(document.getElementById('detailModal')).show();

        fetch(`/approvals/detail/${approvalId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                currentOvertimeId = data.overtime_id;
                document.getElementById('modalBody').innerHTML = generateDetailContent(data);

                const hasCurrentPendingApproval = data.has_pending_approval ||
                    data.current_approval_status === 'pending' ||
                    data.status === 'pending' ||
                    (data.approval_status && data.approval_status === 'pending');

                if (hasCurrentPendingApproval) {
                    document.getElementById('approvalButtons').style.display = 'block';
                } else {
                    document.getElementById('approvalButtons').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('modalBody').innerHTML = '<div class="alert alert-danger">Gagal memuat detail: ' + error.message + '</div>';
            });
    }

    function generateDetailContent(data) {
        let approvalHistoryHtml = '';
        if (data.approval_history && data.approval_history.length > 0) {
            approvalHistoryHtml = `
            <hr>
            <h6>Riwayat Approval:</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Step</th>
                            <th>Level</th>
                            <th>Approver</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.approval_history.map(history => `
                            <tr>
                                <td>${history.step_name}</td>
                                <td>${history.level}</td>
                                <td>${history.approver_name}</td>
                                <td><span class="badge bg-${history.status == 'Approved' ? 'success' : (history.status == 'Rejected' ? 'danger' : 'warning')}">${history.status}</span></td>
                                <td>${history.date || 'Belum diproses'}</td>
                                <td>${history.notes || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        }

        let editTimeFormHtml = '';
        const canEditTime = data.can_edit_time === true || data.can_edit_time === 'true' || data.can_edit_time === 1;

        if (canEditTime && data.details && data.details.length > 0) {
            editTimeFormHtml = `
            <hr>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Edit Jam Tersedia:</strong> Anda dapat mengedit jam lembur untuk pengajuan ini.
            </div>
            <h6>Edit Jam Lembur:</h6>
            <form id="editTimeForm">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.details.map(detail => `
                                <tr>
                                    <td>${detail.employee_name}</td>
                                    <td>
                                        <input type="time" class="form-control form-control-sm" 
                                               name="details[${detail.id}][start_time]" 
                                               value="${detail.start_time}" required>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control form-control-sm" 
                                               name="details[${detail.id}][end_time]" 
                                               value="${detail.end_time}" required>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-warning btn-sm" onclick="updateTime()">
                    <i class="fas fa-clock"></i> Update Jam
                </button>
            </form>
        `;
        }

        let percentageFormHtml = '';
        const canInputPercentage = data.can_input_percentage === true || data.can_input_percentage === 'true' || data.can_input_percentage === 1;

        // ✅ PERBAIKAN: Filter hanya qualitative yang TIDAK di-reject
        const hasQualitativeDetails = data.details && data.details.some(detail =>
            detail.overtime_type === 'qualitative' && !detail.is_rejected
        );

        const hasPercentageInputReady = data.details && data.details.some(detail =>
            detail.overtime_type === 'qualitative' &&
            detail.can_input_percentage_now === true &&
            !detail.is_rejected // ✅ TAMBAHAN
        );

        if (canInputPercentage && hasQualitativeDetails && hasPercentageInputReady) {
            // ✅ PERBAIKAN: Filter rejected details
            const qualitativeDetails = data.details.filter(detail =>
                detail.overtime_type === 'qualitative' &&
                detail.can_input_percentage_now === true &&
                !detail.is_rejected // ✅ TAMBAHAN
            );

            if (qualitativeDetails.length > 0) {
                percentageFormHtml = `
                <hr>
                <div class="alert alert-success">
                    <i class="fas fa-percentage"></i>
                    <strong>Input Persentase Tersedia:</strong> Anda dapat mengisi persentase realisasi untuk lembur kualitatif.
                </div>
                <h6>Input Persentase Realisasi:</h6>
                <form id="editPercentageForm">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jam</th>
                                    <th>Prioritas</th>
                                    <th>Proses</th>
                                    <th>Persentase Realisasi (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${qualitativeDetails.map(detail => `
                                    <tr>
                                        <td>${detail.employee_name}</td>
                                        <td>${detail.start_time} - ${detail.end_time}</td>
                                        <td>${detail.work_priority}</td>
                                        <td>${detail.work_process}</td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
                                                name="details[${detail.id}][percentage_realization]" 
                                                value="${detail.percentage_realization || ''}" 
                                                min="0" max="100" step="0.01"
                                                placeholder="0-100%">
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" onclick="updatePercentage()">
                        <i class="fas fa-percentage"></i> Update Persentase
                    </button>
                </form>
            `;
            }
        } else if (canInputPercentage && hasQualitativeDetails && !hasPercentageInputReady) {
            percentageFormHtml = `
            <hr>
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i>
                <strong>Persentase Belum Siap:</strong> Lembur kualitatif belum dapat diisi persentasenya. Tunggu sampai approval selesai atau melewati jam lembur.
            </div>
        `;
        } else if (canInputPercentage && !hasQualitativeDetails) {
            const allRejected = data.details && data.details.every(detail =>
                detail.overtime_type !== 'qualitative' || detail.is_rejected
            );

            percentageFormHtml = `
            <hr>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Info:</strong> ${allRejected ? 'Semua lembur kualitatif telah ditolak.' : 'Tidak ada lembur kualitatif pada pengajuan ini.'}
            </div>
        `;
        }

        // ✅ FITUR BARU: Detail Lembur dengan Tombol Reject Per-Orang
        const canApprove = data.has_pending_approval || data.can_approve;

        return `
        <div class="row">
            <div class="col-md-6">
                <p><strong>No. SPK:</strong> ${data.request_number}</p>
                <p><strong>Pengaju:</strong> ${data.requester_name}</p>
                <p><strong>Level Pengaju:</strong> ${data.requester_level}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Tanggal:</strong> ${data.date}</p>
                <p><strong>Departemen:</strong> ${data.department_name}</p>
            </div>
        </div>
        <hr>
        
        ${canApprove ? `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Mode Approval:</strong> Anda dapat menolak individual karyawan dengan mengklik tombol "Reject" di sebelah kanan setiap detail. 
                Jika tidak ada yang ditolak, klik tombol "Approve" di bawah untuk menyetujui semua.
            </div>
        ` : ''}
        
        <h6>Detail Lembur:</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>ID Karyawan</th>
                        <th>Jam</th>
                        <th>Tipe</th>
                        <th>Prioritas</th>
                        <th>Proses</th>
                        <th>Qty Plan</th>
                        <th>Qty Act</th>
                        <th>Persentase</th>
                        <th>Status</th>
                        ${canApprove ? '<th width="120px">Aksi</th>' : ''}
                    </tr>
                </thead>
                <tbody>
                    ${(data.details && data.details.length > 0) ? 
                        data.details.map(detail => {
                            const isRejected = detail.is_rejected || false;
                            const rejectionBadge = isRejected ? 
                                `<span class="badge bg-danger" title="${detail.rejection_reason || ''}">
                                    <i class="fas fa-times-circle"></i> Ditolak
                                </span>` : 
                                `<span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Aktif
                                </span>`;
                            
                            return `
                                <tr class="${isRejected ? 'table-danger' : ''}" id="detail-row-${detail.id}">
                                    <td>${detail.employee_name}</td>
                                    <td>${detail.employee_id}</td>
                                    <td>${detail.start_time} - ${detail.end_time}</td>
                                    <td>
                                        <span class="badge bg-${detail.overtime_type === 'quantitative' ? 'primary' : 'info'}">
                                            ${detail.overtime_type === 'quantitative' ? 'Kuantitatif' : 'Kualitatif'}
                                        </span>
                                    </td>
                                    <td><small>${detail.work_priority}</small></td>
                                    <td><small>${detail.work_process}</small></td>
                                    <td>${detail.overtime_type === 'quantitative' ? (detail.qty_plan || '-') : '-'}</td>
                                    <td>${detail.overtime_type === 'quantitative' ? (detail.qty_actual || '-') : '-'}</td>
                                    <td>
                                        ${detail.overtime_type === 'qualitative' ? 
                                            (detail.percentage_realization !== null ? 
                                                `<span class="badge bg-success">${detail.percentage_realization}%</span>` : 
                                                (detail.can_input_percentage_now ? 
                                                    '<span class="badge bg-info">Siap diisi</span>' : 
                                                    '<span class="badge bg-warning">Menunggu</span>'
                                                )
                                            ) : '-'
                                        }
                                    </td>
                                    <td id="detail-status-${detail.id}">
                                        ${rejectionBadge}
                                        ${isRejected ? `
                                            <br><small class="text-danger">
                                                <strong>Alasan:</strong> ${detail.rejection_reason || '-'}
                                            </small>
                                            <br><small class="text-muted">
                                                Oleh: ${detail.rejected_by_name || '-'} 
                                                ${detail.rejected_at ? '(' + detail.rejected_at + ')' : ''}
                                            </small>
                                        ` : ''}
                                    </td>
                                    ${canApprove ? `
                                        <td id="detail-action-${detail.id}">
                                            ${!isRejected ? `
                                                <button class="btn btn-danger btn-sm w-100" 
                                                        onclick="showRejectDetailModal(${detail.id}, '${detail.employee_name}')"
                                                        title="Tolak karyawan ini">
                                                    <i class="fas fa-user-times"></i> Reject
                                                </button>
                                            ` : `
                                                <button class="btn btn-success btn-sm w-100" 
                                                        onclick="unrejectDetail(${detail.id})"
                                                        title="Batalkan penolakan">
                                                    <i class="fas fa-undo"></i> Unreject
                                                </button>
                                            `}
                                        </td>
                                    ` : ''}
                                </tr>
                            `;
                        }).join('') :
                        ` < tr > < td colspan = "${canApprove ? '11' : '10'}"
        class = "text-center" > Tidak ada detail lembur < /td></tr > `
                    }
                </tbody>
            </table>
        </div>
        ${editTimeFormHtml}
        ${percentageFormHtml}
        ${approvalHistoryHtml}
    `;
    }

    // ✅ FUNGSI BARU: Show Modal Reject Detail
    function showRejectDetailModal(detailId, employeeName) {
        currentDetailId = detailId;
        document.getElementById('rejectDetailEmployeeName').textContent = employeeName;
        document.getElementById('detailRejectionReason').value = '';

        new bootstrap.Modal(document.getElementById('rejectDetailModal')).show();
    }

    // ✅ FUNGSI BARU: Submit Reject Detail
    function submitRejectDetail() {
        const reason = document.getElementById('detailRejectionReason').value.trim();

        if (reason.length < 10) {
            alert('Alasan penolakan harus minimal 10 karakter');
            return;
        }

        const submitBtn = document.querySelector('#rejectDetailModal .btn-danger');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        submitBtn.disabled = true;

        fetch(`/approvals/detail/${currentDetailId}/reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('rejectDetailModal')).hide();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Detail berhasil ditolak',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload detail modal
                            showDetailModal(currentApprovalId, '');
                        });
                    } else {
                        alert('Detail berhasil ditolak');
                        showDetailModal(currentApprovalId, '');
                    }
                } else {
                    alert('Gagal menolak detail: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    }

    // ✅ FUNGSI BARU: Unreject Detail
    function unrejectDetail(detailId) {
        if (!confirm('Apakah Anda yakin ingin membatalkan penolakan untuk karyawan ini?')) {
            return;
        }

        fetch(`/approvals/detail/${detailId}/unreject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Penolakan berhasil dibatalkan',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            showDetailModal(currentApprovalId, '');
                        });
                    } else {
                        alert('Penolakan berhasil dibatalkan');
                        showDetailModal(currentApprovalId, '');
                    }
                } else {
                    alert('Gagal membatalkan penolakan: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan: ' + error.message);
            });
    }

    function updatePercentage() {
        if (!currentOvertimeId) {
            alert('Error: Overtime ID tidak ditemukan');
            return;
        }

        const form = document.getElementById('editPercentageForm');
        if (!form) {
            alert('Error: Form edit percentage tidak ditemukan');
            return;
        }

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        const updateButton = form.querySelector('button');
        const originalText = updateButton.innerHTML;
        updateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        updateButton.disabled = true;

        fetch(`/overtime/${currentOvertimeId}/update-percentage`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Persentase realisasi berhasil diupdate',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert('Persentase realisasi berhasil diupdate');
                        location.reload();
                    }
                } else {
                    alert('Gagal update persentase: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan: ' + error.message);
            })
            .finally(() => {
                updateButton.innerHTML = originalText;
                updateButton.disabled = false;
            });
    }

    function updateTime() {
        if (!currentOvertimeId) {
            alert('Error: Overtime ID tidak ditemukan');
            return;
        }

        const form = document.getElementById('editTimeForm');
        if (!form) {
            alert('Error: Form edit time tidak ditemukan');
            return;
        }

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        const updateButton = form.querySelector('button');
        const originalText = updateButton.innerHTML;
        updateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        updateButton.disabled = true;

        fetch(`/overtime/${currentOvertimeId}/update-time`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Jam lembur berhasil diupdate',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert('Jam lembur berhasil diupdate');
                        location.reload();
                    }
                } else {
                    alert('Gagal update jam lembur: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan: ' + error.message);
            })
            .finally(() => {
                updateButton.innerHTML = originalText;
                updateButton.disabled = false;
            });
    }

    function editOvertimeModal(overtimeId) {
        document.getElementById('editModalBody').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data untuk edit...</div>';
        new bootstrap.Modal(document.getElementById('editModal')).show();

        fetch(`/overtime/${overtimeId}/edit`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('editModalBody').innerHTML = generateEditForm(data);
                currentOvertimeId = overtimeId;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('editModalBody').innerHTML = '<div class="alert alert-danger">Gagal memuat data: ' + error.message + '</div>';
            });
    }

    function generateEditForm(data) {
        return `
        <form id="editOvertimeForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">No. SPK</label>
                        <input type="text" class="form-control" value="${data.request_number}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" class="form-control" name="date" value="${data.date}" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Keterangan</label>
                <textarea class="form-control" name="notes" rows="3">${data.notes || ''}</textarea>
            </div>
        </form>
    `;
    }

    function saveEditChanges() {
        const form = document.getElementById('editOvertimeForm');
        if (!form) {
            alert('Form edit tidak ditemukan');
            return;
        }

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        fetch(`/overtime/${currentOvertimeId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data lembur berhasil diupdate',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('Data lembur berhasil diupdate');
                    }

                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Gagal update data: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan: ' + error.message);
            });
    }

    function confirmDelete(overtimeId, requestNumber) {
        deleteOvertimeId = overtimeId;
        document.getElementById('deleteRequestNumber').textContent = requestNumber;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function deleteOvertime() {
        if (!deleteOvertimeId) return;

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghapus...';
        confirmBtn.disabled = true;

        fetch(`/overtime/${deleteOvertimeId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data lembur berhasil dihapus',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('Data lembur berhasil dihapus');
                    }

                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Gagal menghapus data: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan: ' + error.message);
            })
            .finally(() => {
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            });
    }

    function showRejectModal() {
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }

    function approveRequest() {
        if (!currentApprovalId) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/approvals/approve/${currentApprovalId}`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';

        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        document.body.appendChild(form);
        form.submit();
    }

    function submitReject() {
        if (!currentApprovalId) return;

        const reason = document.getElementById('reason').value;
        if (!reason.trim()) {
            alert('Alasan penolakan harus diisi');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/approvals/reject/${currentApprovalId}`;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';

        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'reason';
        reasonInput.value = reason;

        form.appendChild(csrfToken);
        form.appendChild(methodInput);
        form.appendChild(reasonInput);
        document.body.appendChild(form);
        form.submit();
    }
</script>
@endpush
@endsection