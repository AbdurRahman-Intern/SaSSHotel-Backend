<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** SalaryPayment  -  one employee's salary for one month. net = basic + service charge - deductions. */
class SalaryPayment extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = [
        'employee_id', 'month', 'basic_salary', 'service_charge', 'advance_deduction',
        'other_deduction', 'paid_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'float', 'service_charge' => 'float', 'advance_deduction' => 'float',
            'other_deduction' => 'float', 'paid_date' => 'date',
        ];
    }

    public function net(): float
    {
        return $this->basic_salary + $this->service_charge - $this->advance_deduction - $this->other_deduction;
    }
}
