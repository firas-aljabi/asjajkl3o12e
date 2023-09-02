<?php

namespace App\Repository\Receiption;

use App\Events\NotificationEvent;
use App\Filter\Reservation\ReservationFilter;
use App\Filter\User\ClientFilter;
use App\Http\Trait\UploadImage;
use App\Models\Client;
use App\Models\Holiday;
use App\Models\Notification;
use App\Models\Reservation;
use App\Notifications\CopmeleteReservationMessageNotification;
use App\Repository\BaseRepositoryImplementation;
use App\Statuses\EventTypes;
use App\Statuses\HavePermission;
use App\Statuses\ReservationStatus;
use App\Statuses\ReservationType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class ReceiptionRepository extends BaseRepositoryImplementation
{
    use UploadImage;
    public function getFilterItems($filter)
    {

        $records = Reservation::query();

        if ($filter instanceof ReservationFilter) {

            $records->when(isset($filter->expert_id), function ($records) use ($filter) {
                $records->whereHas('expert', function ($q) use ($filter) {
                    return $q->where('id', $filter->getExperttId());
                });
            });

            $records->when(isset($filter->type), function ($query) use ($filter) {
                $query->where('type', $filter->getType());
            });

            $records->when(isset($filter->from_date), function ($query) use ($filter) {
                $query->where('date', $filter->getFromDate());
            });

            $records->when(isset($filter->to_date), function ($query) use ($filter) {
                $query->where('date', $filter->getToDate());
            });

            $records->when((isset($filter->from_date) && isset($filter->to_date)), function ($records) use ($filter) {
                $records->WhereBetween('date', [$filter->getFromDate(), $filter->getToDate()])
                    ->orWhereBetween('date', [$filter->getFromDate(), $filter->getToDate()]);
            });

            return  $records->with(['client', 'expert', 'expert.holidays'])->get();
        }
        return  $records->with(['client', 'expert', 'expert.holidays'])->get();
    }

    public function list_of_client($filter)
    {
        $records = Client::query();
        if ($filter instanceof ClientFilter) {
            return $records->get();
        }
        return $records->get();
    }


    public function create_client($data)
    {
        DB::beginTransaction();
        $existsClient = Client::where('email', $data['email'])->first();
        try {
            if (!$existsClient) {
                $client = Client::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                ]);

                $notification = Notification::create([
                   'user_id' => Auth::user()->id,
                   'notification_type' => EventTypes::CreateClient,
                   'title' => 'العميل ' . $client['name'] . ' تمت اضافته الى النظام من قبل الموظف ' . Auth::user()->name
                ]);

                event(new NotificationEvent($notification));
            }
            DB::commit();
            if ($client != null) {
                return $client;
            } else {
                return $client;
            }
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function create_reservation($data)
    {
        DB::beginTransaction();
        try {
            $existingReservation = Reservation::where('expert_id', $data['expert_id'])
                ->where('date', $data['date'])
                ->where(function ($query) use ($data) {
                    $query->where(function ($query) use ($data) {
                        $query->where('start_time', '>=', $data['start_time'])
                            ->where('start_time', '<', $data['end_time']);
                    })
                        ->orWhere(function ($query) use ($data) {
                            $query->where('end_time', '>', $data['start_time'])
                                ->where('end_time', '<=', $data['end_time']);
                        });
                })
                ->first();
            $holidays = Holiday::where('expert_id', $data['expert_id'])->get();

            if ($existingReservation) {
                DB::rollback();
                return "A reservation already exists for this date and time And This Expert";
            } elseif ($holidays->contains('date', $data['date'])) {
                DB::rollback();
                return "You Cannot Add New Reservation In This Date Because This Expert In Holiday,Please Choose Another Date.";
            } else {
                if ($data['start_time'] != $data['end_time'] && $data['end_time'] > $data['start_time']) {
                    $reservation = new Reservation();
                    $reservation->client_id = $data['client_id'];
                    $reservation->expert_id = $data['expert_id'];
                    $reservation->date = $data['date'];
                    $reservation->start_time = $data['start_time'];
                    $reservation->end_time = $data['end_time'];
                    $data['event'] = array_map('intval', $data['event']);
                    $reservation->event = $data['event'];
                    $reservation->type = ReservationType::UN_APPROVED;
                    $reservation->status = ReservationStatus::PENDING;
                    $reservation->save();
                    $notification = Notification::create([
                        'user_id' => Auth::user()->id,
                        'notification_type' => EventTypes::CreateReservation,
                        'title' => 'حجز جديد تمت اضافته في تاريخ' . $reservation->start_time . ' الى ' . ' من قبل الموظف/ة '. Auth::user()->name
                    ]);

                    event(new NotificationEvent($notification));
                    // $reservation->services()->attach($data['services']);
                } else {
                    DB::rollback();
                    return "Please Check Correct Time";
                }
            }
            DB::commit();

            return $reservation->load(['client', 'expert']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
    }

    public function complete_reservation($data)
    {
        DB::beginTransaction();
        try {
            $reservation = $this->updateById($data['reservation_id'], $data);

            if (Arr::has($data, 'attachment')) {
                $file = Arr::get($data, 'attachment');
                $file_name = $this->uploadReservationAttachment($file);
                $reservation->attachment = $file_name;
            }

            if ($reservation->type = ReservationType::UN_APPROVED) {
                $reservation->type = ReservationType::APPROVED;

            }

            $reservation->save();

            DB::commit();
            if ($reservation === null) {
                return response()->json(['message' => "Reservation was not Updated"]);
            }
            $notification = Notification::create([
                'user_id' => Auth::user()->id,
                'notification_type' => EventTypes::CompleteReservation,
                'title' => 'الحجز رقم ' . $reservation->id . ' تم تثبيته من قبل الموظف/ة ' . Auth::user()->name
            ]);

            event(new NotificationEvent($notification));
            return $reservation->load('expert', 'client');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return response()->json(['message' => $e->getMessage()]);
        }
    }
    public function cancle_reservation($data)
    {
        DB::beginTransaction();
        try {
            $reservation = $this->getById($data['reservation_id']);

            $reservation->update([
                'status' => ReservationStatus::CANCELED,
                'reason_cancle' => $data['reason_cancle']
            ]);

            DB::commit();

            if ($reservation === null) {
                return response()->json(['message' => "Reservation was not Cancled"]);
            }
            $notification = Notification::create([
                'user_id' => Auth::user()->id,
                'notification_type' => EventTypes::CancelReservation,
                'title' => ' الحجز رقم ' . $reservation->id . ' تم الفاءه من قبل الموطف/ة  ' .  Auth::user()->name
            ]);

            event(new NotificationEvent($notification));
            return $reservation->load('expert', 'client');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return response()->json(['message' => $e->getMessage()]);
        }
    }
    public function delay_reservation($data)
    {
        DB::beginTransaction();
        try {
            $reservation = $this->getById($data['reservation_id']);
            if (isset($data['delay_date'])) {
                $reservation->update([
                    'date' => $data['delay_date'],
                    'delay_date' => $data['delay_date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'reason_delay' => $data['reason_delay'],
                    'status' => ReservationStatus::DELAYED,
                ]);
            } else {
                $reservation->update([
                    'reason_delay' => $data['reason_delay'],
                    'status' => ReservationStatus::DELAYED,
                ]);
            }
            $reservation->save();
            DB::commit();
            if ($reservation === null) {
                return response()->json(['message' => "Reservation was not Delayed"]);
            }
            $notification = Notification::create([
                'user_id' => Auth::user()->id,
                'notification_type' => EventTypes::DelayReservation,
                'title' => ' تم تأخير الحجز رقم ' . $reservation->id . ' الى تاريخ  ' . $reservation->delay_date. Auth::user()->name
            ]);

            event(new NotificationEvent($notification));
            return $reservation->load('expert', 'client');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return response()->json(['message' => $e->getMessage()]);
        }
    }


    public function model()
    {
        return Reservation::class;
    }
}
