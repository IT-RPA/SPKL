@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Daftar Pengajuan Lembur</h2>
    
    {{-- ✅ PERBAIKAN: Kondisional untuk button berdasarkan hasIncompleteRequest --}}
    @if(isset($hasIncompleteRequest) && $hasIncompleteRequest)
        {{-- Button disabled dengan tooltip --}}
        <div class="position-relative">
            <button 
                class="btn btn-secondary" 
                disabled 
                data-bs-toggle="tooltip" 
                data-bs-placement="left"
                data-bs-title="Tidak dapat membuat pengajuan baru karena masih ada pengajuan yang perlu diselesaikan"
                onclick="showIncompleteAlert()"
            >
                <i class="fas fa-plus"></i> Buat Pengajuan Baru
            </button>
        </div>
    @else
        {{-- Button normal --}}
        <a href="{{ route('overtime.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Pengajuan Baru
        </a>
    @endif
</div>

{{-- ✅ TAMBAHAN: Alert khusus untuk pengajuan yang perlu input data --}}
@if(isset($hasIncompleteRequest) && $hasIncompleteRequest)
<div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
        <div>
            <h6 class="alert-heading mb-1">Perhatian: Ada Pengajuan yang Belum Selesai!</h6>
            <p class="mb-0">
                Anda memiliki pengajuan lembur dengan status <strong>"Perlu Input Data"</strong> yang belum diselesaikan. 
                Harap lengkapi input <strong>qty actual/percentage realisasi</strong> terlebih dahulu sebelum membuat pengajuan baru.
            </p>
            <hr>
            <small class="text-muted">
                <i class="fas fa-lightbulb me-1"></i>
                Cari pengajuan dengan status "Perlu Input Data" di tabel di bawah, lalu klik "Detail" untuk mengisi data yang diperlukan.
            </small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

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
                    <tr {{-- ✅ Highlight row yang perlu input data --}}
                        @if($request->status == 'approved') class="table-warning" @endif
                    >
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
                                {{-- ✅ Tambahan icon untuk status yang perlu input data --}}
                                @if($request->status == 'approved')
                                    <i class="fas fa-exclamation-triangle ms-1" title="Perlu Input Data"></i>
                                @endif
                            </span>
                            
                            @if($request->status == 'approved')
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    Butuh input qty actual/percentage
                                </small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('overtime.show', $request) }}" 
                               class="btn btn-sm @if($request->status == 'approved') btn-warning @else btn-outline-primary @endif">
                                @if($request->status == 'approved')
                                    <i class="fas fa-edit"></i> Input Data
                                @else
                                    Detail
                                @endif
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

@push('scripts')
<script>
// ✅ Aktivasi tooltip Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// ✅ Function untuk menampilkan alert ketika button disabled diklik
function showIncompleteAlert() {
    Swal.fire({
        icon: 'warning',
        title: 'Tidak Dapat Membuat Pengajuan Baru',
        html: `
            <p>Anda masih memiliki pengajuan lembur dengan status <strong>"Perlu Input Data"</strong> yang belum diselesaikan.</p>
            <hr>
            <p><i class="fas fa-info-circle text-info"></i> <strong>Langkah yang harus dilakukan:</strong></p>
            <ol class="text-start">
                <li>Cari pengajuan dengan status <span class="badge bg-warning text-dark">Perlu Input Data</span> di tabel</li>
                <li>Klik tombol <span class="badge bg-warning text-dark">Input Data</span> pada pengajuan tersebut</li>
                <li>Lengkapi input <strong>qty actual</strong> atau <strong>percentage realisasi</strong></li>
                <li>Setelah selesai, Anda dapat membuat pengajuan baru</li>
            </ol>
        `,
        confirmButtonText: 'Mengerti',
        confirmButtonColor: '#ffc107'
    });
}
</script>
@endpush