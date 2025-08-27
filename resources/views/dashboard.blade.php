@extends('layouts.app')

@section('content')
<style>
    /* Custom Dashboard Styles */
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .stats-card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
        margin-bottom: 20px;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }
    
    .stats-card .card-body {
        padding: 25px;
        position: relative;
        z-index: 2;
    }
    
    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 80px;
        height: 80px;
        opacity: 0.1;
        z-index: 1;
    }
    
    .stats-card.bg-primary::before {
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18v-4h3v4h2v-7.5c0-.83.67-1.5 1.5-1.5S12 9.67 12 10.5V18h2v-6h3l1 4h2l-1-5h-3v-2.5c0-.83.67-1.5 1.5-1.5S19 7.67 19 8.5V10h2V8.5C21 6.57 19.43 5 17.5 5S14 6.57 14 8.5V10H9.5C7.57 10 6 11.57 6 13.5V18H4z"/></svg>') no-repeat center;
        background-size: contain;
    }
    
    .stats-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        line-height: 1;
    }
    
    .stats-label {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.9;
        font-weight: 500;
    }
    
    .stats-icon {
        opacity: 0.8;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    }
    
    .welcome-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .welcome-card .card-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        padding: 20px 30px;
    }
    
    .welcome-card .card-body {
        padding: 30px;
    }
    
    .workflow-step {
        background: #f8f9fa;
        border-left: 4px solid #007bff;
        padding: 15px 20px;
        margin: 10px 0;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .workflow-step:hover {
        background: #e3f2fd;
        border-left-color: #2196f3;
        transform: translateX(5px);
    }
    
    .status-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }
    
    .status-badge {
        padding: 8px 15px;
        border-radius: 25px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .status-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }
    
    .requests-table {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .requests-table .card-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border: none;
        padding: 20px 30px;
    }
    
    .requests-table .table {
        margin: 0;
    }
    
    .requests-table .table thead th {
        background: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 15px 20px;
    }
    
    .requests-table .table tbody td {
        padding: 15px 20px;
        border-top: 1px solid #f1f3f4;
        vertical-align: middle;
    }
    
    .requests-table .table tbody tr:hover {
        background: rgba(74, 172, 254, 0.05);
    }
    
    .detail-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 20px;
        padding: 8px 20px;
        color: white;
        font-size: 12px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }
    
    .detail-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 20px;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
        }
        
        .status-legend {
            justify-content: center;
        }
        
        .welcome-card .card-body {
            padding: 20px;
        }
    }
    
    /* Animation */
    .fade-in {
        animation: fadeIn 0.6s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="fade-in">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">Dashboard SPK Lembur</h1>
                <p class="mb-0 opacity-75">Sistem Pengelolaan Surat Perintah Kerja Lembur</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex align-items-center justify-content-end">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <span>{{ date('d F Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="stats-number">{{ $totalUsers }}</h2>
                        <p class="stats-label">Total Users</p>
                    </div>
                    <i class="fas fa-users fa-3x stats-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stats-card bg-info text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="stats-number">{{ $totalRequests }}</h2>
                        <p class="stats-label">Total Requests</p>
                    </div>
                    <i class="fas fa-clock fa-3x stats-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="stats-number">{{ $pendingRequests }}</h2>
                        <p class="stats-label">Pending Requests</p>
                    </div>
                    <i class="fas fa-hourglass-half fa-3x stats-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stats-card bg-success text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="stats-number">{{ $completedRequests }}</h2>
                        <p class="stats-label">Completed Requests</p>
                    </div>
                    <i class="fas fa-check-circle fa-3x stats-icon"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Welcome Section --}}
    <div class="row">
        <div class="col-12">
            <div class="card welcome-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Sistem SPK Lembur - Alur Kerja & Status
                    </h5>
                </div>
                <div class="card-body">
                    <p class="lead">Sistem ini menggunakan alur approval berlapis untuk memastikan setiap pengajuan lembur disetujui oleh pihak yang berwenang.</p>
                    
                    <h6 class="mb-3">
                        <i class="fas fa-route me-2 text-primary"></i>
                        Alur Approval Berdasarkan Level:
                    </h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="workflow-step">
                                <strong>Foreman:</strong><br>
                                Pengajuan → Sect Head → Dept Head → Div Head → HRD
                            </div>
                            <div class="workflow-step">
                                <strong>Section Head:</strong><br>
                                Pengajuan → Dept Head → Div Head → HRD
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="workflow-step">
                                <strong>Department Head:</strong><br>
                                Pengajuan → Div Head → HRD
                            </div>
                            <div class="workflow-step">
                                <strong>Division Head:</strong><br>
                                Pengajuan → HRD
                            </div>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3">
                        <i class="fas fa-palette me-2 text-primary"></i>
                        Status Color Legend:
                    </h6>
                    <div class="status-legend">
                        <span class="status-badge status-yellow">Pending (Kuning)</span>
                        <span class="status-badge status-orange">Approved Sect Head (Orange)</span>
                        <span class="status-badge status-blue">Approved Dept Head (Biru)</span>
                        <span class="status-badge status-purple">Approved Div Head (Ungu)</span>
                        <span class="status-badge status-green">Completed (Hijau)</span>
                        <span class="status-badge status-red">With Qty Plan (Merah)</span>
                        <span class="status-badge status-act">Ready for Actual Input (Act)</span>
                        <span class="status-badge status-gray">Rejected (Abu-abu)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Requests Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card requests-table">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Recent Overtime Requests
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>
                                        <i class="fas fa-user me-1"></i>
                                        Requester Level
                                    </th>
                                    <th>
                                        <i class="fas fa-flag me-1"></i>
                                        Status
                                    </th>
                                    <th class="text-center">
                                        <i class="fas fa-cog me-1"></i>
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle text-muted me-2"></i>
                                                {{ ucfirst(str_replace('_', ' ', $request->requester_level)) }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $request->status_color }}">
                                                @if($request->status_color == 'act')
                                                    Ready for Input
                                                @else
                                                    {{ ucfirst($request->status) }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('overtime.show', $request) }}" class="btn detail-btn">
                                                <i class="fas fa-eye me-1"></i>
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                                                <p class="mb-0">Tidak ada data pengajuan</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if($requests->hasPages())
                    <div class="d-flex justify-content-center p-3">
                        {{ $requests->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add smooth animations
    $('.stats-card').each(function(index) {
        $(this).delay(100 * index).fadeIn(500);
    });
    
    // Status badge tooltip
    $('.status-badge').tooltip({
        placement: 'top',
        trigger: 'hover'
    });
});
</script>

@endsection