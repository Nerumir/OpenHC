<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->is_admin, 403);

        return Inertia::render('settings/Users', [
            'users'  => User::orderBy('id')->get(['id', 'name', 'email', 'can_edit', 'created_at']),
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        User::create($validated);

        return back()->with('status', 'user-created');
    }

    public function toggleEdit(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);
        abort_if($user->is_admin, 403);

        $user->update(['can_edit' => ! $user->can_edit]);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);
        abort_if($user->is_admin, 403);

        $user->delete();

        return back()->with('status', 'user-deleted');
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => $validated['password']]);

        return back()->with('status', 'password-updated');
    }
}
