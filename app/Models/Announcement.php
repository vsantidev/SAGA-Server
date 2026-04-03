<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

    class Announcement extends Model 
    {
    
    use HasFactory;
    
    protected $fillable = [
        'message',
        'active',
        'order'
        ];

    public function scopeActive($query) {
        return $query->where('active', true)->orderBy('order');
    }

}