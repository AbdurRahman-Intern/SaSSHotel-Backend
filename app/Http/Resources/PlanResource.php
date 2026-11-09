<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Plan { id, name, priceMonthly, maxUsers }  (see UserResource for how resources work) */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'priceMonthly' => $this->price_monthly,
            'maxUsers' => $this->max_users,
        ];
    }
}
