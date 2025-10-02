@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Tambah User Baru</h2>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('users.store') }}" id="userForm">
            @csrf
            
            <div class="row">
                <!-- Employee ID - Select2 -->
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee <span class="text-danger">*</span></label>
                        <select class="form-select select2-employee @error('employee_id') is-invalid @enderror" 
                                id="employee_id" name="employee_id" required>
                            <option value="">Cari berdasarkan nama atau Employee ID...</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_id }}" {{ old('employee_id') == $employee->employee_id ? 'selected' : '' }}>
                                    {{ $employee->employee_id }} - {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Pilih employee yang akan dibuatkan akun user</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Username -->
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" 
                            id="username" name="username" value="{{ old('username') }}" required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Password -->
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>
                </div>

                <!-- Role - Editable -->
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select select2-role @error('role_id') is-invalid @enderror" 
                                id="role_id" name="role_id" required>
                            <option value="">Pilih Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3 text-muted">Data dari Employee (Read-only)</h5>

            <div class="row">
                <!-- Nama Lengkap - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control bg-light" id="name" readonly>
                        <small class="text-muted">Otomatis dari data employee</small>
                    </div>
                </div>

                <!-- Email - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control bg-light" id="email" readonly>
                        <small class="text-muted">Otomatis dari data employee</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Department - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control bg-light" id="department" readonly>
                    </div>
                </div>
                
                <!-- Job Level - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="job_level" class="form-label">Job Level</label>
                        <input type="text" class="form-control bg-light" id="job_level" readonly>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
    $(document).ready(function() {
        // Inisialisasi Select2 untuk employee
        $('.select2-employee').select2({
            width: '100%',
            placeholder: 'Cari berdasarkan nama atau Employee ID...',
            allowClear: true,
            ajax: {
                url: '{{ route("api.employees.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });

        // Inisialisasi Select2 untuk role
        $('.select2-role').select2({
            width: '100%',
            placeholder: 'Pilih Role',
            allowClear: true
        });

        // Event ketika employee dipilih
        $('#employee_id').on('change', function() {
            const employeeId = $(this).val();
            
            if (employeeId) {
                // Fetch employee data
                $.ajax({
                    url: `/api/employees/${employeeId}`,
                    type: 'GET',
                    success: function(data) {
                        // Fill readonly fields
                        $('#name').val(data.name);
                        $('#email').val(data.email);
                        $('#department').val(data.department);
                        $('#job_level').val(data.job_level);
                        
                        const username = data.name.toLowerCase()
                            .replace(/\s+/g, '.')
                            .replace(/[^a-z0-9.]/g, '');
                        $('#username').val(username);
                        
                        // Enable submit button
                        $('#submitBtn').prop('disabled', false);
                    },
                    error: function() {
                        alert('Gagal mengambil data employee');
                        // Clear fields
                        $('#name, #email, #department, #job_level, #username').val('');
                        $('#submitBtn').prop('disabled', true);
                    }
                });
            } else {
                // Clear all fields if no employee selected
                $('#name, #email, #department, #job_level, #username').val('');
                $('#submitBtn').prop('disabled', true);
            }
        });

        // Trigger change if there's old value
        @if(old('employee_id'))
            $('#employee_id').trigger('change');
        @endif
    });
</script>
@endpush