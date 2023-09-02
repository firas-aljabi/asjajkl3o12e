<?php

namespace App\Http\Requests\Rservation;

use App\Statuses\ReservationEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'expert_id' => 'required|exists:experts,id',
            'start_time' => 'required',
            'end_time' => 'required',
            'date' => 'required|date_format:Y-m-d H:i:s',
            'event' => ['required', 'array'],
            'event.*' => ['required', Rule::in(ReservationEvent::$statuses)],
            // 'services' => 'required|array'
        ];
    }
}
