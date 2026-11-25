<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    use Notifiable;


    protected $fillable = ['name', 'email', 'cpf', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $with = ['role'];

    protected function casts(): array
    {
        return ['suspended' => 'boolean', 'password' => 'hashed'];
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string ...$names): bool
    {
        return in_array($this->role?->name, $names, true);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        // Preserve the existing API contract; authorization uses the Role relationship.
        $data['role'] = $this->role?->name;

        return $data;
    }

    public function organizer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Organizer::class);
    }

    public function campaigns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Campaign::class, 'owner_id');
    }

    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
