@extends('layouts.app')

@section('content')
<style>
    .erp-dashboard { animation: erpFade .35s ease-out; }
    @keyframes erpFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    .erp-page-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
    }
    .erp-head-card {
        background: linear-gradient(135deg, #ffffff, #f8fbff);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 18px 20px;
        box-shadow: var(--shadow-sm);
    }
    .erp-date-pill {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 999px;
        padding: 9px 14px;
        font-weight: 800;
        color: #334155;
        box-shadow: var(--shadow-sm);
        white-space: nowrap;
    }

    .erp-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }
    .erp-kpi-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px;
        box-shadow: var(--shadow-sm);
        position: relative;
        overflow: hidden;
    }
    .erp-kpi-card::after {
        content: '';
        position: absolute;
        width: 110px;
        height: 110px;
        border-radius: 999px;
        right: -54px;
        top: -54px;
        background: var(--tone, var(--primary-soft));
    }
    .erp-kpi-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; position: relative; z-index: 1; }
    .erp-kpi-icon { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; background: var(--tone, var(--primary-soft)); color: var(--tone-color, var(--primary)); }
    .erp-kpi-label { font-size: .74rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--text-muted); }
    .erp-kpi-value { font-size: 1.85rem; font-weight: 900; letter-spacing: -.04em; color: #0f172a; position: relative; z-index: 1; }
    .erp-kpi-note { color: var(--text-muted); font-size: .82rem; margin-top: 4px; position: relative; z-index: 1; }

    .erp-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(320px, .85fr);
        gap: 18px;
    }
    .erp-panel { background: #fff; border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow-sm); overflow: hidden; }
    .erp-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--border); background: #fff; }
    .erp-panel-title { font-weight: 900; margin: 0; font-size: 1rem; color: #0f172a; }
    .erp-panel-body { padding: 18px; }

    .erp-mini-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding: 12px; border:1px solid #eef2f7; border-radius: 13px; background:#fbfdff; margin-bottom:10px; }
    .erp-mini-label { font-weight: 800; color:#334155; }
    .erp-empty { text-align:center; padding: 18px 8px; color: var(--text-muted); }

    .erp-actions { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
    .erp-action {
        background:#fff; border:1px solid var(--border); border-radius:16px; padding:16px;
        display:flex; align-items:center; gap:12px; text-decoration:none; color:#0f172a; font-weight:800; box-shadow:var(--shadow-sm);
    }
    .erp-action:hover { border-color:#bfdbfe; background:#f8fbff; color:var(--primary); }
    .erp-action i { width:36px; height:36px; border-radius:11px; display:grid; place-items:center; background:var(--primary-soft); color:var(--primary); }

    @media (max-width: 1200px) {
        .erp-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .erp-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .erp-page-head { align-items: flex-start; flex-direction: column; }
        .erp-head-card { width: 100%; padding: 16px; margin-bottom: 10px; }
        .erp-date-pill { width: 100%; justify-content: center; }
        .erp-kpi-grid { grid-template-columns: 1fr; gap: 10px; }
        .erp-kpi-card { padding: 14px; }
        .erp-kpi-value { font-size: 1.55rem; }
        .erp-actions { grid-template-columns: 1fr 1fr; gap: 10px; }
        .erp-action { padding: 18px 14px; font-size: .85rem; flex-direction: column; text-align: center; height: auto; }
        .erp-action i { width: 44px; height: 44px; margin-bottom: 5px; }
    }
</style>


<div class="erp-dashboard">
    <div class="erp-page-head">
        <div class="erp-head-card flex-grow-1">
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Welcome back, <strong>{{ Auth::user()->name }}</strong>. Ringkasan aktivitas lembur dan approval hari ini.</p>
        </div>
        <div class="erp-date-pill">
            <i class="fas fa-calendar-alt text-primary me-2"></i>{{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}
        </div>
    </div>

    <div class="erp-kpi-grid">
        <div class="erp-kpi-card" style="--tone:#eff6ff;--tone-color:#2563eb;">
            <div class="erp-kpi-top"><span class="erp-kpi-label">SPK Bulan Ini</span><span class="erp-kpi-icon"><i class="fas fa-file-invoice"></i></span></div>
            <div class="erp-kpi-value">{{ $totalSPKLThisMonth }}</div>
            <div class="erp-kpi-note">Total request bulan berjalan</div>
        </div>
        <div class="erp-kpi-card" style="--tone:#dcfce7;--tone-color:#16a34a;">
            <div class="erp-kpi-top"><span class="erp-kpi-label">Planning Aktif</span><span class="erp-kpi-icon"><i class="fas fa-calendar-check"></i></span></div>
            <div class="erp-kpi-value">{{ $activePlanning }}</div>
            <div class="erp-kpi-note">Planning yang masih aktif</div>
        </div>
        <div class="erp-kpi-card" style="--tone:#fef3c7;--tone-color:#d97706;">
            <div class="erp-kpi-top"><span class="erp-kpi-label">Pending Approval</span><span class="erp-kpi-icon"><i class="fas fa-clock"></i></span></div>
            <div class="erp-kpi-value">{{ $pendingMyApproval }}</div>
            <div class="erp-kpi-note">Menunggu tindakan Anda</div>
        </div>
        <div class="erp-kpi-card" style="--tone:#e0f2fe;--tone-color:#0284c7;">
            <div class="erp-kpi-top"><span class="erp-kpi-label">Total Jam Kerja</span><span class="erp-kpi-icon"><i class="fas fa-hourglass-half"></i></span></div>
            <div class="erp-kpi-value" style="font-size:1.35rem;">{{ $totalHoursFormatted }}</div>
            <div class="erp-kpi-note">Akumulasi jam lembur</div>
        </div>
    </div>

    <div class="erp-grid">
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h5 class="erp-panel-title"><i class="fas fa-chart-line text-primary me-2"></i>Overtime Trend</h5>
                <select id="monthSelector" class="form-select form-select-sm" style="width:auto;">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $i, 1)->isoFormat('MMMM') }}</option>
                    @endfor
                </select>
            </div>
            <div class="erp-panel-body" style="height:360px;">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <div class="d-flex flex-column gap-3">
            <div class="erp-panel">
                <div class="erp-panel-head"><h5 class="erp-panel-title"><i class="fas fa-tasks text-primary me-2"></i>Status SPK</h5></div>
                <div class="erp-panel-body">
                    <div class="erp-mini-row"><span class="erp-mini-label">In Progress</span><span class="status-badge status-yellow">{{ $statusBreakdown['pending'] }}</span></div>
                    <div class="erp-mini-row"><span class="erp-mini-label">Ready for Input</span><span class="status-badge status-blue">{{ $statusBreakdown['approved'] }}</span></div>
                    <div class="erp-mini-row"><span class="erp-mini-label">Completed</span><span class="status-badge status-green">{{ $statusBreakdown['completed'] }}</span></div>
                </div>
            </div>

            <div class="erp-panel">
                <div class="erp-panel-head"><h5 class="erp-panel-title"><i class="fas fa-bell text-primary me-2"></i>Planning Alerts</h5></div>
                <div class="erp-panel-body">
                    @if($planningsNeedReminder > 0)
                        <div class="erp-mini-row"><span class="erp-mini-label">Reminder H-7</span><span class="status-badge status-yellow">{{ $planningsNeedReminder }}</span></div>
                    @endif
                    @if($planningsExpired > 0)
                        <div class="erp-mini-row"><span class="erp-mini-label">Expired Planning</span><span class="status-badge status-red">{{ $planningsExpired }}</span></div>
                    @endif
                    @if($planningsNeedReminder == 0 && $planningsExpired == 0)
                        <div class="erp-empty"><i class="fas fa-check-circle text-success fs-4 mb-2 d-block"></i>Semua planning aman</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="erp-actions">
        <a href="{{ route('overtime.create') }}" class="erp-action"><i class="fas fa-plus-circle"></i><span>Buat SPK</span></a>
        <a href="{{ route('overtime.index') }}" class="erp-action"><i class="fas fa-list"></i><span>List SPK</span></a>
        <a href="{{ route('planning.index') }}" class="erp-action"><i class="fas fa-calendar-alt"></i><span>Planning</span></a>
        <a href="{{ route('reports.overtime-leaderboard') }}" class="erp-action"><i class="fas fa-chart-bar"></i><span>Leaderboard</span></a>
    </div>
</div>


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    let dailyChart;

    function loadDailyChart(month, year) {
        $.get('{{ route("dashboard.chart.daily") }}', { month, year }, function(data) {
            updateDailyChart(data);
        });
    }

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
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#4361ee',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1c23',
                        titleFont: { family: 'Outfit' },
                        bodyFont: { family: 'Outfit' },
                        padding: 12,
                        borderRadius: 8
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { stepSize: 1 } 
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    $('#monthSelector').on('change', function() {
        loadDailyChart($(this).val(), {{ date('Y') }});
    });

    loadDailyChart({{ date('n') }}, {{ date('Y') }});
});
</script>
@endpush
@endsection