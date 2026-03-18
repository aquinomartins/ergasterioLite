<?php

namespace App\Models;

use App\Domain\Artists\Models\Artist;
use App\Domain\Identity\Models\Profile;
use App\Domain\Identity\Models\Role;
use App\Domain\Markets\Models\Market;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function createdMarkets(): HasMany
    {
        return $this->hasMany(Market::class, 'created_by');
    }

    public function artistProfiles(): HasMany
    {
        return $this->hasMany(Artist::class);
    }

    public function hasRole(string $code): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->code === $code);
    }
}
