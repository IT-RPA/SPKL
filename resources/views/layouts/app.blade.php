<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPK Lembur System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-700: #1d4ed8;
            --primary-soft: #eff6ff;
            --secondary: #475569;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #0284c7;
            --background: #f3f6fb;
            --surface: #ffffff;
            --surface-soft: #f8fafc;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --sidebar-width: 276px;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.06);
            --shadow-md: 0 8px 24px rgba(15, 23, 42, 0.08);
            --transition: all 0.2s ease;
        }

        * { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        *::-webkit-scrollbar { width: 6px; height: 6px; }
        *::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }

        body {
            font-family: 'Outfit', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.08), transparent 34%),
                var(--background);
            color: var(--text-main);
            overflow-x: hidden;
            font-size: 14px;
        }

        .wrapper { display: flex; width: 100%; min-height: 100vh; }

        /* ERP Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--surface);
            color: var(--text-main);
            min-height: 100vh;
            position: fixed;
            z-index: 1000;
            border-right: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 18px 14px;
            transition: var(--transition);
        }

        .sidebar-header {
            height: 64px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px 16px;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 800;
            background: linear-gradient(135deg, #2563eb, #0f172a);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
        }

        .brand-name {
            display: flex;
            flex-direction: column;
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }
        .brand-name span {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .nav-content { height: calc(100vh - 106px); overflow-y: auto; padding-right: 4px; }
        .menu-header {
            color: #94a3b8;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin: 18px 10px 8px;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            min-height: 42px;
            padding: 10px 12px;
            color: #475569;
            border-radius: 10px;
            font-weight: 600;
            gap: 12px;
            transition: var(--transition);
            text-decoration: none;
        }
        .sidebar .nav-link i { width: 20px; color: #64748b; font-size: 1rem; text-align: center; }
        .sidebar .nav-link:hover { background: var(--surface-soft); color: var(--primary); }
        .sidebar .nav-link:hover i { color: var(--primary); }
        .sidebar .nav-link.active {
            background: var(--primary-soft);
            color: var(--primary);
            box-shadow: inset 3px 0 0 var(--primary);
        }
        .sidebar .nav-link.active i { color: var(--primary); }

        .sidebar .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 8px;
        }
        .sidebar .dropdown-item { border-radius: 8px; padding: 9px 12px; font-weight: 600; color: #475569; }
        .sidebar .dropdown-item:hover { background: var(--primary-soft); color: var(--primary); }
        .sidebar .dropdown-item i { width: 18px; margin-right: 8px; color: #64748b; }

        main {
            flex: 1;
            width: 100%;
            min-width: 0;
            padding: 22px;
            transition: var(--transition);
            overflow-x: hidden;
        }
        @media (min-width: 992px) {
            .wrapper.has-sidebar main {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
                max-width: calc(100vw - var(--sidebar-width));
            }
        }

        /* ERP Topbar */
        .top-navbar {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            padding: 12px 18px;
            margin-bottom: 22px;
            position: sticky;
            top: 12px;
            z-index: 900;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            max-width: 100%;
            overflow: hidden;
        }
        .top-navbar h5 {
            font-size: 1rem;
            letter-spacing: -0.01em;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-profile { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 6px 10px; border-radius: 12px; }
        .user-profile:hover { background: var(--surface-soft); }
        .user-avatar {
            width: 38px; height: 38px; border-radius: 11px;
            display: grid; place-items: center; color: #fff; font-weight: 800;
            background: linear-gradient(135deg, var(--primary), #0f172a);
        }

        .content-wrapper {
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            min-width: 0;
        }
        .container-fluid {
            max-width: 100%;
            overflow-x: hidden;
        }
        .card {
            min-width: 0;
        }
        .table-responsive,
        .dataTables_wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .dataTables_wrapper .row {
            margin-left: 0;
            margin-right: 0;
        }
        .dataTables_wrapper .row > * {
            min-width: 0;
        }
        .page-title { font-size: 1.6rem; font-weight: 800; letter-spacing: -0.03em; margin: 0; }
        .page-subtitle { color: var(--text-muted); margin: 4px 0 0; }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: var(--transition);
        }
        .card:hover { box-shadow: var(--shadow-md); }
        .card-header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 16px 18px; font-weight: 800; }

        .btn { border-radius: 10px; padding: 9px 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 8px 16px rgba(37,99,235,.16); }
        .btn-primary:hover { background: var(--primary-700); border-color: var(--primary-700); transform: translateY(-1px); }

        .table { margin-bottom: 0; border-collapse: collapse; }
        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 800;
            border-bottom: 1px solid var(--border);
            padding: 13px 16px;
            white-space: nowrap;
        }
        .table tbody td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .table tbody tr:hover { background: #f8fbff; }

        .status-badge {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 6px 10px; border-radius: 999px;
            font-size: .74rem; font-weight: 800; white-space: nowrap;
        }
        .status-badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
        .status-green { background: #dcfce7; color: #15803d; }
        .status-blue { background: #dbeafe; color: #1d4ed8; }
        .status-yellow { background: #fef3c7; color: #b45309; }
        .status-red { background: #fee2e2; color: #b91c1c; }
        .status-act { background: #fff7ed; color: #c2410c; box-shadow: inset 0 0 0 1px #fed7aa; }

        .mobile-header { display: none; }
        .mobile-nav { display: none; }

        @media (max-width: 991px) {
            body { background: #f6f8fc; }
            .sidebar { display: none; }
            .top-navbar { top: 0; border-radius: 0 0 18px 18px; margin: -16px -16px 18px; border-left: 0; border-right: 0; }
            main { padding: 16px; padding-bottom: 96px; }
            .page-title { font-size: 1.35rem; }
            .mobile-nav {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                position: fixed;
                left: 10px; right: 10px; bottom: 10px;
                background: rgba(255,255,255,.96);
                backdrop-filter: blur(16px);
                border: 1px solid var(--border);
                border-radius: 20px;
                box-shadow: 0 16px 40px rgba(15,23,42,.14);
                padding: 8px;
                z-index: 1100;
            }
            .mobile-nav-item { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; min-height:54px; border-radius:14px; color:#64748b; text-decoration:none; font-weight:700; }
            .mobile-nav-item.active { background: var(--primary-soft); color: var(--primary); }
            .mobile-nav-item i { font-size: 1.05rem; }
            .mobile-nav-item span { font-size: .68rem; }
        }

        .swal2-popup { border-radius: var(--radius-lg) !important; font-family: 'Outfit', sans-serif !important; }
        .select2-container--default .select2-selection--single {
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 10px !important;
        }
    </style>
</head>

<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="btn btn-link text-white p-0" id="sidebarToggle">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        <div class="fw-bold">SPK Lembur</div>
        @auth
        <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.7rem;">
            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
        </div>
        @endauth
    </div>

    <div class="wrapper {{ Auth::check() ? 'has-sidebar' : '' }}">

        @auth
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="brand-logo">L</div>
                <div class="brand-name">SPKL<span>System</span></div>
            </div>
            
            <div class="nav-content">
                <ul class="nav flex-column">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>

                    @if(Auth::user()->hasPermission('view-users') || Auth::user()->hasPermission('view-roles') || Auth::user()->hasPermission('view-permissions'))
                    <div class="menu-header">System</div>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-shield-alt"></i> Access Control
                        </a>
                        <ul class="dropdown-menu">
                            @permission('view-users')
                            <li><a class="dropdown-item" href="{{ route('users.index') }}"><i class="fas fa-users"></i> Users</a></li>
                            @endpermission
                            @permission('view-roles')
                            <li><a class="dropdown-item" href="{{ route('roles.index') }}"><i class="fas fa-user-shield"></i> Roles</a></li>
                            @endpermission
                            @permission('view-permissions')
                            <li><a class="dropdown-item" href="{{ route('permissions.index') }}"><i class="fas fa-key"></i> Permissions</a></li>
                            @endpermission
                        </ul>
                    </li>
                    @endif

                    @php
                        $masterPermissions = ['view-employees', 'view-departments', 'view-job-levels', 'view-flow-jobs', 'view-plant', 'view-process-types'];
                    @endphp

                    @if(collect($masterPermissions)->contains(fn($p) => Auth::user()->hasPermission($p)))
                    <div class="menu-header">Data Master</div>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-database"></i> Resources
                        </a>
                        <ul class="dropdown-menu">
                            @permission('view-employees')
                            <li><a class="dropdown-item" href="{{ route('employees.index') }}"><i class="fas fa-user-friends"></i> Karyawan</a></li>
                            @endpermission
                            @permission('view-departments')
                            <li><a class="dropdown-item" href="{{ route('departments.index') }}"><i class="fas fa-building"></i> Department</a></li>
                            @endpermission
                            @permission('view-job-levels')
                            <li><a class="dropdown-item" href="{{ route('job-levels.index') }}"><i class="fas fa-sitemap"></i> Job Level</a></li>
                            @endpermission
                            @permission('view-flow-jobs')
                            <li><a class="dropdown-item" href="{{ route('flow-jobs.index') }}"><i class="fas fa-stream"></i> Flow Job</a></li>
                            @endpermission
                            @permission('view-process-types')
                            <li><a class="dropdown-item" href="{{ route('process-types.index') }}"><i class="fas fa-cogs"></i> Tipe Proses</a></li>
                            @endpermission
                            @permission('view-plant')
                            <li><a class="dropdown-item" href="{{ route('plants.index') }}"><i class="fas fa-industry"></i> Plant</a></li>
                            @endpermission
                            <li><a class="dropdown-item" href="{{ route('master-activities.index') }}"><i class="fas fa-clock"></i> Master Activity</a></li>
                        </ul>
                    </li>
                    @endif

                    <div class="menu-header">Operation</div>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('overtime.index') }}">
                            <i class="fas fa-file-invoice"></i> Pengajuan Lembur
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('planning.index') }}">
                            <i class="fas fa-calendar-alt"></i> Planning Lembur
                            @php $pPlanning = $planningsPendingApproval ?? $pendingPlanningApproval ?? 0; @endphp
                            @if($pPlanning > 0)
                                <span class="badge bg-danger ms-2">{{ $pPlanning }}</span>
                            @endif
                        </a>
                    </li>

                    <div class="menu-header">Analytics</div>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('reports.overtime-leaderboard') }}">
                            <i class="fas fa-chart-line"></i> Leaderboard
                        </a>
                    </li>

                    <div class="menu-header">Approvals</div>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('approvals.index', ['job_level' => Auth::user()->jobLevel->code ?? '']) }}">
                            <i class="fas fa-check-double"></i> Tasks
                            @php 
                                $totalNotif = (isset($pendingApprovals) ? $pendingApprovals->count() : 0) + (isset($pendingPercentageCount) ? $pendingPercentageCount : 0) + (isset($pendingMyApproval) ? $pendingMyApproval : 0); 
                            @endphp
                            @if($totalNotif > 0)
                                <span class="badge bg-danger ms-2">{{ $totalNotif }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        @endauth

        <!-- Main Content -->
        <main>
            @auth
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 fw-bold">SPK Lembur System</h5>
                </div>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <div class="user-profile dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="d-none d-md-block text-truncate" style="max-width: 180px;">
                                <div class="fw-bold text-truncate" style="font-size: 0.9rem;">{{ Auth::user()->name }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.75rem;">{{ Auth::user()->jobLevel->name }}</div>
                            </div>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i> Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            @endauth

            <div class="content-wrapper">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" style="border-left: 4px solid #10b981 !important;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-3 text-success" style="font-size: 1.2rem;"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-left: 4px solid #ef4444 !important;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle me-3 text-danger" style="font-size: 1.2rem;"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-nav">
        <a href="{{ route('dashboard') }}" class="mobile-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-grid-2"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('overtime.index') }}" class="mobile-nav-item {{ request()->routeIs('overtime.*') ? 'active' : '' }}">
            <i class="fas fa-file-invoice"></i>
            <span>Overtime</span>
        </a>
        <a href="{{ route('planning.index') }}" class="mobile-nav-item {{ request()->routeIs('planning.*') ? 'active' : '' }}">
            <i class="fas fa-calendar-alt"></i>
            <span>Planning</span>
        </a>
        <a href="{{ route('approvals.index', ['job_level' => Auth::user()->jobLevel->code ?? '']) }}" class="mobile-nav-item {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
            <div class="position-relative">
                <i class="fas fa-check-circle"></i>
                @php 
                    $totalNotif = (isset($pendingApprovals) ? $pendingApprovals->count() : 0) + (isset($pendingPercentageCount) ? $pendingPercentageCount : 0) + (isset($pendingMyApproval) ? $pendingMyApproval : 0); 
                @endphp
                @if($totalNotif > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5rem; padding: 3px 5px;">{{ $totalNotif }}</span>
                @endif
            </div>
            <span>Tasks</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            const sidebar = $('#sidebar');
            const toggleBtn = $('#sidebarToggle');

            // Toggle sidebar for mobile
            toggleBtn.on('click', function() {
                sidebar.toggleClass('active');
            });

            // Active State Handling
            const currentUrl = window.location.href;
            $('.sidebar .nav-link, .sidebar .dropdown-item').each(function() {
                if (this.href === currentUrl) {
                    $(this).addClass('active');
                    // If it's a dropdown item, make the parent nav-link active too
                    if ($(this).hasClass('dropdown-item')) {
                        $(this).closest('.dropdown').find('.nav-link').addClass('active');
                    }
                }
            });

            // Close sidebar when clicking outside on mobile
            $(document).on('click', function(e) {
                if (window.innerWidth <= 991) {
                    if (!$(e.target).closest('#sidebar').length && !$(e.target).closest('#sidebarToggle').length) {
                        sidebar.removeClass('active');
                    }
                }
            });
        });
    </script>


    @stack('scripts')
</body>

</html>