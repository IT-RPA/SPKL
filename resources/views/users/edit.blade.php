@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit User</h2>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            
            <!-- Employee ID - Read Only -->
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="employee_id_display" class="form-label">Employee</label>
                        <input type="text" class="form-control bg-light" id="employee_id_display" 
                               value="{{ $employee->employee_id }} - {{ $employee->name }}" readonly>
                        <small class="text-muted">Employee ID tidak dapat diubah</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Username - Editable -->
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" 
                            id="username" name="username" value="{{ old('username', $user->username) }}" required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <!-- Password - Optional -->
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimal 8 karakter jika diisi</small>
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
                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
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
            <h5 class="mb-3 text-muted">
                <i class="fas fa-lock me-2"></i>Data dari Employee (Read-only)
            </h5>

            <div class="row">
                <!-- Nama Lengkap - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control bg-light" id="name" value="{{ $employee->name }}" readonly>
                        <small class="text-muted">Otomatis dari data employee</small>
                    </div>
                </div>

                <!-- Email - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control bg-light" id="email" value="{{ $employee->email }}" readonly>
                        <small class="text-muted">Otomatis dari data employee</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Department - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control bg-light" id="department" 
                               value="{{ $employee->department->name }}" readonly>
                    </div>
                </div>
                
                <!-- Job Level - Disabled -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="job_level" class="form-label">Job Level</label>
                        <input type="text" class="form-control bg-light" id="job_level" 
                               value="{{ $employee->jobLevel->name }} ({{ $employee->jobLevel->code }})" readonly>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi:</strong> Jika data employee (nama, email, department, job level) berubah, 
                data user akan <strong>otomatis diupdate</strong> mengikuti perubahan tersebut. Namun Role tetap sesuai pilihan manual Anda.
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Inisialisasi Select2 untuk role
        $('.select2-role').select2({
            width: '100%',
            placeholder: 'Pilih Role',
            allowClear: true
        });
    });
</script>
@endpush