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
        'is_orga',
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

    // Nom affiché : « Élodie LEFÈVRE », « Jean-Christophe LEFÈVRE ». Majuscule à chaque partie
    // du prénom (après un tiret ou une espace), nom en majuscules, accents compris
    public function getDisplayNameAttribute(): string
    {
        return self::formatDisplayName($this->firstname, $this->lastname);
    }

    // Même règle pour l'accueil, qui lit le prénom et le nom dans sa requête
    public static function formatDisplayName(?string $firstname, ?string $lastname): string
    {
        return mb_convert_case(mb_strtolower((string) $firstname, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
            . ' ' . mb_strtoupper((string) $lastname, 'UTF-8');
    }

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
