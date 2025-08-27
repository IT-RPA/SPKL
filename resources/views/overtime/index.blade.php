@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Daftar Pengajuan Lembur</h2>
    <a href="{{ route('overtime.create') }}" class="btn btn-primary">Buat Pengajuan Baru</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>No. SPK</th>
                        <th>Tanggal</th>
                        <th>Departemen</th>
                        <th>Tingkatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                    <tr>
                        <td>{{ $request->request_number }}</td>
                        <td>{{ $request->date->format('d/m/Y') }}</td>
                        <td>{{ $request->department->name }}</td>
                        <td>
                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $request->requester_level)) }}</span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $request->status_color }}">
                                @if($request->status_color == 'act')
                                    Act
                                @else
                                    {{ ucfirst($request->status) }}
                                @endif
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('overtime.show', $request) }}" class="btn btn-sm btn-outline-primary">
                                Detail
                            </a>
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
        
        <div class="d-flex justify-content-center">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection