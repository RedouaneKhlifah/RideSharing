<?php

namespace App\Imports;

use App\Models\HistoryOfPay;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class  EmployeeImport implements ToCollection , WithHeadingRow
{

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            // Skip rows with missing or invalid data
            if (empty($row[0]) || empty($row[1]) || !isset($row[2])) {
                continue;
            }

            $matricule = $row[0];
            $dateValue = $row[1];
            $presence = (float) $row[2];

            // Parse the date from Excel format or string
            if (is_numeric($dateValue)) {
                $dateObj = Date::excelToDateTimeObject($dateValue);
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $dateValue);
                if (!$dateObj) {
                    continue; // Skip invalid date formats
                }
            }

            $yearMonth = $dateObj->format('Y-m');
            $startDate = $dateObj->format('Y-m-01');
            $endDate = $dateObj->format('Y-m-t');

            $employee = Employee::where('matricule', $matricule)->first();

            if (!$employee) {
                continue;
            }

            $key = $employee->id . '-' . $yearMonth;

            if (!isset($data[$key])) {
                $data[$key] = [
                    'employee_id' => $employee->id,
                    'total_ton' => 0,
                    'total_gain' => 0,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ];
            }

            $data[$key]['total_ton'] += $presence;
            $data[$key]['total_gain'] += $presence * $employee->price_per_ton;
        }

        foreach ($data as $record) {
            HistoryOfPay::updateOrCreate(
                [
                    'employee_id' => $record['employee_id'],
                    'start_date' => $record['start_date'],
                    'end_date' => $record['end_date']
                ],
                [
                    'total_ton' => $record['total_ton'],
                    'total_gain' => $record['total_gain']
                ]
            );
        }
    }
}