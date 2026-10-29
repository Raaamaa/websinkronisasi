<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('manage.users', [
            'users' => User::whereIn('role', ['admin', 'employee'])->orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'role' => ['required', 'in:admin,employee'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Akun berhasil dibuat.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin() || $user->is($request->user()), 403);

        $user->delete();

        return back()->with('status', 'Akun berhasil dihapus.');
    }
}
