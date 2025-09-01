@extends('layouts.app')

@section('title', 'Manajemen Departemen')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Departemen</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#departmentModal">
                        <i class="fas fa-plus"></i> Tambah Departemen
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="departmentTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Departemen</th>
                                    <th>Jumlah Karyawan</th>
                                    <th>Jumlah Flow</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($departments as $index => $department)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $department->code }}</td>
                                    <td>{{ $department->name }}</td>
                                    <td>{{ $department->active_employees_count }}</td>
                                    <td>{{ $department->flowJobs->count() }}</td>
                                    <td>
    <span class="badge {{ $department->is_active ? 'bg-success' : 'bg-danger' }}">
        {{ $department->is_active ? 'Aktif' : 'Nonaktif' }}
    </span>
                                    </td> 
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                                data-id="{{ $department->id }}"
                                                data-name="{{ $department->name }}"
                                                data-code="{{ $department->code }}"
                                                data-description="{{ $department->description }}"
                                                data-is_active="{{ $department->is_active }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-id="{{ $department->id }}"
                                                data-name="{{ $department->name }}">
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
<div class="modal fade" id="departmentModal" tabindex="-1" role="dialog" aria-labelledby="departmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalLabel">Tambah Departemen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="departmentForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="department_id" name="department_id">
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Departemen</label>
                        <input type="text" class="form-control" id="code" name="code" required maxlength="10">
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Departemen</label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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
    $('#departmentTable').DataTable({
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
    $('#departmentModal').on('show.bs.modal', function() {
        if (!isEditMode) {
            resetForm();
        }
        // Reset flag setelah modal ditampilkan
        isEditMode = false;
    });

    // Fungsi untuk reset form
    function resetForm() {
        $('#departmentForm')[0].reset();
        $('#department_id').val('');
        $('#departmentModalLabel').text('Tambah Departemen');
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#is_active').prop('checked', true); // Set default checked
    }

    // Button tambah departemen - set flag untuk mode tambah
    $('button[data-bs-target="#departmentModal"]').on('click', function() {
        isEditMode = false;
    });

    // Edit button click - set flag untuk mode edit
    $(document).on('click', '.edit-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Set flag bahwa ini mode edit
        isEditMode = true;
        
        const id = $(this).data('id');
        const name = $(this).data('name');
        const code = $(this).data('code');
        const description = $(this).data('description');
        const isActive = $(this).data('is_active');

        // Clear validasi error terlebih dahulu
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Isi data untuk edit
        $('#department_id').val(id);
        $('#name').val(name);
        $('#code').val(code);
        $('#description').val(description || '');
        $('#is_active').prop('checked', Boolean(Number(isActive)));
        $('#departmentModalLabel').text('Edit Departemen');
        
        // Tampilkan modal
        $('#departmentModal').modal('show');
    });

    // Form submission
    $('#departmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#department_id').val();
        const isEdit = id !== '';
        const url = isEdit ? `/departments/${id}` : '/departments';
        
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
                    $('#departmentModal').modal('hide');
                    
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
            text: `Departemen "${name}" akan dihapus permanen!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/departments/${id}`,
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