<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UsersImport extends BaseImport
{
    protected function processRow(mixed $row): void
    {
        $name = $this->strCell($row, 'nama');
        $email = $this->strCell($row, 'email');
        $password = $this->strCell($row, 'password') ?? 'password';
        $role = $this->strCell($row, 'role') ?? 'gudang';

        $aktif = strtolower((string) $this->cell($row, 'aktif'));
        $isActive = in_array($aktif, ['1', 'true', 'yes', 'ya', 'aktif', 'active']);

        if (! in_array($aktif, ['1', '0', 'true', 'false', 'yes', 'no', 'ya', 'tidak', 'aktif', 'nonaktif', 'active', 'inactive'])) {
            throw ValidationException::withMessages(['aktif' => 'Kolom Aktif harus diisi 1 (aktif) atau 0 (nonaktif).']);
        }

        Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,gudang'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.in' => 'Role harus admin atau gudang.',
        ])->validate();

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => $isActive,
        ]);
    }
}