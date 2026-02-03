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
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Status Colors */
        .status-yellow {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-orange {
            background-color: #ffeaa7;
            color: #d63031;
        }

        .status-blue {
            background-color: #cce5ff;
            color: #0066cc;
        }

        .status-purple {
            background-color: #e6ccff;
            color: #6600cc;
        }

        .status-green {
            background-color: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-red {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-gray {
            background-color: #e2e3e5;
            color: #6c757d;
        }

        .status-act {
            background-color: #ffc107;
            color: #000;
            border: 2px solid #fd7e14;
            animation: pulse-warning 2s infinite;
        }

        @keyframes pulse-warning {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
            }
        }

        /* Custom Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: #ecf0f1;
            margin: 1px 1px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .sidebar .nav-link.active {
            background-color: #3498db;
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        /* Dropdown Styles */
        .sidebar .dropdown-menu {
            background-color: #34495e;
            border: none;
            border-radius: 1px;
            margin-left: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100px;
        }

        .sidebar .dropdown-item {
            color: #bdc3c7;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .sidebar .dropdown-item:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .sidebar .dropdown-toggle::after {
            float: right;
            margin-top: 8px;
        }

        /* Menu Header Styles */
        .menu-header {
            color: #95a5a6;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 10px 10px 5px 10px;
            margin-top: 1px;
        }

        /* ===== MOBILE RESPONSIVE STYLES ===== */
        
        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #343a40;
            color: white;
            padding: 10px 15px;
            z-index: 1050;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .mobile-header .hamburger {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            padding: 5px;
        }

        .mobile-header .brand {
            font-weight: 600;
            font-size: 1rem;
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            /* Show mobile header */
            .mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* Hide desktop navbar */
            .navbar {
                display: none;
            }

            /* Sidebar mobile styles */
            .sidebar {
                position: fixed;
                top: 0;
                left: -300px;
                width: 300px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .sidebar.show {
                left: 0;
            }

            /* Sidebar dropdown improvements for mobile */
            .sidebar .dropdown-menu {
                position: static !important;
                transform: none !important;
                box-shadow: none;
                border: none;
                background-color: rgba(52, 73, 94, 0.8);
                margin: 0;
                padding: 0;
                border-radius: 0;
                width: 100%;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease, padding 0.3s ease;
            }

            .sidebar .dropdown.show .dropdown-menu {
                max-height: 300px;
                padding: 0.5rem 0;
            }

            .sidebar .dropdown-item {
                color: #bdc3c7 !important;
                padding: 8px 30px !important;
                transition: all 0.2s ease;
                border-left: 3px solid transparent;
            }

            .sidebar .dropdown-item:hover {
                background-color: rgba(52, 152, 219, 0.2) !important;
                color: #3498db !important;
                border-left-color: #3498db;
            }

            .sidebar .dropdown-toggle::after {
                transition: transform 0.2s ease;
            }

            .sidebar .dropdown.show .dropdown-toggle::after {
                transform: rotate(180deg);
            }

            .sidebar-overlay.show {
                display: block;
            }

            /* Main content adjustments */
            .container-fluid {
                padding: 0;
            }

            .container-fluid .row {
                margin: 0;
            }

            main {
                margin-top: 60px !important;
                padding: 15px !important;
                width: 100% !important;
                margin-left: 0 !important;
            }

            /* Cards responsive */
            .card {
                margin-bottom: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            /* Tables responsive */
            .table-responsive {
                border: none;
                margin-bottom: 1rem;
            }

            .table {
                font-size: 0.875rem;
            }

            .table th,
            .table td {
                padding: 0.5rem;
                white-space: nowrap;
            }

            /* Buttons responsive */
            .btn-group {
                display: flex;
                flex-wrap: wrap;
                gap: 0.25rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            /* Forms responsive */
            .form-control,
            .form-select {
                font-size: 16px; /* Prevent zoom on iOS */
            }

            /* Modal responsive */
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            /* Dashboard specific */
            .stats-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }

            .charts-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }

            /* Chart containers */
            .chart-canvas {
                height: 250px !important;
            }

            /* Horizontal scroll for charts */
            .chart-scroll {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }

            .chart-scroll .chart-canvas {
                min-width: 400px;
            }
        }

        /* Tablet responsive */
        @media (min-width: 769px) and (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .charts-grid {
                grid-template-columns: 1fr !important;
            }

            .info-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        /* Small mobile */
        @media (max-width: 480px) {
            .mobile-header {
                padding: 8px 12px;
            }

            .mobile-header .brand {
                font-size: 0.9rem;
            }

            main {
                padding: 10px !important;
            }

            .card-body {
                padding: 0.75rem;
            }

            .btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }

            .table {
                font-size: 0.8rem;
            }

            .table th,
            .table td {
                padding: 0.375rem;
            }
        }

        /* Report Leaderboard Styles - Tambahkan ini ke dalam <style> tag di layouts/app.blade.php */

        /* Report Leaderboard Styles */
        .card.border-left-primary {
            border-left: 4px solid #007bff;
        }

        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }

        .trophy-gold {
            color: #ffd700;
        }

        .trophy-silver {
            color: #c0c0c0;
        }

        .trophy-bronze {
            color: #cd7f32;
        }

        /* DataTable custom styling for leaderboard */
        #leaderboardTable tbody tr:nth-child(1) {
            background: linear-gradient(45deg, #fff3cd, #ffffff) !important;
            border-left: 4px solid #ffc107;
        }

        #leaderboardTable tbody tr:nth-child(2) {
            background: linear-gradient(45deg, #f8f9fa, #ffffff) !important;
            border-left: 4px solid #6c757d;
        }

        #leaderboardTable tbody tr:nth-child(3) {
            background: linear-gradient(45deg, #fff3cd, #ffffff) !important;
            border-left: 4px solid #fd7e14;
        }

        /* Hover effects */
        #leaderboardTable tbody tr:hover {
            background-color: #f8f9fa !important;
            transition: all 0.3s ease;
        }

        /* Modal customization */
        .modal-xl {
            max-width: 90%;
        }

        /* Badge animations */
        .badge {
            transition: all 0.3s ease;
        }

        .badge:hover {
            transform: scale(1.05);
        }

        /* Loading animation for buttons */
        .btn.loading {
            position: relative;
            pointer-events: none;
            color: transparent !important;
        }

        .btn.loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        .btn-success.loading::after {
            border-color: #ffffff;
            border-top-color: transparent;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Fix DataTable responsive issues */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
        }

        /* Additional DataTable fixes */
        .dataTables_wrapper {
            overflow-x: auto;
        }

        table.dataTable {
            width: 100% !important;
        }

        table.dataTable thead th,
        table.dataTable tbody td {
            white-space: nowrap;
        }

        /* Responsive table adjustments */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }

            .badge {
                font-size: 0.65rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.775rem;
            }
        }

        .select2-container--default .select2-selection--single {
            border: 1px solid #dee2e6 !important;
            border-radius: 0.375rem !important;
            height: 38px !important;
            padding: 0.375rem 0.75rem !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
            padding-right: 0 !important;
            line-height: 1.5 !important;
            color: #212529 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d !important;
        }

        /* Dropdown styling */
        .select2-container--default .select2-dropdown {
            border: 1px solid #dee2e6 !important;
            border-radius: 0.375rem !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #dee2e6 !important;
            border-radius: 0.375rem !important;
            padding: 0.375rem 0.75rem !important;
        }

        /* Hover dan Selected states */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd !important;
            color: white !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #e7f1ff !important;
            color: #0d6efd !important;
        }

        /* Focus state */
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }

        /* Invalid state untuk validation */
        .is-invalid~.select2-container--default .select2-selection--single {
            border-color: #dc3545 !important;
        }

        .is-invalid~.select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Width 100% */
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>

