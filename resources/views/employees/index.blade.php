@extends('layouts.app')

@section('title', 'Manajemen Karyawan')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Karyawan</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal">
                        <i class="fas fa-plus"></i> Tambah Karyawan
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="employeeTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>ID Karyawan</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Departemen</th>
                                    <th>Level Jabatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($employees as $index => $employee)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $employee->employee_id }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->email }}</td>
                                    <td>{{ $employee->department->name }}</td>
                                    <td>{{ $employee->jobLevel->name }}</td>
                                    <td>
 <span class="badge {{ $employee->is_active ? 'bg-success' : 'bg-danger' }}">
        {{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}
    </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                                data-id="{{ $employee->id }}"
                                                data-employee_id="{{ $employee->employee_id }}"
                                                data-name="{{ $employee->name }}"
                                                data-email="{{ $employee->email }}"
                                                data-department_id="{{ $employee->department_id }}"
                                                data-job_level_id="{{ $employee->job_level_id }}"
                                                data-is_active="{{ $employee->is_active }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-id="{{ $employee->id }}"
                                                data-name="{{ $employee->name }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
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

<!-- Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel" aria-hidden="true">
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
                                <input type="text" class="form-control" id="employee_id" name="employee_id" required maxlength="20">
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
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="department_id" class="form-label">Departemen</label>
                                <select class="form-control" id="department_id" name="department_id" required>
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
                                <select class="form-control" id="job_level_id" name="job_level_id" required>
                                    <option value="">Pilih Level Jabatan</option>
                                    @foreach($jobLevels as $jobLevel)
                                    <option value="{{ $jobLevel->id }}">{{ $jobLevel->name }}</option>
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
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Variable untuk tracking apakah ini mode edit atau tambah
    let isEditMode = false;

    // DataTable initialization dengan bahasa Indonesia tanpa CORS
    $('#employeeTable').DataTable({
        responsive: true,
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

    // Reset modal hanya ketika bukan mode edit
    $('#employeeModal').on('show.bs.modal', function() {
        if (!isEditMode) {
            resetForm();
        }
        // Reset flag setelah modal ditampilkan
        isEditMode = false;
    });

    // Fungsi untuk reset form
    function resetForm() {
        $('#employeeForm')[0].reset();
        $('#employee_id_hidden').val('');
        $('#employeeModalLabel').text('Tambah Karyawan');
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#is_active').prop('checked', true); // Set default checked
    }

    // Button tambah karyawan - set flag untuk mode tambah
    $('button[data-bs-target="#employeeModal"]').on('click', function() {
        isEditMode = false;
    });

    // Edit button click - set flag untuk mode edit
    $(document).on('click', '.edit-btn', function(e) {
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
        const isActive = $(this).data('is_active');

        // Clear validasi error terlebih dahulu
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Isi data untuk edit
        $('#employee_id_hidden').val(id);
        $('#employee_id').val(employeeId);
        $('#name').val(name);
        $('#email').val(email);
        $('#department_id').val(departmentId);
        $('#job_level_id').val(jobLevelId);
        $('#is_active').prop('checked', Boolean(Number(isActive)));
        $('#employeeModalLabel').text('Edit Karyawan');
        
        // Tampilkan modal
        $('#employeeModal').modal('show');
    });

    // Form submission
    $('#employeeForm').on('submit', function(e) {
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
        $('.invalid-feedback').text('');

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
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
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    
                    Object.keys(errors).forEach(function(key) {
                        $(`#${key}`).addClass('is-invalid');
                        $(`#${key}`).siblings('.invalid-feedback').text(errors[key][0]);
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

    // Delete button click
    $(document).on('click', '.delete-btn', function(e) {
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
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
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
                    error: function(xhr) {
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
});
</script>
@endpush