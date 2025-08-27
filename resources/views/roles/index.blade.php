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
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
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
