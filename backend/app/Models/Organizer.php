<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organizer extends Model
{
    protected $fillable = ['public_name', 'bio', 'document'];

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $hidden = ['document'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

