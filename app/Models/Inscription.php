<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use HasFactory;

    // RELATION AVEC LA TABLE USER
    public function users()
    {
        return $this->hasOne(User::class);
    }
}
