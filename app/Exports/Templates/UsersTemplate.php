<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UsersTemplate implements Export, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new GenericTemplateSheet('Data', ['Nama', 'Email', 'Password', 'Role', 'Aktif'], [
                ['Contoh User', 'user@example.com', 'password', 'gudang', 1],
                ['Contoh Admin', 'admin@example.com', 'password', 'admin', 1],
            ]),
            new TemplateNotesSheet([
                'Baris pertama adalah header — jangan diubah atau dihapus.',
                'Nama: nama lengkap user, wajib diisi.',
                'Email: email unik — email yang sudah terdaftar akan gagal.',
                'Password: minimal 8 karakter. Jika dikosongkan, password default "password" akan digunakan.',
                'Role: "admin" (akses penuh) atau "gudang" (input stok).',
                'Aktif: 1 (aktif) atau 0 (nonaktif).',
                'User yang dibuat otomatis tercatat di Audit Log.',
            ]),
        ];
    }
}