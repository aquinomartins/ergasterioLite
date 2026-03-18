<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\RoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@ergasterio-lite.local'],
            [
                'name' => 'Administrador Padrão',
                'password' => Hash::make('password123'),
            ]
        );

        $user->profile()->updateOrCreate([], [
            'display_name' => 'Administrador Padrão',
            'bio' => 'Conta administrativa inicial do Ergastério Lite.',
        ]);

        app(RoleService::class)->assign($user, 'admin');
    }
}
