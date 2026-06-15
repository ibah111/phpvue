<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'yandex_review_id' => $this->yandex_review_id,
            'author' => [
                'name' => $this->author_name,
                'public_id' => $this->author_public_id,
            ],
            'date' => $this->reviewed_at?->toISOString(),
            'text' => $this->text,
            'rating' => $this->rating,
        ];
    }
}
