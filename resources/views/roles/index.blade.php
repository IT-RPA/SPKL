@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manajemen Roles</h2>
    <a href="{{ route('roles.create') }}" class="btn btn-primary">Tambah Role</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="rolesTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Display Name</th>
                        <th>Deskripsi</th>
                        <th>Permissions</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td></td> {{-- biar DataTables isi otomatis --}}
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->display_name }}</td>
                        <td>{{ $role->description ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $role->permissions->count() }} permissions</span>
                        </td>
                        <td>
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Yakin hapus role ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#rolesTable').DataTable({
        responsive: true,
        pageLength: 10,
        ordering: true,
        columnDefs: [{
            targets: 0, // Kolom No
            orderable: false,
            searchable: false
        }],
        language: {
            "sProcessing": "Sedang memproses...",
            "sLengthMenu": "Tampilkan _MENU_ entri",
            "sZeroRecords": "Tidak ada data yang sesuai",
            "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
            "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
            "sSearch": "Cari:",
            "oPaginate": {
                "sFirst": "Pertama",
                "sPrevious": "Sebelumnya",
                "sNext": "Selanjutnya",
                "sLast": "Terakhir"
            }
        }
    });

    // Auto numbering untuk kolom No
    table.on('order.dt search.dt', function () {
        table.column(0, { search:'applied', order:'applied' })
             .nodes()
             .each((cell, i) => cell.innerHTML = i + 1);
    }).draw();
});
</script>
@endpush