<?php

namespace App\Http\Trait;

use Illuminate\Support\Str;

trait UploadImage
{
    public function uploadTransferAttachment($file)
    {
        $filename = date('Y-m-d') . '-Transfer-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('trasfers'), $filename);
        $path = 'trasfers/' . $filename;
        return $path;
    }
    public function uploadReservationAttachment($file)
    {
        $filename = date('Y-m-d') . '-Reservation-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('reservations'), $filename);
        $path = 'reservations/' . $filename;
        return $path;
    }

    public function uploadExpertImage($file)
    {
        $filename = date('Y-m-d') . '-Expert-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('images'), $filename);
        $path = 'images/' . $filename;
        return $path;
    }
}