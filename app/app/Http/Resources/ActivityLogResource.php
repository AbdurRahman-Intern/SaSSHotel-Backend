<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** ActivityLog { id, date, message } */
class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'date' => $this->created_at?->toDateString(), 'message' => $this->message];
    }
}
