<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'animation_id'
    ];

    // RELATION AVEC LA TABLE USER
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // RELATION AVEC LA TABLE ANIMATION
    public function animations()
    {
        return $this->belongsToMany(Animation::class);
    }
}
