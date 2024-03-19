<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type_animation extends Model
{
    use HasFactory;

    // RELATION AVEC LA TABLE ANIMATION
    public function animations()
    {
        return $this->hasMany(Animation::class);
    }
}
