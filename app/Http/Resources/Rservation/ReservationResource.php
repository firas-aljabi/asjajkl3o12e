<?php

namespace App\Http\Resources\Rservation;

use App\Http\Resources\Expert\ExpertResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'amount_type' => $this->event,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'reservation_number' => $this->reservation_number,
            'type' => $this->type,
            'status' => $this->status,
            'reservation_amount' => $this->reservation_amount,
            'payment_status' => $this->payment_status,
            'payment_way' => $this->payment_way,
            'delay_date' => $this->delay_date,
            'notes' => $this->notes,
            'attachment' => asset($this->attachment),
            'expert' => ExpertResource::make($this->whenLoaded('expert')),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ];
            }),
        ];
    }
}
