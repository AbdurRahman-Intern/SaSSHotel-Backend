<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\ActivityLog;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * HotelController  -  SUPER ADMIN only (see routes/api.php): manage the hotels (tenants).
 *
 * Creating a hotel ALSO creates its owner login (the owner can then sign in).
 * Hotel is the tenant itself, so this controller does not use BelongsToTenant.
 */
class HotelController extends Controller
{
    private function rules(?string $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ownerName' => ['required', 'string', 'max:255'],
            'ownerEmail' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'planId' => ['required', Rule::exists('plans', 'id')],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
            'contactEmail' => ['nullable', 'email'],
        ];
    }

    /** GET /api/hotels */
    public function index()
    {
        return HotelResource::collection(Hotel::orderByDesc('created_at')->get());
    }

    /** GET /api/hotels/{id} */
    public function show(string $id)
    {
        return new HotelResource(Hotel::findOrFail($id));
    }

    /**
     * POST /api/hotels
     * Body also needs `ownerPassword` (initial password for the owner login).
     */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'ownerPassword' => ['required', 'string', 'min:8'],
            // unique:users = the owner's e-mail becomes the login, so it must not exist yet.
            'ownerEmail' => ['required', 'email', 'unique:users,email'],
        ]);

        $hotel = DB::transaction(function () use ($data) {
            $hotel = Hotel::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(4)),
                'owner_name' => $data['ownerName'],
                'owner_email' => $data['ownerEmail'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? '',
                'status' => 'active',
                'plan_id' => $data['planId'],
                'registered_at' => now()->toDateString(),
                'currency' => strtoupper($data['currency']),
                'contact_email' => $data['contactEmail'] ?? $data['ownerEmail'],
            ]);

            User::create([
                'name' => $data['ownerName'],
                'email' => $data['ownerEmail'],
                'password' => $data['ownerPassword'], // hashed automatically by the User cast
                'role' => 'hotel_owner',
                'tenant_id' => $hotel->id,
            ]);

            ActivityLog::record("New hotel registered: {$hotel->name}");

            return $hotel;
        });

        return (new HotelResource($hotel))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /** PATCH /api/hotels/{id}  (also used to suspend/activate: send {"status":"suspended"}) */
    public function update(Request $request, string $id)
    {
        $hotel = Hotel::findOrFail($id);

        $data = $request->validate(collect($this->rules($id))
            ->except('ownerEmail') // changing the login e-mail is a separate feature (TODO)
            ->map(fn ($r) => array_merge(['sometimes'], array_values(array_diff((array) $r, ['required']))))
            ->all());

        $map = [
            'name' => 'name', 'ownerName' => 'owner_name', 'phone' => 'phone', 'address' => 'address',
            'planId' => 'plan_id', 'currency' => 'currency', 'status' => 'status', 'contactEmail' => 'contact_email',
        ];
        $changes = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $changes[$column] = $data[$input];
            }
        }
        $hotel->update($changes);

        if (($changes['status'] ?? null) === 'suspended') {
            ActivityLog::record("Hotel suspended: {$hotel->name}");
        }

        return new HotelResource($hotel);
    }

    /** DELETE /api/hotels/{id} - TODO: decide policy (soft delete? block when it has data?). */
    public function destroy(string $id)
    {
        Hotel::findOrFail($id)->delete();

        return response()->noContent();
    }
}
