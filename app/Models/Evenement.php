<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    protected $fillable = [
            'logo',
            'actif',
            'title',
            'subtitle',
            'content',
            'date_opening',
            'date_ending',
            'flag',
            'display',
            'attachment',
            'others',
            'announcement',
            'hide_announcement',
            'hide_animation',
            'url_event',
            'url_inscritpion'
        ];
        
    // RELATION AVEC LA TABLE USER
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // RELATION AVEC LA TABLE ANIMATION
    public function animations()
    {
        return $this->hasMany(User::class);
    }

    // RELATION AVEC LA TABLE SITE
    public function sites()
    {
        return $this->belongsTo(Site::class);
    }

    // RELATION AVEC LA TABLE EVENEMENT_USERS
    public function evenement_users()
    {
        return $this->belongsToMany(Evenement::class);
    }

}
