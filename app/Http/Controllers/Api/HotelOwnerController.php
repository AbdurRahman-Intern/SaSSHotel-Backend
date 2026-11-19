<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * HotelOwnerController  -  Super Admin manages the LOGIN ACCOUNT of a hotel's owner.
 *
 *   PATCH /api/hotels/{id}/owner                  -> change the owner's name and/or login e-mail
 *   POST  /api/hotels/{id}/owner/reset-password   -> create a new temporary password (the owner forgot theirs)
 *
 * Super Admin only (see routes/api.php). To block an owner completely, SUSPEND the hotel instead
 * (HotelController::update with status = suspended): that blocks every request immediately.
 *
 * The owner's name and e-mail are stored twice: on the user (users.name / users.email, used to log in) and on the
 * hotel (hotels.owner_name / owner_email, shown in lists). Both must always be changed together.
 */
class HotelOwnerController extends Controller
{
    /**
     * PATCH /api/hotels/{id}/owner   { ownerName?, ownerEmail? }  ->  the updated hotel
     *
     * Changing the e-mail changes how the owner logs in. Tell them!
     */
    public function update(Request $request, string $id)
    {
        $hotel = Hotel::findOrFail($id);
        $owner = $this->ownerOf($hotel);

        $data = $request->validate([
            'ownerName' => ['sometimes', 'string', 'min:2', 'max:255'],
            // unique:users, but the owner may keep their OWN e-mail (ignore their id).
            'ownerEmail' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($owner->id)],
        ]);

        DB::transaction(function () use ($hotel, $owner, $data) {
            $oldEmail = $hotel->owner_email;

            if (isset($data['ownerName'])) {
                $owner->name = $data['ownerName'];
                $hotel->owner_name = $data['ownerName'];
            }
            if (isset($data['ownerEmail'])) {
                $owner->email = strtolower($data['ownerEmail']);
                $hotel->owner_email = strtolower($data['ownerEmail']);
                // If the hotel's public contact e-mail was the owner's e-mail, move it along.
                if ($hotel->contact_email === $oldEmail) {
                    $hotel->contact_email = $hotel->owner_email;
                }
            }

            $owner->save();
            $hotel->save();
        });

        ActivityLog::record("Owner account updated: {$hotel->name}");

        return new HotelResource($hotel->fresh());
    }

    /**
     * POST /api/hotels/{id}/owner/reset-password  ->  { temporaryPassword: "..." }
     *
     * Sets a new random password and logs the owner out everywhere (all old tokens are deleted).
     * The password is returned ONCE and is never stored in readable form, so the Super Admin must copy it now and
     * give it to the owner. TODO (later): send a "set your password" e-mail instead.
     */
    public function resetPassword(string $id)
    {
        $hotel = Hotel::findOrFail($id);
        $owner = $this->ownerOf($hotel);

        $password = Str::password(12);
        $owner->password = $password; // hashed automatically by the User cast
        $owner->save();
        $owner->tokens()->delete();

        ActivityLog::record("Owner password reset: {$hotel->name}");

        return response()->json(['temporaryPassword' => $password]);
    }

    /**
     * The hotel's owner account: the first hotel_owner user of this hotel (404 if somehow none exists).
     */
    private function ownerOf(Hotel $hotel): User
    {
        return User::where('tenant_id', $hotel->id)->where('role', 'hotel_owner')->orderBy('id')->firstOrFail();
    }
}
