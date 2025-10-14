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
    
    .chart-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .chart-card .card-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        border: none;
        padding: 20px 30px;
    }
    
    .chart-card .card-body {
        padding: 30px;
    }
    
    .chart-container {
        position: relative;
        height: 350px;
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
    
    /* Period Selector */
    .period-selector {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .period-btn {
        padding: 8px 20px;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 25px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .period-btn:hover {
        border-color: #667eea;
        color: #667eea;
    }
    
    .period-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
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
        
        .chart-container {
            height: 250px;
        }
        
        .period-selector {
            flex-direction: column;
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

    {{-- Chart Section --}}
    <div class="row">
        <!-- Daily Chart (Current Month) -->
        <div class="col-lg-6">
            <div class="card chart-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            SPKL Per Hari (Bulan Ini)
                        </h5>
                        <select id="monthSelector" class="form-select form-select-sm" style="width: 150px; background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.3);">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Chart (Current Year) -->
        <div class="col-lg-6">
            <div class="card chart-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            SPKL Per Bulan (Tahun Ini)
                        </h5>
                        <select id="yearSelector" class="form-select form-select-sm" style="width: 120px; background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.3);">
                            @for($i = date('Y') - 2; $i <= date('Y'); $i++)
                                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    // Animation untuk stats cards
    $('.stats-card').each(function(index) {
        $(this).delay(100 * index).fadeIn(500);
    });
    
    // Initialize charts
    let dailyChart = null;
    let monthlyChart = null;

    // Load daily chart
    function loadDailyChart(month, year) {
        $.ajax({
            url: '/dashboard/chart-data/daily',
            method: 'GET',
            data: { month: month, year: year },
            success: function(response) {
                updateDailyChart(response);
            }
        });
    }

    // Load monthly chart
    function loadMonthlyChart(year) {
        $.ajax({
            url: '/dashboard/chart-data/monthly',
            method: 'GET',
            data: { year: year },
            success: function(response) {
                updateMonthlyChart(response);
            }
        });
    }

    // Update daily chart
    function updateDailyChart(data) {
        const ctx = document.getElementById('dailyChart').getContext('2d');
        
        if (dailyChart) {
            dailyChart.destroy();
        }

        dailyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Jumlah SPKL',
                    data: data.values,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    // Update monthly chart
    function updateMonthlyChart(data) {
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        
        if (monthlyChart) {
            monthlyChart.destroy();
        }

        monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Jumlah SPKL',
                    data: data.values,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(199, 199, 199, 0.7)',
                        'rgba(83, 102, 255, 0.7)',
                        'rgba(255, 99, 255, 0.7)',
                        'rgba(99, 255, 132, 0.7)',
                        'rgba(255, 195, 0, 0.7)',
                        'rgba(99, 195, 255, 0.7)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)',
                        'rgba(83, 102, 255, 1)',
                        'rgba(255, 99, 255, 1)',
                        'rgba(99, 255, 132, 1)',
                        'rgba(255, 195, 0, 1)',
                        'rgba(99, 195, 255, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Event listeners untuk selectors
    $('#monthSelector').on('change', function() {
        const month = $(this).val();
        const year = $('#yearSelector').val() || {{ date('Y') }};
        loadDailyChart(month, year);
    });

    $('#yearSelector').on('change', function() {
        const year = $(this).val();
        const month = $('#monthSelector').val() || {{ date('n') }};
        loadDailyChart(month, year);
        loadMonthlyChart(year);
    });

    // Load initial data
    loadDailyChart({{ date('n') }}, {{ date('Y') }});
    loadMonthlyChart({{ date('Y') }});
});
</script>
@endpush

@endsection