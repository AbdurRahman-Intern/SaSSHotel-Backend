<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** ActivityLog  -  one line in the Super Admin activity feed. Use ActivityLog::record('text'). */
class ActivityLog extends Model
{
    use HasUlids;

    protected $fillable = ['message'];

    /** Shortcut: ActivityLog::record('Hotel suspended: Palace'); */
    public static function record(string $message): self
    {
        return static::create(['message' => $message]);
    }
}
