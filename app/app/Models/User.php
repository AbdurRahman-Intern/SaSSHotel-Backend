<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User  -  a person who can log in (Super Admin or Hotel Owner).
 *
 * This REPLACES the default app/Models/User.php that ships with Laravel.
 * `HasApiTokens` (Sanctum) gives us $user->createToken('name') for the login endpoint.
 *
 * NOTE: User deliberately does NOT use BelongsToTenant. The login lookup must find users
 * before anyone is authenticated, and the super admin has no tenant.
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /** Columns that may be mass-assigned (User::create([...])). */
    protected $fillable = ['name', 'email', 'password', 'role', 'tenant_id'];

    /** Never include these when the model is turned into JSON. */
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // automatically bcrypt-hashes when you assign a plain password
        ];
    }

    /** $user->hotel : the hotel this owner runs (null for the super admin). */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'tenant_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
