@extends('layouts.app')

@section('title', 'Manajemen Tipe Proses')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Tipe Proses</h3>
                    @permission('create-process-types')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#processTypeModal">
                        <i class="fas fa-plus"></i> Tambah Tipe Proses
                    </button>
                    @endpermission
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="processTypeTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Tipe</th>
                                    <th>Deskripsi</th>
                                    <th>Digunakan</th>
                                    <th>Status</th>
                                    @if(Auth::user()->hasPermission('edit-process-types') || Auth::user()->hasPermission('delete-process-types'))
                                    <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($processTypes as $index => $type)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="badge bg-secondary">{{ $type->code }}</span></td>
                                    <td>{{ $type->name }}</td>
                                    <td>{{ $type->description ?? '-' }}</td>
                                    <td>{{ $type->overtime_details_count }} kali</td>
                                    <td>
                                        <span class="badge {{ $type->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $type->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    @if(Auth::user()->hasPermission('edit-process-types') || Auth::user()->hasPermission('delete-process-types'))
                                    <td>
                                        <div class="btn-group" role="group">
                                            @permission('edit-process-types')
                                            <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                                    data-id="{{ $type->id }}"
                                                    data-code="{{ $type->code }}"
                                                    data-name="{{ $type->name }}"
                                                    data-description="{{ $type->description }}"
                                                    data-is_active="{{ $type->is_active }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endpermission
                                            @permission('delete-process-types')
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                    data-id="{{ $type->id }}"
                                                    data-name="{{ $type->name }}">
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

@permission('create-process-types')
<div class="modal fade" id="processTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processTypeModalLabel">Tambah Tipe Proses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="processTypeForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="process_type_id">
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode</label>
                        <input type="text" class="form-control" id="code" name="code" required maxlength="20">
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Tipe Proses</label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="100">
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
                            <label class="form-check-label" for="is_active">Status Aktif</label>
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

@push('scripts')
<script>
$(document).ready(function() {
    let isEditMode = false;

    $('#processTypeTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        }
    });

    @permission('create-process-types')
    $('#processTypeModal').on('show.bs.modal', function() {
        if (!isEditMode) {
            resetForm();
        }
        isEditMode = false;
    });

    function resetForm() {
        $('#processTypeForm')[0].reset();
        $('#process_type_id').val('');
        $('#processTypeModalLabel').text('Tambah Tipe Proses');
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#is_active').prop('checked', true);
    }
    @endpermission

    @permission('edit-process-types')
    $(document).on('click', '.edit-btn', function(e) {
        e.preventDefault();
        isEditMode = true;
        
        $('#process_type_id').val($(this).data('id'));
        $('#code').val($(this).data('code'));
        $('#name').val($(this).data('name'));
        $('#description').val($(this).data('description') || '');
        $('#is_active').prop('checked', Boolean(Number($(this).data('is_active'))));
        $('#processTypeModalLabel').text('Edit Tipe Proses');
        
        $('#processTypeModal').modal('show');
    });
    @endpermission

    @if(Auth::user()->hasPermission('create-process-types') || Auth::user()->hasPermission('edit-process-types'))
    $('#processTypeForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#process_type_id').val();
        const url = id ? `/process-types/${id}` : '/process-types';
        const formData = new FormData(this);
        
        if (id) formData.append('_method', 'PUT');

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#processTypeModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        $(`#${key}`).addClass('is-invalid').siblings('.invalid-feedback').text(errors[key][0]);
                    });
                }
            }
        });
    });
    @endif

    @permission('delete-process-types')
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: `Tipe Proses "${name}" akan dihapus!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/process-types/${id}`,
                    method: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        Swal.fire('Terhapus!', response.message, 'success').then(() => location.reload());
                    }
                });
            }
        });
    });
    @endpermission
});
</script>
@endpush