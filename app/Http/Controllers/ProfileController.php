<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('profile.show', [
            'user' => request()->user()->load('profile', 'roles'),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->safe()->only('name'));
        $user->profile()->updateOrCreate([], $request->safe()->only('display_name', 'bio'));

        return back()->with('status', 'Perfil atualizado com sucesso.');
    }
}
