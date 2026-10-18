<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'animation_id',
        'weight',
        'status',
        'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'weight' => 'integer',
    ];

    // RELATION AVEC LA TABLE USER
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // RELATION AVEC LA TABLE ANIMATION
    public function animations()
    {
        return $this->belongsTo(Animation::class, 'animation_id');
    }
}
