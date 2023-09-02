<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expert extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'position',
        'image',
    ];
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_experts');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
