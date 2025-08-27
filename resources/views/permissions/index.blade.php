@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manajemen Permissions</h2>
    <a href="{{ route('permissions.create') }}" class="btn btn-primary">Tambah Permission</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="permissionsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Display Name</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $permission)
                    <tr>
                        <td></td> {{-- biar DataTables isi otomatis --}}
                        <td>{{ $permission->name }}</td>
                        <td>{{ $permission->display_name }}</td>
                        <td>{{ $permission->description ?? '-' }}</td>
                        <td>
                            <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('permissions.destroy', $permission) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Yakin hapus permission ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data</td>
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
    let table = $('#permissionsTable').DataTable({
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

    // Auto numbering kolom No
    table.on('order.dt search.dt', function () {
        table.column(0, { search:'applied', order:'applied' })
             .nodes()
             .each((cell, i) => cell.innerHTML = i + 1);
    }).draw();
});
</script>
@endpush
