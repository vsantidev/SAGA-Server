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
        'remark',
        'registration',
        'url',
        'time',
        'other_time',
        'multiple',
        'fight',
        'reflection',
        'roleplay',
        'open_time',
        'closed_time',
        'capacity',
        'min_capacity',
        'user_id',
        'room_id',
        'evenement_id',
        'type_animation_id',
        'picture',
        'registration_date',
        'system',
        'time_slot_id',
    ];

    // L'animateur/auteur de l'animation — via user_id direct sur la table animations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // RELATION AVEC LA TABLE USERS
     public function users()
     {
        return $this->belongsToMany(User::class, 'inscriptions', 'animation_id', 'user_id');
     }

    // RELATION AVEC LA TABLE ROOMS
    public function rooms()
    {
       return $this->belongsTo(Room::class, 'room_id');
    }

    // RELATION AVEC LA TABLE EVENEMENTS
    public function evenements()
    {
        return $this->belongsTo(Evenement::class, 'evenement_id');
    }

    // RELATION AVEC LA TABLE INSCRIPTION
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    // RELATION AVEC LA TABLE TYPE_ANIMATIONS
    public function type_animation()
    {
        return $this->belongsTo(Type_animation::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

}
