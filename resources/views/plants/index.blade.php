@extends('layouts.app')

@section('title', 'Manajemen Tipe Plant')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Plant</h3>
                    @permission('create-plant')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#processTypeModal">
                        <i class="fas fa-plus"></i> Tambah Plant
                    </button>
                    @endpermission
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="processTypeTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Plant</th>
                                    <th>Deskripsi</th>
                                    @if(Auth::user()->hasPermission('edit-plant') || Auth::user()->hasPermission('delete-plant'))
                                    <th>Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($plants as $index => $plant)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $plant->name }}</td>
                                    <td>{{ $plant->description ?? '-' }}</td>
                                    @if(Auth::user()->hasPermission('edit-plant') || Auth::user()->hasPermission('delete-plant'))
                                    <td>
                                        <div class="btn-group" role="group">
                                            @permission('edit-plant')
                                            <button type="button" class="btn btn-sm btn-warning edit-btn"
                                                data-id="{{ $plant->id }}"
                                                data-name="{{ $plant->name }}"
                                                data-description="{{ $plant->description }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endpermission
                                            @permission('delete-plant')
                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-id="{{ $plant->id }}"
                                                data-name="{{ $plant->name }}">
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

@permission('create-plant')
<div class="modal fade" id="processTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processTypeModalLabel">Tambah Plant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="processTypeForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="plant_id">

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Plant</label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="100">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
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
                "sEmptyTable": "Tidak ada data yang tersedia pada tabel ini",
                "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
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
                }
            },
        });

        @permission('create-plant')
        $('#processTypeModal').on('show.bs.modal', function() {
            if (!isEditMode) {
                resetForm();
            }
            isEditMode = false;
        });

        function resetForm() {
            $('#processTypeForm')[0].reset();
            $('#plant_id').val('');
            $('#processTypeModalLabel').text('Tambah Plant');
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#is_active').prop('checked', true);
        }
        @endpermission

        @permission('edit-plant')
        $(document).on('click', '.edit-btn', function(e) {
            e.preventDefault();
            isEditMode = true;

            $('#plant_id').val($(this).data('id'));
            $('#code').val($(this).data('code'));
            $('#name').val($(this).data('name'));
            $('#description').val($(this).data('description') || '');
            $('#is_active').prop('checked', Boolean(Number($(this).data('is_active'))));
            $('#processTypeModalLabel').text('Edit Tipe Proses');

            $('#processTypeModal').modal('show');
        });
        @endpermission

        @if("Auth::user()->hasPermission('create-plant')" || "Auth::user()->hasPermission('edit-plant')")
        $('#processTypeForm').on('submit', function(e) {
            e.preventDefault();

            const id = $('#plant_id').val();
            const url = id ? `/plants/${id}` : '/plants';
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

        @permission('delete-plant')
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
                        url: `/plants/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
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