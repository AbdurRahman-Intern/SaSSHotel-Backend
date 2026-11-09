<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** SalaryPayment { id, employeeId, month, basicSalary, serviceCharge, advanceDeduction, otherDeduction, paidDate, status, notes } */
class SalaryPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employeeId' => $this->employee_id,
            'month' => $this->month,
            'basicSalary' => $this->basic_salary,
            'serviceCharge' => $this->service_charge,
            'advanceDeduction' => $this->advance_deduction,
            'otherDeduction' => $this->other_deduction,
            'paidDate' => $this->paid_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
