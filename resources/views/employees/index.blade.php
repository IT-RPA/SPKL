@extends('layouts.app')

@section('title', 'Manajemen Karyawan')

@section('content')
<style>
    /* Mobile responsive styles for employees */
    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
        }

        .card-header h3 {
            font-size: 1.25rem;
            margin: 0;
        }

        .table-responsive {
            border: none;
            font-size: 0.875rem;
        }

        .table th,
        .table td {
            padding: 0.5rem 0.25rem;
            white-space: nowrap;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }

        .badge {
            font-size: 0.65rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .btn-group .btn {
            width: 100%;
            margin: 0;
        }

        /* Hide less important columns on mobile */
        .table th:nth-child(1),
        .table td:nth-child(1),
        .table th:nth-child(4),
        .table td:nth-child(4),
        .table th:nth-child(6),
        .table td:nth-child(6) {
            display: none;
        }

        /* Modal responsive */
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        .modal-lg {
            max-width: calc(100% - 1rem);
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-body .row .col-md-6 {
            margin-bottom: 1rem;
        }
    }

    @media (max-width: 480px) {

        .table th:nth-child(5),
        .table td:nth-child(5) {
            display: none;
        }

        .btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    }

    /* Custom styling untuk Select2 agar sesuai dengan Bootstrap */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    /* Styling untuk error state */
    .select2-container--default.is-invalid .select2-selection--single {
        border-color: #dc3545;
    }

    /* Dropdown di dalam modal */
    .select2-container {
        width: 100% !important;
    }

    /* Fix Aksi Column Width */
    table td.aksi-col,
    table th.aksi-col {
        width: 130px !important;
        white-space: nowrap;
        text-align: center;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Karyawan</h3>
                    @permission('create-employees')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#employeeModal">
                        <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Tambah Karyawan</span>
                    </button>
                    @endpermission
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="employeeTable">
                            <thead>
                                <tr>
                                    <th class="d-none d-md-table-cell">No</th>
                                    <th>ID Karyawan</th>
                                    <th>Nama</th>
                                    <th class="d-none d-md-table-cell">Email</th>
                                    <th>Jenis Karyawan</th>
                                    <th class="d-none d-lg-table-cell">Departemen</th>
                                    <th class="d-none d-lg-table-cell">Level Jabatan</th>
                                    <th>Status</th>
                                    @if(Auth::user()->hasPermission('edit-employees') || Auth::user()->hasPermission('delete-employees'))
                                        <th class="aksi-col">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $index => $employee)
                                <tr>
                                    <td class="d-none d-md-table-cell">{{ $index + 1 }}</td>
                                    <td>{{ $employee->employee_id }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td class="d-none d-md-table-cell">{{ $employee->email }}</td>
                                    <td>{{ $employee->type }}</td>
                                    <td class="d-none d-lg-table-cell">{{ $employee->department->name }}</td>
                                    <td class="d-none d-lg-table-cell">{{ $employee->jobLevel->name }}</td>
                                    <td>
                                        <span class="badge {{ $employee->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    @if(Auth::user()->hasPermission('edit-employees') || Auth::user()->hasPermission('delete-employees'))
                                    <td class="aksi-col">
                                        <div class="btn-group" role="group">
                                            @permission('edit-employees')
                                            <button type="button" class="btn btn-sm btn-warning edit-btn"
                                                data-bs-toggle="modal" data-bs-target="#employeeModal"
                                                data-id="{{ $employee->id }}"
                                                data-employee_id="{{ $employee->employee_id }}"
                                                data-name="{{ $employee->name }}" data-email="{{ $employee->email }}"
                                                data-type="{{ $employee->type }}"
                                                data-department_id="{{ $employee->department_id }}"
                                                data-job_level_id="{{ $employee->job_level_id }}"
                                                data-plant_id="{{ $employee->plant_id }}"
                                                data-is_active="{{ $employee->is_active }}" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endpermission

                                            @permission('delete-employees')
                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-id="{{ $employee->id }}" data-name="{{ $employee->name }}"
                                                title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endpermission
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@permission('create-employees')
<!-- Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel">Tambah Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="employeeForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="employee_id_hidden" name="employee_id_hidden">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">ID Karyawan</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required
                                    maxlength="20">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="name" name="name" required maxlength="255">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Jenis Karyawan</label>
                                <select name="type" id="type" class="form-control" required>
                                    <option value="karyawan">Karyawan</option>
                                    <option value="pkl">PKL</option>
                                    <option value="harian_lepas">Harian Lepas</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Departemen</label>
                                <select class="form-control select2" id="department_id" name="department_id" required>
                                    <option value="">Pilih Departemen</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="job_level_id" class="form-label">Level Jabatan</label>
                                <select class="form-control select2" id="job_level_id" name="job_level_id" required>
                                    <option value="">Pilih Level Jabatan</option>
                                    @foreach($jobLevels as $jobLevel)
                                        <option value="{{ $jobLevel->id }}">{{ $jobLevel->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="plant_id" class="form-label">Pilih Plant</label>
                                <select class="form-control select2" id="plant_id" name="plant_id" required>
                                    <option value="">Pilih Plant</option>
                                    @foreach($plants as $plant)
                                        <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        value="1" checked>
                                    <label class="form-check-label" for="is_active">
                                        Status Aktif
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpermission
@endsection

@push('styles')
    <style>
        /* Mobile responsive styles for employees */
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }

            .card-header h3 {
                font-size: 1.25rem;
                margin: 0;
            }

            .table-responsive {
                border: none;
                font-size: 0.875rem;
            }

            .table th,
            .table td {
                padding: 0.5rem 0.25rem;
                white-space: nowrap;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .badge {
                font-size: 0.65rem;
            }

            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .btn-group .btn {
                width: 100%;
                margin: 0;
            }

            /* Hide less important columns on mobile */
            .table th:nth-child(1),
            .table td:nth-child(1),
            .table th:nth-child(4),
            .table td:nth-child(4),
            .table th:nth-child(6),
            .table td:nth-child(6) {
                display: none;
            }

            /* Modal responsive */
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-lg {
                max-width: calc(100% - 1rem);
            }

            .modal-body {
                padding: 1rem;
            }

            .modal-body .row .col-md-6 {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 480px) {

            .table th:nth-child(5),
            .table td:nth-child(5) {
                display: none;
            }

            .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
        }

        /* Custom styling untuk Select2 agar sesuai dengan Bootstrap */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        /* Styling untuk error state */
        .select2-container--default.is-invalid .select2-selection--single {
            border-color: #dc3545;
        }

        /* Dropdown di dalam modal */
        .select2-container {
            width: 100% !important;
        }

        /* Fix Aksi Column Width */
        table td.aksi-col,
        table th.aksi-col {
            width: 130px !important;
            white-space: nowrap;
            text-align: center;
        }
    </style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        // Variable untuk tracking apakah ini mode edit atau tambah
        let isEditMode = false;

        // DataTable initialization dengan bahasa Indonesia
        $('#employeeTable').DataTable({
            responsive: true,
            "pageLength": 10,
            "ordering": true,
            "searching": true,
            language: {
                "sEmptyTable": "Tidak ada data yang tersedia pada tabel ini",
                "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                "sInfoPostFix": "",
                "sInfoThousands": ".",
                "sLengthMenu": "Tampilkan _MENU_ entri",
                "sLoadingRecords": "Sedang memuat...",
                "sProcessing": "Sedang memproses...",
                "sSearch": "Cari:",
                "sZeroRecords": "Tidak ditemukan data yang sesuai",
                "oPaginate": {
                    "sFirst": "Pertama",
                    "sLast": "Terakhir",
                    "sNext": "Selanjutnya",
                    "sPrevious": "Sebelumnya"
                },
                "oAria": {
                    "sSortAscending": ": aktifkan untuk mengurutkan kolom naik",
                    "sSortDescending": ": aktifkan untuk mengurutkan kolom turun"
                }
            }
        });

        // Initialize Select2 dengan konfigurasi khusus untuk modal
        function initSelect2() {
            $('.select2').select2({
                dropdownParent: $('#employeeModal'),
                placeholder: function () {
                    return $(this).find('option:first').text();
                },
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function () {
                        return "Tidak ada hasil yang ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });
        }

        @permission('create-employees')
        // Reset modal hanya ketika bukan mode edit
        $('#employeeModal').on('show.bs.modal', function () {
            if (!isEditMode) {
                resetForm();
            }
            // Initialize Select2 setiap kali modal dibuka
            initSelect2();
            // Reset flag setelah modal ditampilkan
            isEditMode = false;
        });

        // Destroy Select2 saat modal ditutup untuk menghindari duplikasi
        $('#employeeModal').on('hidden.bs.modal', function () {
            $('.select2').select2('destroy');
        });

        // Fungsi untuk reset form
        function resetForm() {
            $('#employeeForm')[0].reset();
            $('#employee_id_hidden').val('');
            $('#employeeModalLabel').text('Tambah Karyawan');
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#is_active').prop('checked', true);
            $('#type').val('karyawan');

            // Reset Select2
            $('#department_id').val('').trigger('change');
            $('#job_level_id').val('').trigger('change');
            $('#plant_id').val('').trigger('change');
        }

        // Button tambah karyawan - set flag untuk mode tambah
        $('button[data-bs-target="#employeeModal"]').on('click', function () {
            isEditMode = false;
        });
        @endpermission

        @permission('edit-employees')
        // Edit button click - set flag untuk mode edit
        $(document).on('click', '.edit-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Set flag bahwa ini mode edit
            isEditMode = true;

            const id = $(this).data('id');
            const employeeId = $(this).data('employee_id');
            const name = $(this).data('name');
            const email = $(this).data('email');
            const departmentId = $(this).data('department_id');
            const jobLevelId = $(this).data('job_level_id');
            const plantId = $(this).data('plant_id');
            const phone = $(this).data('phone');
            const isActive = $(this).data('is_active');

            // Clear validasi error terlebih dahulu
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            // Isi data untuk edit
            $('#employee_id_hidden').val(id);
            $('#employee_id').val(employeeId);
            $('#name').val(name);
            $('#email').val(email);
            // Handle type
            const type = $(this).data('type');
            $('#type').val(type); // Set select value
            $('#is_active').prop('checked', Boolean(Number(isActive)));
            $('#employeeModalLabel').text('Edit Karyawan');

            // Set nilai Select2 - akan dijalankan setelah modal terbuka dan Select2 terinisialisasi
            setTimeout(function () {
                $('#department_id').val(departmentId).trigger('change');
                $('#job_level_id').val(jobLevelId).trigger('change');
                $('#plant_id').val(plantId).trigger('change');
            }, 100);

            // Tampilkan modal
            $('#employeeModal').modal('show');
        });
        @endpermission

        @if(Auth::user()->hasPermission('create-employees') || Auth::user()->hasPermission('edit-employees'))
            // Form submission
            $('#employeeForm').on('submit', function (e) {
                e.preventDefault();

                const id = $('#employee_id_hidden').val();
                const isEdit = id !== '';
                const url = isEdit ? `/employees/${id}` : '/employees';

                const formData = new FormData(this);
                if (isEdit) {
                    formData.append('_method', 'PUT');
                }

                // Reset error states
                $('.form-control').removeClass('is-invalid');
                $('.select2-container').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            $('#employeeModal').modal('hide');

                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;

                            Object.keys(errors).forEach(function (key) {
                                const element = $(`#${key}`);
                                element.addClass('is-invalid');

                                // Tambahkan class is-invalid ke Select2 container juga
                                if (element.hasClass('select2')) {
                                    element.next('.select2-container').addClass('is-invalid');
                                }

                                element.siblings('.invalid-feedback').text(errors[key][0]);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Terjadi kesalahan sistem. Silakan coba lagi.'
                            });
                        }
                    }
                });
            });
        @endif

        @permission('delete-employees')
        // Delete button click
        $(document).on('click', '.delete-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: `Karyawan "${name}" akan dihapus permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/employees/${id}`,
                        method: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function (xhr) {
                            const response = xhr.responseJSON;
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response?.message || 'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        });
        @endpermission
    });
</script>
@endpush