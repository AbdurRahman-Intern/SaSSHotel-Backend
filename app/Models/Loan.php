<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Loan  -  money borrowed by the hotel ('borrowed') or lent to someone ('lent'). */
class Loan extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['type', 'party', 'amount', 'issue_date', 'due_date', 'notes'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'issue_date' => 'date', 'due_date' => 'date'];
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    /** What is still unpaid. Eager-load `repayments` first. */
    public function outstanding(): float
    {
        return max(0, $this->amount - (float) $this->repayments->sum('amount'));
    }
}
