<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    // RELATION AVEC LA TABLE USER
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // RELATION AVEC LA TABLE ANIMATION
    public function animations()
    {
        return $this->hasMany(User::class);
    }


}
