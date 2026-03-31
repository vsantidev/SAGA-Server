<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement_user extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id', 
        'evenement_id',
        'rewards',
        'masters',
        'winner_lot',
        'winner_lot_pos',
        ];

    // RELATION AVEC LA TABLE USERS
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // RELATION AVEC LA TABLE EVENEMENTS
    public function evenements()
    {
        return $this->belongsToMany(Evenement::class);
    }

    // RELATION DIRECTE POUR LE TIRAGE
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    }
