<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** LoanRepayment  -  one partial payment of a loan. Not an operating expense. */
class LoanRepayment extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['loan_id', 'paid_on', 'amount'];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount' => 'float'];
    }
}
