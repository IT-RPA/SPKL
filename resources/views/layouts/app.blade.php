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
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-yellow { background-color: #fff3cd; color: #856404; }
        .status-orange { background-color: #ffeaa7; color: #d63031; }
        .status-blue { background-color: #cce5ff; color: #0066cc; }
        .status-purple { background-color: #e6ccff; color: #6600cc; }
        .status-green { background-color: #d4edda; color: #155724; }
        .status-red { background-color: #f8d7da; color: #721c24; }
        .status-gray { background-color: #e2e3e5; color: #6c757d; }
        .status-act { background-color: #ffc107; color: #000; }
        
        /* Custom Sidebar Styles */
        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
        }
    </style>
</head>
<body>
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
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            @auth
            <!-- Sidebar hanya muncul ketika user sudah login -->
            <nav class="col-md-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <!-- Management Data -->
                        <div class="menu-header">Management Data</div>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cogs"></i> Management Data
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('users.index') }}">
                                    <i class="fas fa-users"></i> User
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('roles.index') }}">
                                    <i class="fas fa-user-tag"></i> Role
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('permissions.index') }}">
                                    <i class="fas fa-key"></i> Permission
                                </a></li>
                            </ul>
                        </li>
                        
                        <!-- Data Master -->
                        <div class="menu-header">Data Master</div>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-database"></i> Data Master
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('employees.index') }}">
                                    <i class="fas fa-user-friends"></i> Karyawan
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('departments.index') }}">
                                    <i class="fas fa-building"></i> Department
                                </a></li>
                                 <li><a class="dropdown-item" href="{{ route('job-levels.index') }}">
                                    <i class="fas fa-sitemap"></i> Level Jabatan
                                </a></li>
                                 <li><a class="dropdown-item" href="{{ route('flow-jobs.index') }}">
                                    <i class="fas fa-stream"></i> Flow Job
                                </a></li>
                            </ul>
                        </li>
                        
                        <!-- Pengajuan Lembur -->
                        <div class="menu-header">Pengajuan</div>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('overtime.index') }}">
                                <i class="fas fa-clock"></i> Pengajuan Lembur
                            </a>
                        </li>
                        
