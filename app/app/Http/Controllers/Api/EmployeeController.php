<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Validation\Rule;

/** EmployeeController  -  /api/employees. Supports ?status=active&department=Kitchen */
class EmployeeController extends TenantCrudController
{
    protected function modelClass(): string { return Employee::class; }
    protected function resourceClass(): string { return EmployeeResource::class; }
    protected function filterable(): array { return ['status', 'department']; }
    protected function orderBy(): array { return ['full_name', 'asc']; }

    protected function rules(?string $id): array
    {
        return [
            'fullName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'position' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'employeeType' => ['required', Rule::in(['Full Time', 'Part Time', 'Contract'])],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'salary' => ['required', 'numeric', 'min:0'],
            'joinDate' => ['required', 'date'],
            'status' => ['required', Rule::in(['active', 'on_leave', 'inactive'])],
            'serviceChargeEligible' => ['boolean'],
            'address' => ['nullable', 'string', 'max:255'],
            'idNumber' => ['nullable', 'string', 'max:255'],
            'emergencyContact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
    // TODO: empty strings arrive as null for "nullable" fields and the columns default to ''.
    //       If MySQL complains "cannot be null", override prepare() and replace null with ''.
}
