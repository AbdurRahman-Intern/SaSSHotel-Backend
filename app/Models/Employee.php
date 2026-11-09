<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Employee  -  a staff member of the hotel. */
class Employee extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = [
        'full_name', 'phone', 'position', 'department', 'employee_type', 'gender', 'salary', 'join_date',
        'status', 'service_charge_eligible', 'address', 'id_number', 'emergency_contact', 'notes',
    ];

    protected function casts(): array
    {
        return ['salary' => 'float', 'join_date' => 'date', 'service_charge_eligible' => 'boolean'];
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }
}
