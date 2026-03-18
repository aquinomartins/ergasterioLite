<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RoleService $roleService): RedirectResponse
    {
        $user = User::query()->create($request->validated());
        $user->profile()->create([
            'display_name' => $user->name,
        ]);

        $roleService->assign($user, 'user');

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