<body>
    <!-- Mobile Header (Hidden on Desktop) -->
    <div class="mobile-header">
        <button class="hamburger" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="brand">SPK Lembur System</div>
        @auth
        <div class="dropdown">
            <button class="btn btn-link text-white dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
        @endauth
    </div>

    <!-- Sidebar Overlay (Mobile Only) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Desktop Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="navbar-nav ms-auto">
                @auth
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            @auth
            <!-- Sidebar hanya muncul ketika user sudah login -->
            <nav class="col-md-2 d-md-block sidebar" id="sidebar">
                <div class="position-sticky pt-3">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>

                        <!-- Management Data - Cek permission untuk seluruh section -->
                        @if(Auth::user()->hasPermission('view-users') || Auth::user()->hasPermission('view-roles') || Auth::user()->hasPermission('view-permissions'))
                        <div class="menu-header">Management Data</div>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="fas fa-cogs"></i> Management Data
                            </a>
                            <ul class="dropdown-menu">
                                @permission('view-users')
                                <li><a class="dropdown-item" href="{{ route('users.index') }}">
                                        <i class="fas fa-users"></i> User
                                    </a></li>
                                @endpermission

                                @permission('view-roles')
                                <li><a class="dropdown-item" href="{{ route('roles.index') }}">
                                        <i class="fas fa-user-tag"></i> Role
                                    </a></li>
                                @endpermission

                                @permission('view-permissions')
                                <li><a class="dropdown-item" href="{{ route('permissions.index') }}">
                                        <i class="fas fa-key"></i> Permission
                                    </a></li>
                                @endpermission
                            </ul>
                        </li>
                        @endif

                        <!-- Data Master - Cek permission untuk masing-masing item -->
                        @php
                            $permissions = ['view-employees', 'view-departments', 'view-job-levels', 'view-flow-jobs', 'view-plant'];
                        @endphp

                        @if(collect($permissions)->contains(fn($p) => Auth::user()->hasPermission($p)))
                        <div class="menu-header">Data Master</div>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="fas fa-database"></i> Data Master
                            </a>
                            <ul class="dropdown-menu">
                                @permission('view-employees')
                                <li><a class="dropdown-item" href="{{ route('employees.index') }}">
                                        <i class="fas fa-user-friends"></i> Karyawan
                                    </a></li>
                                @endpermission

                                @permission('view-departments')
                                <li><a class="dropdown-item" href="{{ route('departments.index') }}">
                                        <i class="fas fa-building"></i> Department
                                    </a></li>
                                @endpermission

                                @permission('view-job-levels')
                                <li><a class="dropdown-item" href="{{ route('job-levels.index') }}">
                                        <i class="fas fa-sitemap"></i> Level Jabatan
                                    </a></li>
                                @endpermission

                                @permission('view-flow-jobs')
                                <li><a class="dropdown-item" href="{{ route('flow-jobs.index') }}">
                                        <i class="fas fa-stream"></i> Flow Job
                                    </a></li>
                                @endpermission

                                @permission('view-process-types')
                                <li><a class="dropdown-item" href="{{ route('process-types.index') }}">
                                        <i class="fas fa-cogs"></i> Tipe Proses
                                    </a></li>
                                @endpermission
                                @permission('view-plant')
                                                        <li><a class="dropdown-item" href="{{ route('plants.index') }}">
                                                                <i class="fas fa-building"></i> Plant
                                                            </a></li>
                                                        @endpermission
                                                    </ul>
                                                </li>
                                                @endif

                                                <!-- Pengajuan Lembur -->
                                                <div class="menu-header">Pengajuan</div>
                                                <li class="nav-item">
                                                    <a class="nav-link" href="{{ route('overtime.index') }}">
                                                        <i class="fas fa-clock"></i> Pengajuan Lembur
                                                    </a>
                                                </li>

                                                <div class="menu-header">Planning</div>
                                                <li class="nav-item">
                                                    <a class="nav-link" href="{{ route('planning.index') }}">
                                                        <i class="fas fa-calendar-alt"></i> Planning Lembur
                                                        @if(isset($pendingPlanningApproval) && $pendingPlanningApproval > 0)
                                                            <span class="badge bg-danger ms-2">{{ $pendingPlanningApproval }}</span>
                                                        @endif
                                                    </a>
                                                </li>

                                                <!-- Report -->
                                                <div class="menu-header">Report</div>
                                                <li class="nav-item">
                                                    <a class="nav-link" href="{{ route('reports.overtime-leaderboard') }}">
                                                        <i class="fas fa-chart-bar"></i> Report Lembur
                                                    </a>
                                                </li>


                                                <!-- Approval Menu - PERBAIKAN STEP NAMES -->
                                                <div class="menu-header">Approval</div>

                                                <li class="nav-item dropdown">
                                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-check-circle"></i> Approval
                                                        @php $totalNotif = $pendingApprovals->count() + $pendingPercentageCount; @endphp
                                                        @if($totalNotif > 0)
                                                            <span class="badge bg-danger ms-1">{{ $totalNotif }}</span>
                                                        @endif
                                                    </a>
                                                    <ul class="dropdown-menu" style="width: 100%;">
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('approvals.index', ['job_level' => Auth::user()->jobLevel->code]) }}">
                                                                <i class="fas fa-user-check"></i> {{ $currentEmployee->jobLevel->name }}
                                                                <span class="badge bg-danger ms-1">{{$totalNotif}}</span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </li>

                                            </ul>
                                        </div>
                                    </nav>

                                    <!-- Main content dengan lebar penuh ketika tidak ada sidebar -->
                                    <main class="@auth col-md-10 ms-sm-auto @else col-12 @endauth px-md-4">
                                @else
                <!-- Ketika user belum login, main content menggunakan container biasa -->
                <main class="col-12 px-md-4">
                    @endauth
                    <div class="pt-3">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Mobile sidebar toggle dengan perbaikan dropdown
        $(document).ready(function() {
            const sidebar = $('#sidebar');
            const overlay = $('#sidebarOverlay');
            const toggleBtn = $('#sidebarToggle');

            // Toggle sidebar
            toggleBtn.on('click', function() {
                sidebar.toggleClass('show');
                overlay.toggleClass('show');
            });

            // Close sidebar when clicking overlay
            overlay.on('click', function() {
                sidebar.removeClass('show');
                overlay.removeClass('show');
            });

            // Perbaikan dropdown di sidebar mobile
            if (window.innerWidth <= 768) {
                // Prevent dropdown from closing sidebar immediately
                $('.sidebar .dropdown-toggle').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const dropdown = $(this).closest('.dropdown');
                    const isOpen = dropdown.hasClass('show');
                    
                    // Close other dropdowns
                    $('.sidebar .dropdown').removeClass('show');
                    
                    // Toggle current dropdown
                    if (!isOpen) {
                        dropdown.addClass('show');
                    }
                });

                // Close sidebar only when clicking actual menu item (not dropdown toggle)
                $('.sidebar .dropdown-item').on('click', function() {
                    // Small delay to allow navigation
                    setTimeout(function() {
                        sidebar.removeClass('show');
                        overlay.removeClass('show');
                    }, 150);
                });

                // Close sidebar when clicking non-dropdown nav links
                $('.sidebar .nav-link:not(.dropdown-toggle)').on('click', function() {
                    setTimeout(function() {
                        sidebar.removeClass('show');
                        overlay.removeClass('show');
                    }, 150);
                });
            }

            // Handle window resize
            $(window).on('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.removeClass('show');
                    overlay.removeClass('show');
                    $('.sidebar .dropdown').removeClass('show');
                }
            });
        });

        // Script untuk active state menu
        $(document).ready(function () {
            // Mendapatkan URL saat ini
            var currentUrl = window.location.href;

            // Menambahkan class active pada link yang sesuai
            $('.nav-link').each(function () {
                if (this.href === currentUrl) {
                    $(this).addClass('active');
                }
            });

            $('.dropdown-item').each(function () {
                if (this.href === currentUrl) {
                    $(this).addClass('active');
                    $(this).closest('.dropdown').find('.dropdown-toggle').addClass('active');
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>