<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    protected $fillable = ['name'];

    public function role(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permissions');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
