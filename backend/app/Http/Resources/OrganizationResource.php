<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'yandex_business_id' => $this->yandex_business_id,
            'yandex_url' => $this->yandex_url,
            'title' => $this->title,
            'address' => $this->address,
            'average_rating' => $this->average_rating !== null ? (float) $this->average_rating : null,
            'rating_count' => $this->rating_count,
            'review_count' => $this->review_count,
            'parsed_review_count' => $this->parsed_review_count,
            'sync_status' => $this->sync_status,
            'sync_error' => $this->sync_error,
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
