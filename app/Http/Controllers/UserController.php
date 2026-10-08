<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\Stock\StockEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
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
}
