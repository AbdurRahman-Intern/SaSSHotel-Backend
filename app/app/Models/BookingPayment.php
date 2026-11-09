<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** BookingPayment  -  an extra payment received after the advance. */
class BookingPayment extends Model
{
    use HasUlids, BelongsToTenant;

    protected $fillable = ['booking_id', 'paid_on', 'amount'];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount' => 'float'];
    }
}
