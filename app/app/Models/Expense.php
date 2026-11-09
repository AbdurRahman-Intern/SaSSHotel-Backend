<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** Expense  -  a daily operating cost (fuel, electricity ...). */
class Expense extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['spent_on', 'category', 'description', 'amount', 'payment_method', 'paid_by', 'reference', 'notes'];

    protected function casts(): array
    {
        return ['spent_on' => 'date', 'amount' => 'float'];
    }
}
