<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'yandex_business_id',
    'yandex_url',
    'title',
    'address',
    'average_rating',
    'rating_count',
    'review_count',
    'parsed_review_count',
    'sync_status',
    'sync_error',
    'last_synced_at',
    'meta',
])]
class Organization extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'average_rating' => 'decimal:2',
            'rating_count' => 'integer',
            'review_count' => 'integer',
            'parsed_review_count' => 'integer',
            'last_synced_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(OrganizationReview::class);
    }
}