<!-- Approval Menu -->
<div class="menu-header">Approval</div>
@php
    // Get current employee data berdasarkan email yang login
    $currentEmployee = App\Models\Employee::with('jobLevel')
        ->where('email', Auth::user()->email)
        ->first();
    
    $showApprovalMenu = false;
    $availableApprovals = [];
    
    if ($currentEmployee && $currentEmployee->jobLevel) {
        // Cek apakah user memiliki approval berdasarkan job level code
        $jobLevelCode = $currentEmployee->jobLevel->code;
        
        // Cek approval yang tersedia untuk user ini
        $pendingApprovals = App\Models\OvertimeApproval::where('approver_employee_id', $currentEmployee->id)
            ->where('status', 'pending')
            ->with('overtimeRequest')
            ->get();
        
        // Group by step_name untuk menentukan menu yang perlu ditampilkan
        $approvalsByStep = $pendingApprovals->groupBy('step_name');
        
        if ($approvalsByStep->has('Approval Section Head')) {
            $availableApprovals['sect-head'] = 'Approval Section Head';
            $showApprovalMenu = true;
        }
        
        if ($approvalsByStep->has('Approval Department Head')) {
            $availableApprovals['dept-head'] = 'Approval Department Head';
            $showApprovalMenu = true;
        }

        // ✅ PERBAIKAN: Step name yang benar untuk Sub Division Head
        if ($approvalsByStep->has('Approval Sub Divisi')) {
            $availableApprovals['sub-div-head'] = 'Approval Sub Division Head';
            $showApprovalMenu = true;
        }
        
        if ($approvalsByStep->has('Approval Division Head')) {
            $availableApprovals['div-head'] = 'Approval Division Head';
            $showApprovalMenu = true;
        }
        
        if ($approvalsByStep->has('Approval HRD')) {
            $availableApprovals['hrd'] = 'Approval HRD';
            $showApprovalMenu = true;
        }
        
        // Juga tampilkan approval yang sudah diproses untuk history
        $processedApprovals = App\Models\OvertimeApproval::where('approver_employee_id', $currentEmployee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->with('overtimeRequest')
            ->exists();
            
        if ($processedApprovals && empty($availableApprovals)) {
            // Jika tidak ada pending tapi ada history, tetap tampilkan menu berdasarkan job level
            switch ($jobLevelCode) {
                case 'SECT':
                    $availableApprovals['sect-head'] = 'Approval Section Head';
                    break;
                case 'DEPT':
                    $availableApprovals['dept-head'] = 'Approval Department Head';
                    break;
                case 'SUBDIV':
                    $availableApprovals['sub-div-head'] = 'Approval Sub Division Head';
                    break;
                case 'DIV':
                    $availableApprovals['div-head'] = 'Approval Division Head';
                    break;
                case 'HRD':
                    $availableApprovals['hrd'] = 'Approval HRD';
                    break;
            }
            $showApprovalMenu = true;
        }
    }
@endphp

@if($showApprovalMenu)
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-check-circle"></i> Approval
        @if($pendingApprovals && $pendingApprovals->count() > 0)
            <span class="badge bg-danger ms-1">{{ $pendingApprovals->count() }}</span>
        @endif
    </a>
    <ul class="dropdown-menu">
        @if(isset($availableApprovals['sect-head']))
            <li><a class="dropdown-item" href="{{ route('approvals.sect-head') }}">
                <i class="fas fa-user-check"></i> {{ $availableApprovals['sect-head'] }}
                @php $sectPending = $pendingApprovals->where('step_name', 'Approval Section Head')->count(); @endphp
                @if($sectPending > 0)
                    <span class="badge bg-danger ms-1">{{ $sectPending }}</span>
                @endif
            </a></li>
        @endif
        
        @if(isset($availableApprovals['dept-head']))
            <li><a class="dropdown-item" href="{{ route('approvals.dept-head') }}">
                <i class="fas fa-user-tie"></i> {{ $availableApprovals['dept-head'] }}
                @php $deptPending = $pendingApprovals->where('step_name', 'Approval Department Head')->count(); @endphp
                @if($deptPending > 0)
                    <span class="badge bg-danger ms-1">{{ $deptPending }}</span>
                @endif
            </a></li>
        @endif

        {{-- ✅ PERBAIKAN: Route yang benar untuk Sub Division Head --}}
        @if(isset($availableApprovals['sub-div-head']))
            <li><a class="dropdown-item" href="{{ route('approvals.sub-div-head') }}">
                <i class="fas fa-user-cog"></i> {{ $availableApprovals['sub-div-head'] }}
                @php $subdivPending = $pendingApprovals->where('step_name', 'Approval Sub Divisi')->count(); @endphp
                @if($subdivPending > 0)
                    <span class="badge bg-danger ms-1">{{ $subdivPending }}</span>
                @endif
            </a></li>
        @endif
        
        @if(isset($availableApprovals['div-head']))
            <li><a class="dropdown-item" href="{{ route('approvals.div-head') }}">
                <i class="fas fa-user-graduate"></i> {{ $availableApprovals['div-head'] }}
                @php $divPending = $pendingApprovals->where('step_name', 'Approval Division Head')->count(); @endphp
                @if($divPending > 0)
                    <span class="badge bg-danger ms-1">{{ $divPending }}</span>
                @endif
            </a></li>
        @endif
        
        @if(isset($availableApprovals['hrd']))
            <li><a class="dropdown-item" href="{{ route('approvals.hrd') }}">
                <i class="fas fa-user-shield"></i> {{ $availableApprovals['hrd'] }}
                @php $hrdPending = $pendingApprovals->where('step_name', 'Approval HRD')->count(); @endphp
                @if($hrdPending > 0)
                    <span class="badge bg-danger ms-1">{{ $hrdPending }}</span>
                @endif
            </a></li>
        @endif
        
        @if(empty($availableApprovals))
            <li><span class="dropdown-item text-muted">
                <i class="fas fa-info-circle"></i> Tidak ada approval tersedia
            </span></li>
        @endif
    </ul>
</li>
@endif
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
        // Script untuk active state menu
        $(document).ready(function() {
            // Mendapatkan URL saat ini
            var currentUrl = window.location.href;
            
            // Menambahkan class active pada link yang sesuai
            $('.nav-link').each(function() {
                if (this.href === currentUrl) {
                    $(this).addClass('active');
                }
            });
            
            $('.dropdown-item').each(function() {
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