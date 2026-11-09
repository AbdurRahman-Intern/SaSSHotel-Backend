<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hotel  -  one customer of the SaaS platform = one TENANT.
 *
 * Managed by the Super Admin (HotelController). A hotel owner reads/updates ONLY their own row
 * through OwnHotelController. Hotel itself has no BelongsToTenant trait (it IS the tenant).
 */
class Hotel extends Model
{
    use HasUlids; // string primary key generated automatically

    protected $fillable = [
        'name', 'slug', 'owner_name', 'owner_email', 'phone', 'address', 'logo_url',
        'status', 'plan_id', 'registered_at', 'currency', 'date_format', 'contact_email',
    ];

    protected function casts(): array
    {
        return ['registered_at' => 'date'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }
}
