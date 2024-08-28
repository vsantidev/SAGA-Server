<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'building',
        'capacity',
        'floor',
        'pmr',
        'description',
        'picture'
    ];

    // RELATION AVEC LA TABLE SITES
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // RELATION AVEC LA TABLE ANIMATIONS
    public function animations()
    {
        return $this->hasMany(Animation::class);
    }
}
