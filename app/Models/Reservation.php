<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_id',
        'expert_id',
        'date',
        'start_time',
        'end_time',
        'reservation_number',
        'event',
        'type',
        'status',
        'reservation_amount',
        'payment_status',
        'payment_way',
        'delay_date',
        'notes',
        'reason_delay',
        'reason_cancle',
        'attachment'
    ];
    protected $casts = [
        'event' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->reservation_number = sprintf('%05d', static::max('id') + 1);
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function expert()
    {
        return $this->belongsTo(Expert::class, 'expert_id');
    }


    // public function services()
    // {
    //     return $this->belongsToMany(Service::class, 'service_reservations');
    // }
}
