@extends('layouts.app')

@section('content')
<style>
    /* Dashboard Specific Styles */
    .dashboard-container {
        padding: 0;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .dashboard-header h1 {
        font-size: 1.8rem;
        margin: 0 0 5px 0;
        font-weight: 700;
    }
    
    .dashboard-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }
    
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.12);
    }
    
    .stat-card.blue { border-left: 4px solid #007bff; }
    .stat-card.green { border-left: 4px solid #28a745; }
    .stat-card.orange { border-left: 4px solid #fd7e14; }
    .stat-card.cyan { border-left: 4px solid #17a2b8; }
    
    .stat-card .stat-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 3rem;
        opacity: 0.1;
    }
    
    .stat-card .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        color: #2c3e50;
    }
    
    .stat-card .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin: 5px 0 0 0;
        font-weight: 500;
    }
    
    /* Charts */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .chart-box {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .chart-header {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .chart-header.purple {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .chart-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .chart-header select {
        background: white !important;
        color: #333 !important;
        border: none !important;
        font-size: 0.85rem;
        padding: 5px 10px;
        border-radius: 5px;
    }
    
    .chart-body {
        padding: 20px;
    }
    
    .chart-canvas {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Mobile chart scroll wrapper */
    .chart-scroll {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Info Cards */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .info-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .info-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .info-body {
        padding: 15px;
    }
    
    .status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        margin: 5px 0;
        background: #f8f9fa;
        border-radius: 5px;
        transition: background 0.3s ease;
    }
    
    .status-row:hover {
        background: #e9ecef;
    }
    
    .dept-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 10px;
        margin: 5px 0;
        background: #f8f9fa;
        border-left: 3px solid #007bff;
        border-radius: 3px;
    }
    
    .alert-box {
        padding: 10px 12px;
        border-radius: 5px;
        margin: 8px 0;
        display: flex;
        align-items: center;
        font-size: 0.85rem;
    }
    
    .alert-box i {
        margin-right: 10px;
        font-size: 1.2rem;
    }
    
    /* Quick Links */
    .quick-links {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        margin-bottom: 25px;
    }
    
    .quick-links h5 {
        margin: 0 0 15px 0;
        color: #2c3e50;
        font-weight: 600;
    }
    
    .quick-links .btn {
        margin: 5px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 20px 15px;
            margin-bottom: 20px;
        }
        
        .dashboard-header h1 {
            font-size: 1.4rem;
        }
        
        .dashboard-header .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 10px;
        }
        
        .dashboard-header .d-flex > div:last-child {
            font-size: 0.9rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .charts-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .chart-canvas {
            height: 250px;
        }
        
        .chart-body {
            padding: 15px;
        }
        
        .chart-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 10px;
        }
        
        .chart-scroll .chart-canvas {
            min-width: 380px;
        }
        
        .chart-header {
            padding: 12px 15px;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .chart-header h5 {
            font-size: 0.95rem;
        }
        
        .chart-header select {
            width: 100%;
            max-width: 150px;
        }
        
        .stat-card {
            padding: 15px;
        }
        
        .stat-card .stat-number {
            font-size: 1.6rem;
        }
        
        .stat-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.08;
        }
        
        .info-body {
            padding: 12px;
        }
        
        .quick-links {
            padding: 15px;
        }
        
        .quick-links .btn {
            margin: 3px;
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
        
        .status-row,
        .dept-row {
            padding: 8px;
            margin: 4px 0;
        }
        
        .alert-box {
            padding: 8px 10px;
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 480px) {
        .dashboard-header {
            padding: 15px 12px;
        }
        
        .dashboard-header h1 {
            font-size: 1.25rem;
        }
        
        .stat-card {
            padding: 12px;
        }
        
        .stat-card .stat-number {
            font-size: 1.4rem;
        }
        
        .chart-scroll .chart-canvas {
            min-width: 350px;
        }
        
        .chart-header {
            padding: 10px 12px;
        }
        
        .chart-body {
            padding: 12px;
        }
        
        .quick-links .btn {
            font-size: 0.8rem;
            padding: 0.35rem 0.7rem;
            margin: 2px;
        }
    }
    
    @media (min-width: 769px) and (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .charts-grid {
            grid-template-columns: 1fr;
        }
        
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-chart-line"></i> Dashboard SPK Lembur</h1>
                <p>Monitoring dan Analisis Sistem Lembur</p>
            </div>
            <div>
                <i class="fas fa-calendar-alt me-2"></i>
                <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <i class="fas fa-file-alt stat-icon"></i>
            <h2 class="stat-number">{{ $totalSPKLThisMonth }}</h2>
            <p class="stat-label">Total SPK Bulan Ini</p>
        </div>
        
        <div class="stat-card green">
            <i class="fas fa-calendar-check stat-icon"></i>
            <h2 class="stat-number">{{ $activePlanning }}</h2>
            <p class="stat-label">Planning Aktif</p>
        </div>
        
        <div class="stat-card orange">
            <i class="fas fa-hourglass-half stat-icon"></i>
            <h2 class="stat-number">{{ $pendingMyApproval }}</h2>
            <p class="stat-label">Menunggu Approval Saya</p>
        </div>
        
        <div class="stat-card cyan">
            <i class="fas fa-clock stat-icon"></i>
            <h2 class="stat-number" style="font-size: 1.5rem;">{{ $totalHoursFormatted }}</h2>
            <p class="stat-label">Total Jam Bulan Ini</p>
        </div>
    </div>

    <!-- Charts Row 1: SPK -->
    <div class="charts-grid">
        <div class="chart-box">
            <div class="chart-header">
                <h5><i class="fas fa-chart-line me-2"></i>SPK Per Hari (Bulan Ini)</h5>
                <select id="monthSelector" class="form-select-sm">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $i, 1)->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="chart-body">
                <div class="chart-scroll">
                    <div class="chart-canvas">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-box">
            <div class="chart-header">
                <h5><i class="fas fa-chart-bar me-2"></i>SPK Per Bulan (Tahun Ini)</h5>
                <select id="yearSelector" class="form-select-sm">
                    @for($i = date('Y') - 2; $i <= date('Y'); $i++)
                        <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="chart-body">
                <div class="chart-scroll">
                    <div class="chart-canvas">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Total Jam -->
    <div class="charts-grid">
        <div class="chart-box">
            <div class="chart-header purple">
                <h5><i class="fas fa-clock me-2"></i>Total Jam Per Hari (Bulan Ini)</h5>
                <select id="monthSelectorHours" class="form-select-sm">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create(null, $i, 1)->isoFormat('MMMM') }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="chart-body">
                <div class="chart-scroll">
                    <div class="chart-canvas">
                        <canvas id="dailyHoursChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-box">
            <div class="chart-header purple">
                <h5><i class="fas fa-chart-area me-2"></i>Total Jam Per Bulan (Tahun Ini)</h5>
                <select id="yearSelectorHours" class="form-select-sm">
                    @for($i = date('Y') - 2; $i <= date('Y'); $i++)
                        <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="chart-body">
                <div class="chart-scroll">
                    <div class="chart-canvas">
                        <canvas id="monthlyHoursChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="info-grid">
        <!-- Status Breakdown -->
        <div class="info-card">
            <div class="info-header">
                <i class="fas fa-chart-pie me-2"></i>Breakdown Status SPK
            </div>
            <div class="info-body">
                <div class="status-row">
                    <span><i class="fas fa-circle text-warning me-2"></i>In Progress</span>
                    <span class="badge bg-warning">{{ $statusBreakdown['pending'] }}</span>
                </div>
                <div class="status-row">
                    <span><i class="fas fa-circle text-info me-2"></i>Ready for Input</span>
                    <span class="badge bg-info">{{ $statusBreakdown['approved'] }}</span>
                </div>
                <div class="status-row">
                    <span><i class="fas fa-circle text-success me-2"></i>Completed</span>
                    <span class="badge bg-success">{{ $statusBreakdown['completed'] }}</span>
                </div>
                <div class="status-row">
                    <span><i class="fas fa-circle text-danger me-2"></i>Rejected</span>
                    <span class="badge bg-danger">{{ $statusBreakdown['rejected'] }}</span>
                </div>
            </div>
        </div>

        <!-- Top Departments -->
        <div class="info-card">
            <div class="info-header">
                <i class="fas fa-building me-2"></i>Top 5 Department (Bulan Ini)
            </div>
            <div class="info-body">
                @forelse($topDepartments as $dept)
                    <div class="dept-row">
                        <strong>{{ $dept['department'] }}</strong>
                        <span class="badge bg-primary">{{ $dept['formatted_time'] }}</span>
                    </div>
                @empty
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0 small">Belum ada data bulan ini</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Planning Alerts -->
        <div class="info-card">
            <div class="info-header">
                <i class="fas fa-exclamation-triangle me-2"></i>Planning Perhatian
            </div>
            <div class="info-body">
                @if($planningsNeedReminder > 0)
                    <div class="alert-box alert-warning">
                        <i class="fas fa-bell"></i>
                        <span><strong>{{ $planningsNeedReminder }}</strong> planning H-7 perlu reminder</span>
                    </div>
                @endif

                @if($planningsExpired > 0)
                    <div class="alert-box alert-danger">
                        <i class="fas fa-calendar-times"></i>
                        <span><strong>{{ $planningsExpired }}</strong> planning sudah expired</span>
                    </div>
                @endif

                @if($planningsPendingApproval > 0)
                    <div class="alert-box alert-info">
                        <i class="fas fa-hourglass-half"></i>
                        <span><strong>{{ $planningsPendingApproval }}</strong> planning menunggu approval</span>
                    </div>
                @endif

                @if($planningsNeedReminder == 0 && $planningsExpired == 0 && $planningsPendingApproval == 0)
                    <div class="text-center text-success py-3">
                        <i class="fas fa-check-circle fa-3x mb-2"></i>
                        <p class="mb-0"><strong>Semua planning OK!</strong></p>
                    </div>
                @endif

                <div class="text-center mt-3">
                    <a href="{{ route('planning.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-calendar-alt me-1"></i>Lihat Planning
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
        <h5><i class="fas fa-link me-2"></i>Quick Access</h5>
        <a href="{{ route('overtime.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus-circle"></i> Buat SPK
        </a>
        <a href="{{ route('overtime.index') }}" class="btn btn-info btn-sm">
            <i class="fas fa-list"></i> Lihat SPK
        </a>
        <a href="{{ route('planning.index') }}" class="btn btn-success btn-sm">
            <i class="fas fa-calendar-alt"></i> Planning
        </a>
        <a href="{{ route('reports.overtime-leaderboard') }}" class="btn btn-warning btn-sm">
            <i class="fas fa-chart-bar"></i> Report
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    let dailyChart, monthlyChart, dailyHoursChart, monthlyHoursChart;

    // Load functions
    function loadDailyChart(month, year) {
        $.get('{{ route("dashboard.chart.daily") }}', { month, year }, function(data) {
            updateDailyChart(data);
        });
    }

    function loadMonthlyChart(year) {
        $.get('{{ route("dashboard.chart.monthly") }}', { year }, function(data) {
            updateMonthlyChart(data);
        });
    }

    function loadDailyHoursChart(month, year) {
        $.get('{{ route("dashboard.chart.daily-hours") }}', { month, year }, function(data) {
            updateDailyHoursChart(data);
        });
    }

    function loadMonthlyHoursChart(year) {
        $.get('{{ route("dashboard.chart.monthly-hours") }}', { year }, function(data) {
            updateMonthlyHoursChart(data);
        });
    }

    // Update chart functions
    function updateDailyChart(data) {
        const ctx = document.getElementById('dailyChart');
        if (!ctx) return;
        
        if (dailyChart) dailyChart.destroy();
        dailyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Jumlah SPK',
                    data: data.values,
                    borderColor: 'rgb(79, 172, 254)',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    function updateMonthlyChart(data) {
        const ctx = document.getElementById('monthlyChart');
        if (!ctx) return;
        
        if (monthlyChart) monthlyChart.destroy();
        monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Jumlah SPK',
                    data: data.values,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    function updateDailyHoursChart(data) {
        const ctx = document.getElementById('dailyHoursChart');
        if (!ctx) return;
        
        if (dailyHoursChart) dailyHoursChart.destroy();
        dailyHoursChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Total Jam',
                    data: data.values,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function updateMonthlyHoursChart(data) {
        const ctx = document.getElementById('monthlyHoursChart');
        if (!ctx) return;
        
        if (monthlyHoursChart) monthlyHoursChart.destroy();
        monthlyHoursChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Total Jam',
                    data: data.values,
                    borderColor: 'rgb(118, 75, 162)',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Event listeners
    $('#monthSelector, #monthSelectorHours').on('change', function() {
        const month = $(this).val();
        const year = $(this).attr('id').includes('Hours') ? $('#yearSelectorHours').val() : $('#yearSelector').val();
        if ($(this).attr('id').includes('Hours')) {
            loadDailyHoursChart(month, year);
        } else {
            loadDailyChart(month, year);
        }
    });

    $('#yearSelector, #yearSelectorHours').on('change', function() {
        const year = $(this).val();
        if ($(this).attr('id').includes('Hours')) {
            loadMonthlyHoursChart(year);
        } else {
            loadMonthlyChart(year);
        }
    });

    // Initial load
    loadDailyChart({{ date('n') }}, {{ date('Y') }});
    loadMonthlyChart({{ date('Y') }});
    loadDailyHoursChart({{ date('n') }}, {{ date('Y') }});
    loadMonthlyHoursChart({{ date('Y') }});
});
</script>
@endpush
@endsection