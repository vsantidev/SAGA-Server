<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    // RELATION AVEC LA TABLE ROOMS
    public function rooms()
    {
        return $this->hasMany(Inscription::class);
    }

    // RELATION AVEC LA TABLE EVENEMENT
    public function evenements()
    {
        return $this->hasMany(Evenement::class);
    }
}
