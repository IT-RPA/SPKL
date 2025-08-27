@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Approval Department Head</h2>
</div>

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
                            <span class="badge bg-{{ $approval->status == 'approved' ? 'success' : ($approval->status == 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($approval->status) }}
                            </span>
                            @if($approval->notes)
                                <br><small class="text-muted">{{ $approval->notes }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <!-- Tombol Detail selalu ada -->
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="showDetailModal({{ $approval->id }}, '{{ $approval->overtimeRequest->request_number }}')"
                                        title="Lihat Detail">
                                    <i class="fas fa-eye"></i> Detail
                                </button>
                                
                                <!-- Tombol Edit (hanya tampil jika status pending dan user memiliki akses edit) -->
                                @if($approval->status == 'pending' && auth()->user()->can('edit-overtime'))
                                    <button class="btn btn-sm btn-outline-warning"
                                            onclick="editOvertimeModal({{ $approval->overtimeRequest->id }})"
                                            title="Edit Overtime">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                @endif
                                
                                <!-- Tombol Delete (hanya tampil jika user memiliki akses delete) -->
                                @if(auth()->user()->can('delete-overtime'))
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete({{ $approval->overtimeRequest->id }}, '{{ $approval->overtimeRequest->request_number }}')"
                                            title="Hapus Overtime">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada data untuk disetujui</td>
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
                <!-- Tombol Approve/Reject hanya muncul jika status pending -->
                <div id="approvalButtons" style="display: none;">
                    <button type="button" class="btn btn-danger me-2" onclick="showRejectModal()">
                        <i class="fas fa-times"></i> Reject
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

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alasan Penolakan</h5>
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
                    <i class="fas fa-times"></i> Tolak
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
        
        // Tampilkan tombol approval hanya jika ada approval yang pending
        // Cek dari beberapa sumber data yang mungkin
        const hasCurrentPendingApproval = data.has_pending_approval || 
                                         data.current_approval_status === 'pending' || 
                                         data.status === 'pending' ||
                                         (data.approval_status && data.approval_status === 'pending');
        
        console.log('Checking approval buttons visibility:', {
            has_pending_approval: data.has_pending_approval,
            current_approval_status: data.current_approval_status,
            status: data.status,
            approval_status: data.approval_status,
            showButtons: hasCurrentPendingApproval
        });
                                         
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
            // Tutup modal
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            
            // Tampilkan pesan sukses
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
            
            // Refresh halaman
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

function generateDetailContent(data) {
    console.log('generateDetailContent called with:', data);
    console.log('can_edit_time value:', data.can_edit_time, 'type:', typeof data.can_edit_time);
    
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
    
    console.log('Final canEditTime:', canEditTime);
    
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
    } else if (canEditTime) {
        editTimeFormHtml = `
            <hr>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Perhatian:</strong> Anda memiliki akses edit jam, tetapi tidak ada detail lembur yang tersedia.
            </div>
        `;
    } else {
        editTimeFormHtml = `
            <hr>
            <div class="alert alert-secondary">
                <i class="fas fa-lock"></i>
                <strong>Info:</strong> Anda tidak memiliki akses untuk mengedit jam lembur ini.
            </div>
        `;
    }

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
                <p><strong>Can Edit Time:</strong> <span class="badge bg-${canEditTime ? 'success' : 'secondary'}">${canEditTime ? 'Ya' : 'Tidak'}</span></p>
            </div>
        </div>
        <hr>
        <h6>Detail Lembur:</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>ID Karyawan</th>
                        <th>Jam</th>
                        <th>Prioritas</th>
                        <th>Proses</th>
                        <th>Qty Plan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    ${(data.details && data.details.length > 0) ? 
                        data.details.map(detail => `
                            <tr>
                                <td>${detail.employee_name}</td>
                                <td>${detail.employee_id}</td>
                                <td>${detail.start_time} - ${detail.end_time}</td>
                                <td>${detail.work_priority}</td>
                                <td>${detail.work_process}</td>
                                <td>${detail.qty_plan || '-'}</td>
                                <td>${detail.notes || '-'}</td>
                            </tr>
                        `).join('') :
                        '<tr><td colspan="7" class="text-center">Tidak ada detail lembur</td></tr>'
                    }
                </tbody>
            </table>
        </div>
        ${editTimeFormHtml}
        ${approvalHistoryHtml}
    `;
}

function generateEditForm(data) {
    // Ini adalah placeholder untuk form edit
    // Anda perlu menyesuaikan dengan struktur data yang sebenarnya
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
            
            <!-- Tambahkan field lain sesuai kebutuhan -->
        </form>
    `;
}

function updateTime() {
    console.log('updateTime called, currentOvertimeId:', currentOvertimeId);
    
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
    
    console.log('Form data to be sent:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
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
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.log('Non-JSON response:', text);
                throw new Error('Response bukan JSON: ' + text);
            });
        }
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Jam lembur berhasil diupdate',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
                    location.reload();
                });
            } else {
                alert('Jam lembur berhasil diupdate');
                bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
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
</script>
@endpush
@endsection