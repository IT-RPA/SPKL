@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Planning Lembur</h2>
    <a href="{{ route('planning.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Buat Planning Baru
    </a>
</div>

{{-- Filter & Stats --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Planning</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $plannings->total() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-warning">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Approval</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ $plannings->where('status', 'pending')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ $plannings->where('status', 'approved')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-left-danger">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ $plannings->where('status', 'rejected')->count() }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="planningsTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>No. Planning</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Kuota</th>
                        <th>Terpakai</th>
                        <th>Sisa</th>
                        <th>Status</th>
                        <th>Dibuat Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plannings as $planning)
                    <tr>
                        <td><strong>{{ $planning->planning_number }}</strong></td>
                        <td>{{ $planning->planned_date->format('d M Y') }}</td>
                        <td>{{ $planning->planned_start_time }} - {{ $planning->planned_end_time }}</td>
                        <td>{{ $planning->max_employees }} orang</td>
                        <td>{{ $planning->used_employees }} orang</td>
                        <td>
                            <span class="badge bg-{{ $planning->remaining_employees > 0 ? 'success' : 'secondary' }}">
                                {{ $planning->remaining_employees }} orang
                            </span>
                        </td>
                        <td>
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
                        </td>
                        <td>{{ $planning->creator->name ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('planning.show', $planning->id) }}" 
                               class="btn btn-sm btn-outline-primary"
                               title="Lihat Detail">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Belum ada planning lembur</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $plannings->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#planningsTable').DataTable({
        responsive: true,
        pageLength: 10,
        ordering: true,
        paging: false,
        info: false,
        searching: true,
        language: {
            search: "Cari:",
            zeroRecords: "Tidak ada data yang sesuai",
            emptyTable: "Tidak ada data tersedia"
        }
    });
});
</script>
@endpush
