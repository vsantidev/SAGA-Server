<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'evenement_id',
        'name',
        'start_time',
        'end_time',
        'draw_status',
        'drawn_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'drawn_at'   => 'datetime',
    ];

    // RELATION AVEC LA TABLE EVENEMENTS
    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    // RELATION AVEC LA TABLE ANIMATIONS
    public function animations()
    {
        return $this->hasMany(Animation::class);
    }

    // RELATION AVEC LES INSCRIPTIONS (via animations)
    public function inscriptions()
    {
        return $this->hasManyThrough(Inscription::class, Animation::class, 'time_slot_id', 'animation_id');
    }
}