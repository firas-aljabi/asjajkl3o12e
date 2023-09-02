<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function experts()
    {
        return $this->belongsToMany(Expert::class, 'service_experts');
    }
    public function reservation()
    {
        return $this->belongsToMany(Reservation::class, 'service_reservations');
    }
}
