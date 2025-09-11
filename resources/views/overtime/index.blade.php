@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Daftar Pengajuan Lembur</h2>
    <a href="{{ route('overtime.create') }}" class="btn btn-primary">Buat Pengajuan Baru</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Tanggal</th>
                        <th>Departemen</th>
                        <th>Tingkatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    <tr>
                        <td>{{ $request->request_number }}</td>
                        <td>{{ $request->date->format('d/m/Y') }}</td>
                        <td>{{ $request->department->name }}</td>
                        <td>
                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $request->requester_level)) }}</span>
                        </td>
                        <td>
                            @php
                                // ✅ PERBAIKAN: Logic penampilan status yang lebih jelas
                                $statusText = '';
                                $statusClass = '';
                                
                                switch($request->status) {
                                    case 'completed':
                                        $statusText = 'Completed';
                                        $statusClass = 'status-green';
                                        break;
                                    case 'approved':
                                        $statusText = 'Perlu Input Data';
                                        $statusClass = 'status-act';
                                        break;
                                    case 'rejected':
                                        $statusText = 'Rejected';
                                        $statusClass = 'status-red';
                                        break;
                                    case 'approved_sect':
                                        $statusText = 'Approved Section';
                                        $statusClass = 'status-yellow';
                                        break;
                                    case 'approved_subdept':
                                        $statusText = 'Approved Sub Dept';
                                        $statusClass = 'status-yellow';
                                        break;
                                    case 'approved_dept':
                                        $statusText = 'Approved Dept';
                                        $statusClass = 'status-yellow';
                                        break;
                                    case 'approved_subdiv':
                                        $statusText = 'Approved Sub Div';
                                        $statusClass = 'status-yellow';
                                        break;
                                    case 'approved_div':
                                        $statusText = 'Approved Div';
                                        $statusClass = 'status-yellow';
                                        break;
                                    case 'approved_hrd':
                                        $statusText = 'Approved HRD';
                                        $statusClass = 'status-yellow';
                                        break;
                                    default:
                                        $statusText = 'Pending';
                                        $statusClass = 'status-yellow';
                                        break;
                                }
                            @endphp
                            
                            <span class="status-badge {{ $statusClass }}">
                                {{ $statusText }}
                            </span>
                            
                            @if($request->status == 'approved')
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    Butuh input qty actual/percentage
                                </small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('overtime.show', $request) }}" class="btn btn-sm btn-outline-primary">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-center">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection