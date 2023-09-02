<?php

namespace App\Repository\Admin;

use App\ApiHelper\SortParamsHelper;
use App\Events\CreateClientEvent;
use App\Events\NotificationEvent;
use App\Filter\Transfer\TransferFilter;
use App\Http\Trait\UploadImage;
use App\Models\Expert;
use App\Models\Holiday;
use App\Models\Notification;
use App\Models\Service;
use App\Models\Transfer;
use App\Models\User;
use App\Repository\BaseRepositoryImplementation;
use App\Statuses\EventTypes;
use App\Statuses\HavePermission;
use App\Statuses\PermissionType;
use App\Statuses\UserType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class AdminRepository extends BaseRepositoryImplementation
{
    use UploadImage;
    public function getFilterItems($filter)
    {

        $records = Transfer::query();

        if ($filter instanceof TransferFilter) {

            $records->when(isset($filter->starting_date), function ($query) use ($filter) {
                $query->where('date', $filter->getStartingDate());
            });
            $records->when(isset($filter->end_date), function ($query) use ($filter) {
                $query->where('date', $filter->getEndingDate());
            });

            $records->when((isset($filter->starting_date) && isset($filter->end_date)), function ($records) use ($filter) {
                $records->whereBetween('date', [$filter->getStartingDate(), $filter->getEndingDate()])
                    ->orWhereBetween('date', [$filter->getStartingDate(), $filter->getEndingDate()]);
            });

            $records->when(isset($filter->transfer_amount), function ($records) use ($filter) {
                $records->where('transfer_amount', 'LIKE', '%' . $filter->getTransferAmount() . '%');
            });

            $records->when(isset($filter->user_id), function ($records) use ($filter) {
                $records->whereHas('user', function ($q) use ($filter) {
                    return $q->where('id', $filter->getUserId());
                });
            });
            $records->when(isset($filter->client_id), function ($records) use ($filter) {
                $records->whereHas('client', function ($q) use ($filter) {
                    return $q->where('id', $filter->getClientId());
                });
            });

            return $records->with(['client', 'user'])->paginate($filter->per_page);
        }

        return $records->with(['client', 'user'])->paginate($filter->per_page);
    }

    public function model()
    {
        return Transfer::class;
    }
    public function create_admin($data)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => UserType::ADMIN
            ]);
            DB::commit();
            return $user;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }
    public function create_receiption($data)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'type' => UserType::RECEPTION
            ]);

            DB::commit();
            $notification = Notification::create([
                'user_id' => Auth::user()->id,
                'notification_type' => EventTypes::CreateReciption,
                'title' => ' تم اضافة موظف استقبال جديد اسمه ' . $data['name'] . ' من قبل الموظف/ة ' . Auth::user()->name
            ]);

            event(new NotificationEvent($notification));
            return $user;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function create_transfer($data)
    {
        DB::beginTransaction();
        try {
            $transfer = new Transfer();
            if (Arr::has($data, 'attachment')) {
                $file = Arr::get($data, 'attachment');
                $file_name = $this->uploadTransferAttachment($file);
                $transfer->attachment = $file_name;
            }
            $transfer->user_id = auth()->user()->id;
            $transfer->client_id = $data['client_id'];
            $transfer->date = $data['date'];
            $transfer->transfer_amount = $data['transfer_amount'];
            $transfer->save();
            DB::commit();
            $notification = Notification::create([
                'user_id' => Auth::user()->id,
                'notification_type' => EventTypes::CreateTransfer,
                'title' => ' تم اضافة تحويلة ' . $transfer->id . ' بمبلغ قدره ' . $transfer->transfer_amount . ' من قبل الموظف/ة ' . Auth::user()->name
            ]);

            event(new NotificationEvent($notification));
            return $transfer->load(['user', 'client']);
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function update_transfer($data)
    {
        DB::beginTransaction();
        try {
            $transfer = $this->updateById($data['transfer_id'], $data);

            if (Arr::has($data, 'attachment')) {
                $file = Arr::get($data, 'attachment');
                $file_name = $this->uploadTransferAttachment($file);
                $transfer->attachment = $file_name;
            }

            DB::commit();

            if ($transfer === null) {
                return response()->json(['message' => "Transfer was not Updated"]);
            }

            return $transfer->load('user', 'client');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
            return response()->json(['message' => $e->getMessage()]);
        }
    }
    public function create_service($data)
    {
        DB::beginTransaction();
        try {
            $service = Service::create([
                'name' => $data['name'],
            ]);
            DB::commit();

            return $service;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function create_expert($data)
    {
        DB::beginTransaction();
        try {
            $expert = new Expert();
            if (Arr::has($data, 'image')) {
                $file = Arr::get($data, 'image');
                $file_name = $this->uploadExpertImage($file);
                $expert->image = $file_name;
            }
            $expert->name = $data['name'];
            $expert->position = $data['position'];
            $expert->save();
            // $expert->services()->attach($data['services']);
            DB::commit();
            $notification = Notification::create([
                'user_id' => Auth::user()->id,
                'notification_type' => EventTypes::CreateExpert,
                'title' => ' تم اضافة الخبير  ' . $data['name'] . ' في النظام من قبل الموظف/ة '. Auth::user()->name
            ]);

            event(new NotificationEvent($notification));
            return $expert;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function list_of_experts($filter)
    {
        $records = Expert::query();
        return $records->with(['holidays', 'reservations'])->paginate($filter->per_page);
    }

    public function list_of_services($filter)
    {
        $records = Service::query();
        return $records->paginate($filter->per_page);
    }
    public function list_of_receiptions($filter)
    {
        $records = User::query()->where('type', UserType::RECEPTION);
        return $records->paginate($filter->per_page);
    }

    public function create_holiday($data)
    {
        DB::beginTransaction();
        try {
            $existHoliday = Holiday::where('date', $data['date'])->where('expert_id', $data['expert_id'])->first();
            if (!$existHoliday) {
                $holiday = Holiday::create([
                    'date' => $data['date'],
                    'expert_id' => $data['expert_id'],
                ]);
                $notification = Notification::create([
                    'user_id' => Auth::user()->id,
                    'notification_type' => EventTypes::CreateReservation,
                    'title' => ' تم اضافة عطلة جديدة للخبير ذو المعرف  ' . $data['expert_id'] . ' في اليوم ' . $data['date'] . ' من قبل الموظف/ة ' . Auth::user()->name
                ]);

                event(new NotificationEvent($notification));
            }
            DB::commit();
            if ($holiday != null) {
                return $holiday;
            } else {
                $holiday->load('expert');
            }
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }

    public function chang_permission($data)
    {
        DB::beginTransaction();
        try {
            $receiption = User::where('id', $data['receiption_id'])->first();

            if ($receiption->type == UserType::RECEPTION) {
                if ($data['type'] == PermissionType::UPDATE && $data['can'] == HavePermission::TRUE) {
                    $receiption->update([
                        'permission_to_update' => HavePermission::TRUE
                    ]);
                } elseif ($data['type'] == PermissionType::UPDATE && $data['can'] == HavePermission::FALSE) {
                    $receiption->update([
                        'permission_to_update' => HavePermission::FALSE
                    ]);
                } elseif ($data['type'] == PermissionType::CANCLE && $data['can'] == HavePermission::TRUE) {
                    $receiption->update([
                        'permission_to_delete' => HavePermission::TRUE
                    ]);
                } elseif ($data['type'] == PermissionType::CANCLE && $data['can'] == HavePermission::FALSE) {
                    $receiption->update([
                        'permission_to_delete' => HavePermission::FALSE
                    ]);
                }
            } else {
                return 'Failed';
            }

            DB::commit();
            return $receiption;
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th->getMessage());
        }
    }
}
