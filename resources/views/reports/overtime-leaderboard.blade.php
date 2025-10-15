@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-trophy text-warning"></i> Leaderboard Lembur</h2>
            <p class="text-muted mb-0">Ranking karyawan berdasarkan total jam lembur</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filter Data</h5>
        </div>
        <div class="card-body">
            <form id="filterForm" method="GET">
                <!-- Quick Date Range Buttons -->
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary date-range-btn" data-range="today">Hari Ini</button>
                            <button type="button" class="btn btn-outline-secondary date-range-btn" data-range="this_week">Minggu Ini</button>
                            <button type="button" class="btn btn-outline-secondary date-range-btn" data-range="this_month">Bulan Ini</button>
                            <button type="button" class="btn btn-outline-secondary date-range-btn" data-range="last_month">Bulan Lalu</button>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Date Range -->
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" 
                               value="{{ $startDate }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" 
                               value="{{ $endDate }}">
                    </div>
                    
                    <!-- ✅ TAMBAHAN: Category Filter -->
                    <div class="col-md-2">
                        <label for="category_filter" class="form-label">
                            Jenis Lembur
                            <i class="fas fa-info-circle text-info" 
                               data-bs-toggle="tooltip" 
                               title="Planning = Lembur terencana dengan planning | Unplanned = Lembur mendadak"></i>
                        </label>
                        <select name="category_filter" id="category_filter" class="form-select">
                            <option value="all" {{ ($category_filter ?? 'all') == 'all' ? 'selected' : '' }}>Semua Jenis</option>
                            <option value="planned" {{ ($category_filter ?? '') == 'planned' ? 'selected' : '' }}>Planning</option>
                            <option value="unplanned" {{ ($category_filter ?? '') == 'unplanned' ? 'selected' : '' }}>Unplanned</option>
                        </select>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label for="status_filter" class="form-label">Status</label>
                        <select name="status_filter" id="status_filter" class="form-select">
                            <option value="completed" {{ $status_filter == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="in_progress" {{ $status_filter == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="realisasi" {{ $status_filter == 'realisasi' ? 'selected' : '' }}>Realisasi</option>
                            <option value="all" {{ $status_filter == 'all' ? 'selected' : '' }}>Semua Status</option>
                        </select>
                    </div>
                    
                    <!-- Department Filter -->
                    <div class="col-md-2">
                        <label for="department_id" class="form-label">Department</label>
                        <select name="department_id" id="department_id" class="form-select">
                            <option value="">Semua Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $department_id == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="row mt-3">
                    <div class="col-md-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('reports.overtime-leaderboard') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                        <button type="button" class="btn btn-success btn-sm" id="exportExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
                
                <!-- Status Legend -->
                <div class="row mt-3">
                    <div class="col-12">
                        <small class="text-muted">
                            <strong>Keterangan:</strong>
                            <span class="badge bg-primary ms-2">Planning</span> <em>Lembur terencana dengan planning</em> |
                            <span class="badge bg-warning ms-2">Unplanned</span> <em>Lembur mendadak tanpa planning</em> |
                            <span class="badge bg-success ms-2">Completed</span> <em>Sudah diapprove & data lengkap</em> |
                            <span class="badge bg-info ms-2">Realisasi</span> <em>Perlu input qty/persentase</em>
                        </small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Current Filter Info -->
    <div class="card mb-4">
        <div class="card-body bg-light">
            <div class="row">
                <div class="col-md-12">
                    <h6 class="mb-2"><i class="fas fa-info-circle"></i> Filter Aktif:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @if($startDate || $endDate)
                            <span class="badge bg-primary">
                                <i class="fas fa-calendar"></i> 
                                @if($startDate && $endDate)
                                    {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                                @elseif($startDate)
                                    Sejak {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                                @else
                                    Sampai {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                                @endif
                            </span>
                        @endif
                        
                        {{-- ✅ TAMBAHAN: Badge untuk Category Filter --}}
                        <span class="badge {{ ($category_filter ?? 'all') == 'all' ? 'bg-secondary' : (($category_filter ?? '') == 'planned' ? 'bg-primary' : 'bg-warning') }}">
                            <i class="fas fa-clipboard-list"></i> 
                            Jenis: {{ 
                                ($category_filter ?? 'all') == 'all' ? 'Semua' : 
                                (($category_filter ?? '') == 'planned' ? 'Planning' : 'Unplanned') 
                            }}
                        </span>
                        
                        <span class="badge bg-secondary">
                            <i class="fas fa-tasks"></i> 
                            Status: {{ ucfirst(str_replace('_', ' ', $status_filter ?? 'completed')) }}
                        </span>
                        
                        @if($department_id)
                            <span class="badge bg-info">
                                <i class="fas fa-building"></i> 
                                Dept: {{ $departments->firstWhere('id', $department_id)?->name ?? 'Unknown' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Sort Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Cari karyawan...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="entriesPerPage">
                        <option value="10">10 entries per page</option>
                        <option value="25" selected>25 entries per page</option>
                        <option value="50">50 entries per page</option>
                        <option value="100">100 entries per page</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="sortBy">
                        <option value="rank">Sort by Rank</option>
                        <option value="name">Sort by Name</option>
                        <option value="department">Sort by Department</option>
                        <option value="hours">Sort by Hours</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> Ranking Lembur
                    @if($startDate && $endDate)
                        - {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                    @endif
                </h5>
                <span class="badge bg-info">Total: <span id="totalEmployees">{{ count($employees) }}</span> karyawan</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="leaderboardTable" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%" data-sort="rank">
                                Rank <i class="fas fa-sort text-white-50"></i>
                            </th>
                            <th width="12%" data-sort="employee_id">
                                ID Karyawan <i class="fas fa-sort text-white-50"></i>
                            </th>
                            <th width="20%" data-sort="name">
                                Nama Karyawan <i class="fas fa-sort text-white-50"></i>
                            </th>
                            <th width="15%" data-sort="department">
                                Department <i class="fas fa-sort text-white-50"></i>
                            </th>
                            <th width="15%" data-sort="job_level">
                                Level Jabatan <i class="fas fa-sort text-white-50"></i>
                            </th>
                            <th width="15%" data-sort="hours">
                                Total Jam Lembur <i class="fas fa-sort text-white-50"></i>
                            </th>
                            <th width="10%" data-sort="requests">
                                Total SPK <i class="fas fa-sort text-white-50"></i>
                            </th>
                            <th width="8%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        @forelse($employees as $index => $employee)
                            <tr class="employee-row" 
                                data-rank="{{ $index + 1 }}"
                                data-employee-id="{{ strtolower($employee->employee_id) }}"
                                data-name="{{ strtolower($employee->name) }}"
                                data-department="{{ strtolower($employee->department) }}"
                                data-job-level="{{ strtolower($employee->job_level) }}"
                                data-hours="{{ $employee->total_hours }}"
                                data-requests="{{ $employee->total_requests }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($index == 0)
                                            <i class="fas fa-trophy text-warning fs-4 me-2"></i>
                                            <span class="badge bg-warning text-dark fw-bold">1</span>
                                        @elseif($index == 1)
                                            <i class="fas fa-medal text-secondary fs-5 me-2"></i>
                                            <span class="badge bg-secondary fw-bold">2</span>
                                        @elseif($index == 2)
                                            <i class="fas fa-award text-warning fs-5 me-2"></i>
                                            <span class="badge bg-warning text-dark fw-bold">3</span>
                                        @else
                                            <span class="badge bg-primary">{{ $index + 1 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">{{ $employee->employee_id }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <span class="text-white fw-bold">{{ strtoupper(substr($employee->name, 0, 1)) }}</span>
                                        </div>
                                        <span class="fw-medium">{{ $employee->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $employee->department }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $employee->job_level }}</span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <div class="fw-bold text-success fs-6">{{ $employee->formatted_time }}</div>
                                        <small class="text-muted">({{ $employee->total_hours }} jam)</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary fs-6">{{ $employee->total_requests }}</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="showEmployeeDetails({{ $employee->id }})"
                                            title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="noDataRow">
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>Tidak ada data lembur untuk filter yang dipilih</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <!-- Custom Pagination -->
                <div class="row mt-3">
                    <div class="col-sm-12 col-md-5">
                        <div id="tableInfo" class="dataTables_info">
                            Showing 1 to {{ min(25, count($employees)) }} of {{ count($employees) }} entries
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <div id="tablePagination" class="dataTables_paginate paging_simple_numbers">
                            <!-- Pagination will be generated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Details Modal -->
<div class="modal fade" id="employeeDetailsModal" tabindex="-1" aria-labelledby="employeeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="employeeDetailsModalLabel">
                    <i class="fas fa-user"></i> Detail Lembur Karyawan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="employeeInfo" class="mb-4"></div>
                <div class="table-responsive">
                    <table class="table table-striped" id="detailsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>No. SPK</th>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Durasi</th>
                                <th>Prioritas Pekerjaan</th>
                                <th>Proses Pekerjaan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="detailsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.table th[data-sort] {
    cursor: pointer;
    user-select: none;
}

.table th[data-sort]:hover {
    background-color: rgba(255,255,255,0.1);
}

.pagination .page-link {
    cursor: pointer;
}

#noResultsRow {
    display: none;
}

.badge {
    font-size: 0.75em;
}
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize custom table functionality
    initializeCustomTable();
    
    // Export Excel functionality (✅ TAMBAHAN: Include category_filter)
    $('#exportExcel').click(function() {
        var params = new URLSearchParams();
        params.append('start_date', $('#start_date').val());
        params.append('end_date', $('#end_date').val());
        params.append('category_filter', $('#category_filter').val());
        params.append('status_filter', $('#status_filter').val());
        params.append('department_id', $('#department_id').val());
        
        window.location.href = '{{ route("reports.export-excel") }}?' + params.toString();
    });
    
    // Quick date range buttons
    $('.date-range-btn').click(function() {
        var range = $(this).data('range');
        var today = new Date();
        var startDate, endDate;
        
        switch(range) {
            case 'today':
                startDate = endDate = today.toISOString().split('T')[0];
                break;
            case 'this_week':
                var monday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
                startDate = monday.toISOString().split('T')[0];
                endDate = new Date().toISOString().split('T')[0];
                break;
            case 'this_month':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                endDate = new Date().toISOString().split('T')[0];
                break;
            case 'last_month':
                var lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                startDate = lastMonth.toISOString().split('T')[0];
                endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
                break;
        }
        
        $('#start_date').val(startDate);
        $('#end_date').val(endDate);
    });
});

function initializeCustomTable() {
    let currentPage = 1;
    let entriesPerPage = 25;
    let filteredRows = [];
    let allRows = [];
    let currentSort = { column: 'rank', order: 'asc' };
    
    // Get all rows
    allRows = Array.from(document.querySelectorAll('.employee-row'));
    filteredRows = [...allRows];
    
    // Search functionality
    $('#searchInput').on('input', function() {
        const searchTerm = this.value.toLowerCase();
        filteredRows = allRows.filter(row => {
            const name = row.dataset.name || '';
            const employeeId = row.dataset.employeeId || '';
            const department = row.dataset.department || '';
            
            return name.includes(searchTerm) || 
                   employeeId.includes(searchTerm) || 
                   department.includes(searchTerm);
        });
        
        currentPage = 1;
        updateTable();
    });
    
    // Entries per page
    $('#entriesPerPage').on('change', function() {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        updateTable();
    });
    
    // Sort functionality
    $('th[data-sort]').on('click', function() {
        const column = $(this).data('sort');
        
        if (currentSort.column === column) {
            currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.order = 'asc';
        }
        
        sortRows();
        updateTable();
        updateSortIcons();
    });
    
    function sortRows() {
        filteredRows.sort((a, b) => {
            let valueA, valueB;
            
            switch (currentSort.column) {
                case 'rank':
                    valueA = parseInt(a.dataset.rank);
                    valueB = parseInt(b.dataset.rank);
                    break;
                case 'hours':
                    valueA = parseFloat(a.dataset.hours);
                    valueB = parseFloat(b.dataset.hours);
                    break;
                case 'requests':
                    valueA = parseInt(a.dataset.requests);
                    valueB = parseInt(b.dataset.requests);
                    break;
                default:
                    valueA = (a.dataset[currentSort.column] || '').toLowerCase();
                    valueB = (b.dataset[currentSort.column] || '').toLowerCase();
            }
            
            if (valueA < valueB) return currentSort.order === 'asc' ? -1 : 1;
            if (valueA > valueB) return currentSort.order === 'asc' ? 1 : -1;
            return 0;
        });
    }
    
    function updateSortIcons() {
        $('th[data-sort] i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
        const currentHeader = $(`th[data-sort="${currentSort.column}"] i`);
        currentHeader.removeClass('fa-sort').addClass(currentSort.order === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
    }
    
    function updateTable() {
        // Hide all rows
        allRows.forEach(row => row.style.display = 'none');
        
        // Show current page rows
        const startIndex = (currentPage - 1) * entriesPerPage;
        const endIndex = startIndex + entriesPerPage;
        const pageRows = filteredRows.slice(startIndex, endIndex);
        
        pageRows.forEach(row => row.style.display = '');
        
        // Update info
        const totalEntries = filteredRows.length;
        const showingStart = totalEntries > 0 ? startIndex + 1 : 0;
        const showingEnd = Math.min(endIndex, totalEntries);
        
        $('#tableInfo').text(`Showing ${showingStart} to ${showingEnd} of ${totalEntries} entries`);
        $('#totalEmployees').text(totalEntries);
        
        // Update pagination
        updatePagination();
        
        // Show/hide no results row
        if (totalEntries === 0) {
            $('#noDataRow').show();
        } else {
            $('#noDataRow').hide();
        }
    }
    
    function updatePagination() {
        const totalPages = Math.ceil(filteredRows.length / entriesPerPage);
        const pagination = $('#tablePagination');
        pagination.empty();
        
        if (totalPages <= 1) return;
        
        let paginationHTML = '<ul class="pagination">';
        
        // Previous button
        paginationHTML += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
        </li>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Next button
        paginationHTML += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
        </li>`;
        
        paginationHTML += '</ul>';
        pagination.html(paginationHTML);
        
        // Pagination click handlers
        pagination.on('click', '.page-link', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                currentPage = page;
                updateTable();
            }
        });
    }
    
    // Initial table setup
    updateTable();
}

function showEmployeeDetails(employeeId) {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const categoryFilter = $('#category_filter').val(); // ✅ TAMBAHAN
    const statusFilter = $('#status_filter').val();
    
    // Show loading state
    $('#employeeInfo').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>');
    $('#detailsTableBody').html('<tr><td colspan="9" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
    $('#employeeDetailsModal').modal('show');
    
    $.ajax({
        url: `{{ route('reports.employee-details', ':id') }}`.replace(':id', employeeId),
        method: 'GET',
        data: { 
            start_date: startDate,
            end_date: endDate,
            category_filter: categoryFilter, // ✅ TAMBAHAN
            status_filter: statusFilter
        },
        success: function(response) {
            // Fill employee info
            const employee = response.employee;
            const departmentName = employee.department ? employee.department.name : (employee.department_name || '-');
            const jobLevelName = employee.job_level ? employee.job_level.name : (employee.job_level_name || '-');
            
            $('#employeeInfo').html(`
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>ID Karyawan:</strong><br>
                                <span class="text-primary fs-5">${employee.employee_id}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Nama:</strong><br>
                                <span class="fs-5">${employee.name}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Department:</strong><br>
                                <span class="badge bg-info fs-6">${departmentName}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Level Jabatan:</strong><br>
                                <span class="badge bg-success fs-6">${jobLevelName}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            // Fill details table
            let tableBody = '';
            if (response.details && response.details.length > 0) {
                response.details.forEach(function(detail) {
                    // ✅ TAMBAHAN: Badge untuk jenis lembur
                    let categoryBadge = '';
                    if (detail.category === 'planned') {
                        categoryBadge = '<span class="badge bg-primary">Planning</span>';
                    } else {
                        categoryBadge = '<span class="badge bg-warning">Unplanned</span>';
                    }
                    
                    // Status badge
                    let statusBadge = '';
                    switch(detail.status) {
                        case 'completed':
                            statusBadge = '<span class="badge bg-success">Completed</span>';
                            break;
                        case 'approved':
                            statusBadge = '<span class="badge bg-info">Realisasi</span>';
                            break;
                        case 'approved_sect':
                        case 'approved_subdept':
                        case 'approved_dept':
                        case 'approved_subdiv':
                        case 'approved_div':
                        case 'pending':
                            statusBadge = '<span class="badge bg-warning">In Progress</span>';
                            break;
                        case 'rejected':
                            statusBadge = '<span class="badge bg-danger">Rejected</span>';
                            break;
                        default:
                            statusBadge = `<span class="badge bg-secondary">${detail.status}</span>`;
                    }
                    
                    tableBody += `
                        <tr>
                            <td><span class="fw-bold text-primary">${detail.spk_number}</span></td>
                            <td>${categoryBadge}</td>
                            <td>${detail.date}</td>
                            <td>${detail.start_time}</td>
                            <td>${detail.end_time}</td>
                            <td><span class="badge bg-warning text-dark">${detail.formatted_duration}</span></td>
                            <td>${detail.work_priority}</td>
                            <td>${detail.work_process}</td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                });
            } else {
                tableBody = `
                    <tr>
                        <td colspan="9" class="text-center text-muted">Tidak ada data lembur</td>
                    </tr>
                `;
            }
            
            $('#detailsTableBody').html(tableBody);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#employeeInfo').html('<div class="alert alert-danger">Gagal memuat informasi karyawan</div>');
            $('#detailsTableBody').html('<tr><td colspan="9" class="text-center text-danger">Gagal memuat data</td></tr>');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Gagal mengambil data detail karyawan: ' + error
                });
            } else {
                alert('Gagal mengambil data detail karyawan: ' + error);
            }
        }
    });
}
</script>
@endpush