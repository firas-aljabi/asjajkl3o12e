<?php

namespace App\Services\Admin;

use App\Filter\Expert\ExpertFilter;
use App\Filter\Service\ServiceFilter;
use App\Filter\Transfer\TransferFilter;
use App\Filter\User\UserFilter;
use App\Interfaces\Admin\AdminServiceInterface;
use App\Models\Transfer;
use App\Repository\Admin\AdminRepository;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AdminService implements AdminServiceInterface
{
    public function __construct(private AdminRepository $adminRepository)
    {
    }
    public function create_admin($data)
    {
        return $this->adminRepository->create_admin($data);
    }

    public function create_receiption($data)
    {
        return $this->adminRepository->create_receiption($data);
    }

    public function create_transfer($data)
    {
        return $this->adminRepository->create_transfer($data);
    }
    public function show_transfer(int $id)
    {
        return $this->adminRepository->getById($id)->load(['user', 'client']);
    }
    public function update_transfer($data)
    {
        return $this->adminRepository->update_transfer($data);
    }

    public function list_of_transfers(TransferFilter $transferFilter = null)
    {
        if ($transferFilter != null)
            return $this->adminRepository->getFilterItems($transferFilter);
        else
            return $this->adminRepository->paginate();
    }

    public function export()
    {
        $table_attributes = DB::getSchemaBuilder()->getColumnListing('transfers');
        $transfers = Transfer::with(['user', 'client'])->get();
        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();

        $i = 'A';
        foreach ($table_attributes as $value) {
            if ($value == 'attachment') {
                continue;
            }
            if ($value == 'created_at') {
                continue;
            }
            if ($value == 'updated_at') {
                continue;
            }
            if ($value == 'user_id') {
                $value = 'user';
            }
            if ($value == 'client_id') {
                $value = 'client';
            }
            $activeWorksheet->setCellValue($i . '1', $value);
            $activeWorksheet->getStyle($i . '1')->getAlignment()->setHorizontal('center');
            $activeWorksheet->getColumnDimension($i)->setWidth(30);
            $activeWorksheet->getStyle($i . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFA0A0A0');
            $header_columns[] = [$i => $value];
            $i++;
        }

        $i = 'A';
        $key_index = 2;
        foreach ($header_columns as $key => $value) {
            foreach ($transfers as $userKey => $userValue) {
                $name = $value[$i];

                if ($name == 'user') {
                    $name = 'user.name';
                }
                if ($name == 'client') {
                    $name = 'client.name';
                }

                $theKey = key($value);
                $activeWorksheet->setCellValue($theKey . $key_index, data_get($userValue, $name));
                $activeWorksheet->getStyle($theKey . $key_index)->getAlignment()->setHorizontal('center');

                $activeWorksheet->getStyle($theKey . $key_index)
                    ->getBorders()
                    ->getOutline()
                    ->setBorderStyle(Border::BORDER_THICK);
                $key_index++;
            }
            $i++;
            $key_index = 2;
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save('transfers.xlsx');
        $file_name = Date('Y-m-d') . '-transfers.xlsx';
        return response()->download(public_path('transfers.xlsx'), $file_name, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Dispostion' => 'inline,file_name=" ' . $file_name . ' "'
        ]);
    }

    public function create_service($data)
    {
        return $this->adminRepository->create_service($data);
    }
    public function create_expert($data)
    {
        return $this->adminRepository->create_expert($data);
    }

    public function list_of_experts(ExpertFilter $expertFilter = null)
    {
        if ($expertFilter != null)
            return $this->adminRepository->list_of_experts($expertFilter);
        else
            return $this->adminRepository->paginate();
    }

    public function list_of_services(ServiceFilter $serviceFilter = null)
    {
        if ($serviceFilter != null)
            return $this->adminRepository->list_of_services($serviceFilter);
        else
            return $this->adminRepository->paginate();
    }

    public function list_of_receiptions(UserFilter $userFilter = null)
    {
        if ($userFilter != null)
            return $this->adminRepository->list_of_receiptions($userFilter);
        else
            return $this->adminRepository->paginate();
    }
    public function create_holiday($data)
    {
        return $this->adminRepository->create_holiday($data);
    }
    public function chang_permission($data)
    {
        return $this->adminRepository->chang_permission($data);
    }
}
