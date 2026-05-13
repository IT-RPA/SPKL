@extends('layouts.app')

@section('title', 'Master Activity')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Daftar Waktu Istirahat</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#activityModal">
                        <i class="fas fa-plus"></i> Tambah Aktivitas
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="activityTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Aktivitas</th>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activities as $index => $activity)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $activity->name }}</td>
                                    <td>{{ $activity->start_time ? \Carbon\Carbon::parse($activity->start_time)->format('H:i') : '-' }}</td>
                                    <td>{{ $activity->end_time ? \Carbon\Carbon::parse($activity->end_time)->format('H:i') : '-' }}</td>
                                    <td>{{ $activity->calculated_duration_minutes }} Menit</td>
                                    <td>
                                        <span class="badge {{ $activity->is_active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $activity->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td> 
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-warning edit-btn" 
                                                data-id="{{ $activity->id }}"
                                                data-name="{{ $activity->name }}"
                                                data-start_time="{{ $activity->start_time ? \Carbon\Carbon::parse($activity->start_time)->format('H:i') : '' }}"
                                                data-end_time="{{ $activity->end_time ? \Carbon\Carbon::parse($activity->end_time)->format('H:i') : '' }}"
                                                data-is_active="{{ $activity->is_active }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                                data-id="{{ $activity->id }}"
                                                data-name="{{ $activity->name }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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
<div class="modal fade" id="activityModal" tabindex="-1" role="dialog" aria-labelledby="activityModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityModalLabel">Tambah Aktivitas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activityForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="activity_id" name="activity_id">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Aktivitas (Misal: Istirahat Siang)</label>
                        <input type="text" class="form-control" id="name" name="name" required maxlength="255">
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Jam Mulai</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">Jam Selesai</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                            <div class="invalid-feedback"></div>
                        </div>
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
    let isEditMode = false;

    $('#activityTable').DataTable({
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
        }
    });

    $('#activityModal').on('show.bs.modal', function() {
        if (!isEditMode) {
            resetForm();
        }
        isEditMode = false;
    });

    function resetForm() {
        $('#activityForm')[0].reset();
        $('#activity_id').val('');
        $('#activityModalLabel').text('Tambah Aktivitas');
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#is_active').prop('checked', true);
    }

    $('button[data-bs-target="#activityModal"]').on('click', function() {
        isEditMode = false;
    });

    $(document).on('click', '.edit-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        isEditMode = true;
        
        const id = $(this).data('id');
        const name = $(this).data('name');
        const startTime = $(this).data('start_time');
        const endTime = $(this).data('end_time');
        const isActive = $(this).data('is_active');

        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        $('#activity_id').val(id);
        $('#name').val(name);
        $('#start_time').val(startTime);
        $('#end_time').val(endTime);
        $('#is_active').prop('checked', Boolean(Number(isActive)));
        $('#activityModalLabel').text('Edit Aktivitas');
        
        $('#activityModal').modal('show');
    });

    $('#activityForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#activity_id').val();
        const isEdit = id !== '';
        const url = isEdit ? `/master-activities/${id}` : '/master-activities';
        
        const formData = new FormData(this);
        if (isEdit) {
            formData.append('_method', 'PUT');
        }

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
                    $('#activityModal').modal('hide');
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

    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: `Aktivitas "${name}" akan dihapus permanen!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/master-activities/${id}`,
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan saat menghapus data.'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush
