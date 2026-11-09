<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SalaryPaymentResource;
use App\Models\SalaryPayment;
use App\Support\Tenancy;
use Illuminate\Validation\Rule;

/** SalaryPaymentController  -  /api/salary-payments. Supports ?month=2026-11&employeeId=... One row per employee per month. */
class SalaryPaymentController extends TenantCrudController
{
    protected function modelClass(): string { return SalaryPayment::class; }
    protected function resourceClass(): string { return SalaryPaymentResource::class; }
    protected function filterable(): array { return ['month', 'employeeId', 'status']; }
    protected function orderBy(): array { return ['month', 'desc']; }

    protected function rules(?string $id): array
    {
        return [
            // Tenancy::exists = the employee must exist AND belong to MY hotel.
            'employeeId' => ['required', Tenancy::exists('employees')],
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
                Rule::unique('salary_payments', 'month')->where('employee_id', request('employeeId'))->ignore($id)],
            'basicSalary' => ['required', 'numeric', 'min:0'],
            'serviceCharge' => ['numeric', 'min:0'],
            'advanceDeduction' => ['numeric', 'min:0'],
            'otherDeduction' => ['numeric', 'min:0'],
            'paidDate' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['paid', 'pending'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
