<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lastname',
        'firstname',
        'birthday',
        'email',
        'password',
        'presentation',
        'picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // RELATION AVEC LA TABLE EVENEMENTS
    public function evenements()
    {
        return $this->belongsToMany(Evenement::class);
    }

    // RELATION AVEC LA TABLE ANIMATION
    public function animations()
    {
        return $this->belongsToMany(Animation::class);
    }

    // RELATION AVEC LA TABLE INSCRIPTION
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }
    
    // RELATION AVEC LA TABLE EVENEMENT_USERS
    public function evenement_users()
    {
        return $this->belongsToMany(Evenement::class);
    }
    
}
