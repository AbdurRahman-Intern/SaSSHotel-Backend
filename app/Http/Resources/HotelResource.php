<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Hotel { id, name, slug, ownerName, ownerEmail, phone, address, logoUrl, status, planId, registeredAt, currency, dateFormat, contactEmail } */
class HotelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'ownerName' => $this->owner_name,
            'ownerEmail' => $this->owner_email,
            'phone' => $this->phone,
            'address' => $this->address,
            'logoUrl' => $this->logo_url,
            'status' => $this->status,
            'planId' => $this->plan_id,
            'registeredAt' => $this->registered_at?->toDateString(),
            'currency' => $this->currency,
            'dateFormat' => $this->date_format,
            'contactEmail' => $this->contact_email,
        ];
    }
}
