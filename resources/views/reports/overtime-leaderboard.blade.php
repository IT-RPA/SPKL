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
                <div class="row">
                    <div class="col-md-3">
                        <label for="year" class="form-label">Tahun</label>
                        <select name="year" id="year" class="form-select">
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="month" class="form-label">Bulan</label>
                        <select name="month" id="month" class="form-select">
                            <option value="">Semua Bulan</option>
                            @php
                                $months = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                            @endphp
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('reports.overtime-leaderboard') }}" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                        <button type="button" class="btn btn-success" id="exportExcel">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
            </form>
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
                    @if($month && $year)
                        - {{ $months[$month] ?? '' }} {{ $year }}
                    @elseif($year)
                        - {{ $year }}
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
                                        <p>Tidak ada data lembur untuk periode yang dipilih</p>
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
                                <th>Tanggal</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Durasi</th>
                                <th>Prioritas Pekerjaan</th>
                                <th>Proses Pekerjaan</th>
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
</style>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize custom table functionality
    initializeCustomTable();
    
    // Export Excel functionality
    $('#exportExcel').click(function() {
        var params = new URLSearchParams();
        params.append('year', $('#year').val());
        params.append('month', $('#month').val());
        params.append('department_id', $('#department_id').val());
        
        window.location.href = '{{ route("reports.export-excel") }}?' + params.toString();
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
    const year = $('#year').val();
    const month = $('#month').val();
    
    // Show loading state
    $('#employeeInfo').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>');
    $('#detailsTableBody').html('<tr><td colspan="7" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
    $('#employeeDetailsModal').modal('show');
    
    $.ajax({
        url: `{{ route('reports.employee-details', ':id') }}`.replace(':id', employeeId),
        method: 'GET',
        data: { year: year, month: month },
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

            // Fill details table - simple HTML only, no DataTables
            let tableBody = '';
            if (response.details && response.details.length > 0) {
                response.details.forEach(function(detail) {
                    tableBody += `
                        <tr>
                            <td><span class="fw-bold text-primary">${detail.spk_number}</span></td>
                            <td>${detail.date}</td>
                            <td>${detail.start_time}</td>
                            <td>${detail.end_time}</td>
                            <td><span class="badge bg-warning text-dark">${detail.formatted_duration}</span></td>
                            <td>${detail.work_priority}</td>
                            <td>${detail.work_process}</td>
                        </tr>
                    `;
                });
            } else {
                tableBody = `
                    <tr>
                        <td colspan="7" class="text-center text-muted">Tidak ada data lembur</td>
                    </tr>
                `;
            }
            
            $('#detailsTableBody').html(tableBody);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#employeeInfo').html('<div class="alert alert-danger">Gagal memuat informasi karyawan</div>');
            $('#detailsTableBody').html('<tr><td colspan="7" class="text-center text-danger">Gagal memuat data</td></tr>');
            
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