@extends('layouts.app')

@section('content')
<style>
    /* Mobile responsive styles for planning */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
        }
        
        .page-header h2 {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .stats-row {
            margin-bottom: 20px;
        }
        
        .stats-row .col-md-3 {
            margin-bottom: 15px;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .table-responsive {
            border: none;
            font-size: 0.875rem;
        }
        
        .table th,
        .table td {
            padding: 0.5rem 0.25rem;
            white-space: nowrap;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .badge {
            font-size: 0.65rem;
        }
        
        /* Hide less important columns on mobile */
        .table th:nth-child(5),
        .table td:nth-child(5),
        .table th:nth-child(6),
        .table td:nth-child(6),
        .table th:nth-child(9),
        .table td:nth-child(9) {
            display: none;
        }
    }
    
    @media (min-width: 769px) and (max-width: 1024px) {
        .stats-row .col-md-3 {
            margin-bottom: 15px;
        }
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .card.border-left-primary {
        border-left: 4px solid #007bff;
    }
    
    .card.border-left-warning {
        border-left: 4px solid #ffc107;
    }
    
    .card.border-left-success {
        border-left: 4px solid #28a745;
    }
    
    .card.border-left-danger {
        border-left: 4px solid #dc3545;
    }
</style>

<div class="page-header">
    <h2>Planning Lembur</h2>
    @if(in_array(auth()->user()->level_jabatan, ['Department Head', 'Administrator']))
        <a href="{{ route('planning.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Buat Planning Baru</span>
        </a>
    @endif
</div>

{{-- Filter & Stats --}}
<div class="row stats-row">
    <div class="col-md-3 col-6">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Planning</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $plannings->total() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-left-warning">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Approval</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ $plannings->where('status', 'pending')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800">
                    {{ $plannings->where('status', 'approved')->count() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
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
                        <th>Planning ID</th>
                        <th>Departemen</th>
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
                        <td><strong>{{ $planning->department->name ?? '-' }}</strong></td>
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
                                <i class="fas fa-eye"></i> <span class="d-none d-lg-inline">Detail</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center">Belum ada planning lembur</td>
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
        },
        columnDefs: [
            {
                targets: [4, 5, 8], // Hide kuota, terpakai, dibuat oleh on mobile
                className: 'd-none d-md-table-cell'
            }
        ]
    });
});
</script>
@endpush
