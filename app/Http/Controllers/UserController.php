<?php

namespace App\Http\Controllers;

use App\Exports\Templates\UsersTemplate;
use App\Exports\UsersExport;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\UserRequest;
use App\Imports\UsersImport;
use App\Models\User;
use App\Services\Stock\StockEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    use HandlesExcelImport;

    public function __construct(protected StockEngine $engine)
    {
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);

        $this->engine->writeAudit('Create User', 'Users', null, null, ['id' => $user->id, 'name' => $user->name, 'role' => $user->role]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(UserRequest $request, User $user)
    {
        $before = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'is_active' => $user->is_active];

        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        $this->engine->writeAudit('Update User', 'Users', null, $before, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'is_active' => $user->is_active]);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu Admin.');
        }

        $name = $user->name;
        $user->delete();

        $this->engine->writeAudit('Delete User', 'Users', null, ['name' => $name], null);

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        return Excel::download(
            new UsersExport($search),
            'users-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(new UsersTemplate(), 'template-users.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $import = new UsersImport($request->user());
        $error = $this->importFile($import, $request->file('file'));

        if ($error) {
            return redirect()->back()->with('error', $error);
        }

        return $this->importRedirect($import, "Import user selesai: {$import->successCount} user baru dibuat.");
    }
}