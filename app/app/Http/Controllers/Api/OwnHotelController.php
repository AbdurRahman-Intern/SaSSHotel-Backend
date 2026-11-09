<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use App\Support\Tenancy;
use Illuminate\Http\Request;

/**
 * OwnHotelController  -  the Hotel Settings page. An owner reads/updates ONLY their own hotel.
 *   GET /api/my-hotel      PATCH /api/my-hotel
 * The hotel id always comes from the logged-in user, never from the URL.
 */
class OwnHotelController extends Controller
{
    public function show()
    {
        return new HotelResource(Hotel::findOrFail(Tenancy::id()));
    }

    public function update(Request $request)
    {
        $hotel = Hotel::findOrFail(Tenancy::id());

        // Owners may edit contact + display settings, NOT plan or status (only the Super Admin can).
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:40'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contactEmail' => ['sometimes', 'email'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'dateFormat' => ['sometimes', 'string', 'max:20'],
        ]);

        $hotel->update(collect($data)->mapWithKeys(fn ($v, $k) => [\Illuminate\Support\Str::snake($k) => $v])->all());
        // TODO: logo upload -> $request->file('logo')->store('logos', 'public') and save the URL.

        return new HotelResource($hotel);
    }
}
