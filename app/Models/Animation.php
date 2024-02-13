<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'fight',
        'reflection',
        'roleplay',
        'open_time',
        'closed_time',
        'capacity',
        'user_id',
        'room_id',
        'evenement_id',
        'type_animation_id'
    ];


    // RELATION AVEC LA TABLE USERS
     public function users()
     {
        return $this->belongsToMany(User::class);
     }

    // RELATION AVEC LA TABLE ROOMS
    public function rooms()
    {
       return $this->belongsTo(Room::class);
    }

    // RELATION AVEC LA TABLE EVENEMENTS
    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    // RELATION AVEC LA TABLE INSCRIPTION
    public function inscriptions()
    {
        return $this->belongsToMany(Inscription::class);
    }

}
