<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const PARTICIPANT = 'participante';
    public const ORGANIZER = 'organizador';
    public const ADMINISTRATOR = 'administrador';

    public const NAMES = [self::PARTICIPANT, self::ORGANIZER, self::ADMINISTRATOR];

    protected $fillable = ['name'];

    public function resource(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'permissions');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
