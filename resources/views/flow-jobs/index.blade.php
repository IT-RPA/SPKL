@extends('layouts.app')

@section('title', 'Manajemen Flow Job')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Flow Job</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#flowJobModal">
                        <i class="fas fa-plus"></i> Tambah Flow Job
                    </button>
                </div>
                <div class="card-body">
                    <!-- Filter by Department -->
                    <div class="mb-3">
                        <label for="departmentFilter" class="form-label">Filter Departemen:</label>
                        <select class="form-control" id="departmentFilter" style="width: 250px; display: inline-block;">
                            <option value="">Semua Departemen</option>
                            @foreach($departments as $department)
                            <option value="{{ $department->name }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="flowJobTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Departemen</th>
                                    <th>Urutan Step</th>
                                    <th>Nama Step</th>
                                    <th>Level Jabatan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $groupedFlows = $flowJobs->groupBy('department.name'); @endphp
                                @foreach($groupedFlows as $departmentName => $flows)
                                @foreach($flows as $index => $flowJob)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $departmentName }}
                                    </td>
                                    <td>
                                        <span class="badge text-dark">Step {{ $flowJob->step_order }}</span>
                                    </td>
                                    <td>{{ $flowJob->step_name }}</td>
                                    <td>{{ $flowJob->jobLevel->name }}</td>
                                    <td>
                                        <span class="badge {{$flowJob->is_active ? 'text-dark' : 'text-danger' }}">
                                            {{ $flowJob->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn"
                                            data-id="{{ $flowJob->id }}"
                                            data-department_id="{{ $flowJob->department_id }}"
                                            data-job_level_id="{{ $flowJob->job_level_id }}"
                                            data-step_order="{{ $flowJob->step_order }}"
                                            data-step_name="{{ $flowJob->step_name }}"
                                            data-is_active="{{ $flowJob->is_active }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn"
                                            data-id="{{ $flowJob->id }}"
                                            data-department="{{ $departmentName }}"
                                            data-step="{{ $flowJob->step_name }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
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
<div class="modal fade" id="flowJobModal" tabindex="-1" role="dialog" aria-labelledby="flowJobModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="flowJobModalLabel">Tambah Flow Job</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="flowJobForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="flow_job_id" name="flow_job_id">

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

                    <div class="mb-3">
                        <label for="step_order" class="form-label">Urutan Step</label>
                        <input type="number" class="form-control" id="step_order" name="step_order" required min="1">
                        <small class="form-text text-muted">Urutan step dalam flow approval (1, 2, 3, dst.)</small>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="step_name" class="form-label">Nama Step</label>
                        <input type="text" class="form-control" id="step_name" name="step_name" required maxlength="255">
                        <small class="form-text text-muted">Contoh: Pengajuan, Approval Sect Head, Approval Dept Head, dst.</small>
                        <div class="invalid-feedback"></div>
                    </div>

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

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">
                                Status Aktif
                            </label>
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
    var table = $('#flowJobTable').DataTable({
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
        },
        order: [
            [1, 'asc'],
            [2, 'asc']
        ] // Sort by department then step_order
    });

    // Department filter
    $('#departmentFilter').on('change', function() {
        var selectedDepartment = this.value;
        if (selectedDepartment === '') {
            table.column(1).search('').draw();
        } else {
            table.column(1).search(selectedDepartment).draw();
        }
    });

    // Reset modal hanya ketika bukan mode edit
    $('#flowJobModal').on('show.bs.modal', function() {
        if (!isEditMode) {
            resetForm();
        }
        // Reset flag setelah modal ditampilkan
        isEditMode = false;
    });

    // Fungsi untuk reset form
    function resetForm() {
        $('#flowJobForm')[0].reset();
        $('#flow_job_id').val('');
        $('#flowJobModalLabel').text('Tambah Flow Job');
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#is_active').prop('checked', true); // Set default checked
    }

    // Button tambah flow job - set flag untuk mode tambah
    $('button[data-bs-target="#flowJobModal"]').on('click', function() {
        isEditMode = false;
    });

    // Edit button click - set flag untuk mode edit
    $(document).on('click', '.edit-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Set flag bahwa ini mode edit
        isEditMode = true;
        
        const id = $(this).data('id');
        const departmentId = $(this).data('department_id');
        const jobLevelId = $(this).data('job_level_id');
        const stepOrder = $(this).data('step_order');
        const stepName = $(this).data('step_name');
        const isActive = $(this).data('is_active');

        // Clear validasi error terlebih dahulu
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Isi data untuk edit
        $('#flow_job_id').val(id);
        $('#department_id').val(departmentId);
        $('#job_level_id').val(jobLevelId);
        $('#step_order').val(stepOrder);
        $('#step_name').val(stepName);
        $('#is_active').prop('checked', Boolean(Number(isActive)));
        $('#flowJobModalLabel').text('Edit Flow Job');
        
        // Tampilkan modal
        $('#flowJobModal').modal('show');
    });

    // Form submission
    $('#flowJobForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#flow_job_id').val();
        const isEdit = id !== '';
        const url = isEdit ? `/flow-jobs/${id}` : '/flow-jobs';

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
                    $('#flowJobModal').modal('hide');
                    
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
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response?.message || 'Terjadi kesalahan saat menyimpan data.'
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
        const department = $(this).data('department');
        const step = $(this).data('step');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: `Flow Job "${step}" di departemen "${department}" akan dihapus permanen!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/flow-jobs/${id}`,
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