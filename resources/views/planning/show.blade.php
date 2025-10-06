@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Detail Planning Lembur</h2>
    <a href="{{ route('planning.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="row">
    <div class="col-md-8">
        {{-- Info Planning --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Informasi Planning</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>No. Planning:</strong> {{ $planning->planning_number }}</p>
                        <p><strong>Departemen:</strong> {{ $planning->department->name }}</p>
                        <p><strong>Tanggal Lembur:</strong> {{ $planning->planned_date->format('d F Y') }}</p>
                        <p><strong>Jam:</strong> {{ $planning->planned_start_time }} - {{ $planning->planned_end_time }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Kuota Maksimal:</strong> {{ $planning->max_employees }} orang</p>
                        <p><strong>Terpakai:</strong> {{ $planning->used_employees }} orang</p>
                        <p><strong>Sisa Kuota:</strong> 
                            <span class="badge bg-{{ $planning->remaining_employees > 0 ? 'success' : 'secondary' }}">
                                {{ $planning->remaining_employees }} orang
                            </span>
                        </p>
                        <p><strong>Status:</strong> 
                            @php
                                $statusBadge = [
                                    'draft' => 'secondary',
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'active' => 'info',
                                    'completed' => 'dark',
                                    'expired' => 'secondary'
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusBadge[$planning->status] ?? 'secondary' }}">
                                {{ ucfirst($planning->status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <p><strong>Deskripsi Pekerjaan:</strong></p>
                        <p class="text-muted">{{ $planning->work_description }}</p>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <p><strong>Alasan/Justifikasi:</strong></p>
                        <p class="text-muted">{{ $planning->reason }}</p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Dibuat Oleh:</strong> {{ $planning->creator->name }} ({{ $planning->creator->jobLevel->name }})</p>
                        <p><strong>Tanggal Dibuat:</strong> {{ $planning->created_at->format('d M Y H:i') }}</p>
                    </div>
                    <div class="col-md-6">
                        @if($planning->approved_at)
                        <p><strong>Disetujui Pada:</strong> {{ $planning->approved_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Pengajuan Lembur yang Menggunakan Planning Ini --}}
        @if($planning->overtimeRequests->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Pengajuan Lembur Terkait ({{ $planning->overtimeRequests->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>No. SPK</th>
                                <th>Pengaju</th>
                                <th>Jumlah Karyawan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planning->overtimeRequests as $overtime)
                            <tr>
                                <td>{{ $overtime->request_number }}</td>
                                <td>{{ $overtime->requesterEmployee->name }}</td>
                                <td>{{ $overtime->details->count() }} orang</td>
                                <td><span class="badge bg-{{ $overtime->status_color }}">{{ $overtime->status }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        {{-- Status Approval --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle"></i> Status Approval</h5>
            </div>
            <div class="card-body">
                @forelse($planning->approvals as $approval)
                <div class="mb-3 pb-3 border-bottom">
                    <h6>{{ $approval->step_name }}</h6>
                    <p class="mb-1"><strong>Approver:</strong> {{ $approval->approverEmployee->name ?? 'Belum ditentukan' }}</p>
                    <p class="mb-1"><strong>Level:</strong> {{ $approval->approverEmployee->jobLevel->name ?? '-' }}</p>
                    <p class="mb-1"><strong>Status:</strong> 
                        <span class="badge bg-{{ $approval->status == 'approved' ? 'success' : ($approval->status == 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($approval->status) }}
                        </span>
                    </p>
                    @if($approval->approved_at)
                    <p class="mb-1"><small class="text-muted">{{ $approval->approved_at->format('d M Y H:i') }}</small></p>
                    @endif
                    @if($approval->notes)
                    <p class="mb-0"><small><strong>Catatan:</strong> {{ $approval->notes }}</small></p>
                    @endif
                </div>
                @empty
                <p class="text-muted">Tidak ada approval</p>
                @endforelse
            </div>
        </div>

        {{-- Action Buttons untuk Approver --}}
        @if($currentApproval && $currentApproval->status === 'pending')
            @php
                $canApproveNow = !$planning->approvals()
                    ->where('step_order', '<', $currentApproval->step_order)
                    ->where('status', 'pending')
                    ->exists();
            @endphp
            
            @if($canApproveNow)
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Giliran Anda</h5>
                </div>
                <div class="card-body">
                    <p>Anda memiliki wewenang untuk approve/reject planning ini.</p>
                    <form action="{{ route('planning.approve', $currentApproval->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger w-100" onclick="showRejectModal({{ $currentApproval->id }})">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Menunggu approval sebelumnya
            </div>
            @endif
        @endif
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alasan Penolakan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Planning</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showRejectModal(approvalId) {
    const form = document.getElementById('rejectForm');
    form.action = `/planning/approval/${approvalId}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush