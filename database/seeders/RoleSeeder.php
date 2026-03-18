<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['code' => 'user', 'name' => 'Usuário'],
            ['code' => 'artist', 'name' => 'Artista'],
            ['code' => 'admin', 'name' => 'Administrador'],
        ])->each(fn (array $role) => Role::query()->updateOrCreate(['code' => $role['code']], $role));
    }
}
