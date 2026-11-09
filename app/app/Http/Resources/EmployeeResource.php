<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Employee { id, fullName, phone, position, department, employeeType, gender, salary, joinDate, status, serviceChargeEligible, address, idNumber, emergencyContact, notes } */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'phone' => $this->phone,
            'position' => $this->position,
            'department' => $this->department,
            'employeeType' => $this->employee_type,
            'gender' => $this->gender,
            'salary' => $this->salary,
            'joinDate' => $this->join_date?->toDateString(),
            'status' => $this->status,
            'serviceChargeEligible' => $this->service_charge_eligible,
            'address' => $this->address,
            'idNumber' => $this->id_number,
            'emergencyContact' => $this->emergency_contact,
            'notes' => $this->notes,
        ];
    }
}
