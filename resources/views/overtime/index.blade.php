@extends('layouts.app')

@section('content')
<style>
    .erp-list-page { animation: erpFade .35s ease-out; }
    @keyframes erpFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    .erp-list-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 18px 20px;
        box-shadow: var(--shadow-sm);
    }
    .erp-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 12px 14px;
        margin-bottom: 14px;
        box-shadow: var(--shadow-sm);
    }
    .erp-searchbox {
        display:flex; align-items:center; gap:8px; color:var(--text-muted);
        background:#f8fafc; border:1px solid var(--border); border-radius:12px; padding:9px 12px; min-width:280px;
    }
    .erp-alert-card {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-left: 4px solid #f97316;
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 14px;
        display:flex; gap:12px; align-items:flex-start;
    }
    .erp-alert-icon { width:38px; height:38px; border-radius:11px; display:grid; place-items:center; background:#ffedd5; color:#c2410c; flex:0 0 auto; }

    .erp-table-card { background:#fff; border:1px solid var(--border); border-radius:18px; box-shadow:var(--shadow-sm); overflow:hidden; }
    .erp-id-main { font-weight:900; color:#0f172a; letter-spacing:-.01em; }
    .erp-subtext { display:block; color:var(--text-muted); font-size:.78rem; margin-top:2px; }
    .erp-action-btn { width:36px; height:36px; padding:0; display:inline-grid; place-items:center; border-radius:10px; }
    .desktop-table { display:block; }
    .mobile-cards { display:none; }

    .spk-mobile-card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:16px;
        box-shadow:var(--shadow-sm);
        padding:14px;
        margin-bottom:12px;
    }
    .spk-mobile-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:12px; }
    .spk-mobile-meta { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:12px; background:#f8fafc; border-radius:13px; margin-bottom:12px; }
    .spk-meta-label { color:var(--text-muted); font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .spk-meta-value { font-weight:800; color:#334155; margin-top:2px; }

    @media (max-width: 768px) {
        .erp-list-header { flex-direction: column; align-items: stretch; padding:16px; }
        .erp-list-header .btn { width:100%; justify-content:center; }
        .erp-toolbar { display:none; }
        .desktop-table { display:none; }
        .mobile-cards { display:block; }
    }
</style>

<div class="erp-list-page">
    <div class="erp-list-header">
        <div>
            <h1 class="page-title">Pengajuan Lembur</h1>
            <p class="page-subtitle">Kelola daftar SPK lembur dengan tampilan ERP yang rapi dan mudah dipantau.</p>
        </div>
        @if(isset($hasIncompleteRequest) && $hasIncompleteRequest)
            <button class="btn btn-secondary" disabled onclick="showIncompleteAlert()">
                <i class="fas fa-plus"></i> Pengajuan Baru
            </button>
        @else
            <a href="{{ route('overtime.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Pengajuan Baru
            </a>
        @endif
    </div>

    @if(isset($hasIncompleteRequest) && $hasIncompleteRequest)
    <div class="erp-alert-card">
        <div class="erp-alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <h6 class="fw-bold mb-1">Ada pengajuan yang belum selesai</h6>
            <p class="mb-0 text-muted small">Selesaikan input data aktual/percentage realisasi terlebih dahulu sebelum membuat pengajuan baru.</p>
        </div>
    </div>
    @endif

    <div class="erp-toolbar">
        <div class="erp-searchbox"><i class="fas fa-search"></i><span>Daftar SPK Lembur</span></div>
        <div class="text-muted small fw-bold"><i class="fas fa-database me-1"></i>{{ $requests->total() }} records</div>
    </div>

    <div class="erp-table-card desktop-table">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Tanggal</th>
                        <th>Departemen</th>
                        <th>Kategori</th>
                        <th>Tingkatan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    @php
                        $statusText = '';
                        $statusClass = '';
                        switch($request->status) {
                            case 'completed': $statusText = 'Completed'; $statusClass = 'status-green'; break;
                            case 'approved': $statusText = 'Perlu Input Data'; $statusClass = 'status-act'; break;
                            case 'rejected': $statusText = 'Rejected'; $statusClass = 'status-red'; break;
                            case 'approved_sect': $statusText = 'Approved Section'; $statusClass = 'status-yellow'; break;
                            case 'approved_subdept': $statusText = 'Approved Sub Dept'; $statusClass = 'status-yellow'; break;
                            case 'approved_dept': $statusText = 'Approved Dept'; $statusClass = 'status-yellow'; break;
                            case 'approved_subdiv': $statusText = 'Approved Sub Div'; $statusClass = 'status-yellow'; break;
                            case 'approved_div': $statusText = 'Approved Div'; $statusClass = 'status-yellow'; break;
                            case 'approved_hrd': $statusText = 'Approved HRD'; $statusClass = 'status-yellow'; break;
                            default: $statusText = 'Pending'; $statusClass = 'status-yellow'; break;
                        }
                    @endphp
                    <tr>
                        <td><span class="erp-id-main">{{ $request->request_number }}</span>@if($request->planning)<span class="erp-subtext">{{ $request->planning->planning_number }}</span>@endif</td>
                        <td>{{ $request->date->format('d M Y') }}</td>
                        <td>{{ $request->department->name }}</td>
                        <td>
                            @if($request->overtime_category === 'planned')
                                <span class="status-badge status-green">Planned</span>
                            @else
                                <span class="status-badge status-blue">Unplanned</span>
                            @endif
                        </td>
                        <td><span class="badge text-bg-light border">{{ ucfirst(str_replace('_', ' ', $request->requester_level)) }}</span></td>
                        <td><span class="status-badge {{ $statusClass }}">{{ $statusText }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('overtime.show', $request) }}" class="btn btn-sm {{ $request->status == 'approved' ? 'btn-primary' : 'btn-light border' }} erp-action-btn" title="{{ $request->status == 'approved' ? 'Input Data' : 'Detail' }}">
                                <i class="fas {{ $request->status == 'approved' ? 'fa-edit' : 'fa-eye' }}"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mobile-cards">
        @forelse($requests as $request)
            @php
                $statusText = '';
                $statusClass = '';
                switch($request->status) {
                    case 'completed': $statusText = 'Completed'; $statusClass = 'status-green'; break;
                    case 'approved': $statusText = 'Perlu Input Data'; $statusClass = 'status-act'; break;
                    case 'rejected': $statusText = 'Rejected'; $statusClass = 'status-red'; break;
                    case 'approved_sect': $statusText = 'Approved Section'; $statusClass = 'status-yellow'; break;
                    case 'approved_subdept': $statusText = 'Approved Sub Dept'; $statusClass = 'status-yellow'; break;
                    case 'approved_dept': $statusText = 'Approved Dept'; $statusClass = 'status-yellow'; break;
                    case 'approved_subdiv': $statusText = 'Approved Sub Div'; $statusClass = 'status-yellow'; break;
                    case 'approved_div': $statusText = 'Approved Div'; $statusClass = 'status-yellow'; break;
                    case 'approved_hrd': $statusText = 'Approved HRD'; $statusClass = 'status-yellow'; break;
                    default: $statusText = 'Pending'; $statusClass = 'status-yellow'; break;
                }
            @endphp
            <div class="spk-mobile-card">
                <div class="spk-mobile-top">
                    <div>
                        <div class="erp-id-main">{{ $request->request_number }}</div>
                        <span class="erp-subtext">{{ $request->date->format('d M Y') }}</span>
                    </div>
                    <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                </div>
                <div class="spk-mobile-meta">
                    <div><div class="spk-meta-label">Departemen</div><div class="spk-meta-value">{{ $request->department->name }}</div></div>
                    <div><div class="spk-meta-label">Kategori</div><div class="spk-meta-value">{{ ucfirst($request->overtime_category) }}</div></div>
                    <div><div class="spk-meta-label">Tingkatan</div><div class="spk-meta-value">{{ ucfirst(str_replace('_', ' ', $request->requester_level)) }}</div></div>
                    <div><div class="spk-meta-label">Planning</div><div class="spk-meta-value">{{ $request->planning->planning_number ?? '-' }}</div></div>
                </div>
                <a href="{{ route('overtime.show', $request) }}" class="btn {{ $request->status == 'approved' ? 'btn-primary' : 'btn-light border' }} w-100 justify-content-center">
                    <i class="fas {{ $request->status == 'approved' ? 'fa-edit' : 'fa-eye' }}"></i>
                    {{ $request->status == 'approved' ? 'Input Data' : 'Lihat Detail' }}
                </a>
            </div>
        @empty
            <div class="erp-table-card p-5 text-center text-muted">Tidak ada data</div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $requests->links() }}
    </div>
</div>


@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function showIncompleteAlert() {
    Swal.fire({
        icon: 'warning',
        title: 'Tidak Dapat Membuat Pengajuan Baru',
        html: `
            <p>Anda masih memiliki pengajuan lembur dengan status <strong>"Perlu Input Data"</strong>.</p>
            <hr>
            <p><strong>Langkah yang harus dilakukan:</strong></p>
            <ol class="text-start">
                <li>Cari pengajuan dengan status <span class="badge bg-warning text-dark">Perlu Input Data</span></li>
                <li>Klik tombol <span class="badge bg-warning text-dark">Input Data</span></li>
                <li>Lengkapi input qty actual atau percentage</li>
                <li>Setelah selesai, Anda dapat membuat pengajuan baru</li>
            </ol>
        `,
        confirmButtonText: 'Mengerti',
        confirmButtonColor: '#ffc107'
    });
}
</script>
@endpush