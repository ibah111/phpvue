<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Models\OrganizationReview;
use App\Models\User;
use App\Services\Logging\AppLogger;
use App\Services\YandexMaps\YandexMapsScraper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrganizationSyncService
{
    public function __construct(
        private readonly YandexMapsScraper $scraper,
        private readonly AppLogger $logger,
    ) {
    }

    public function sync(User $user, string $url): Organization
    {
        $this->logger->log('OrganizationSyncService@sync.start', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $parsed = $this->scraper->parse($url);

        $organization = DB::transaction(function () use ($user, $url, $parsed): Organization {
            $organization = Organization::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'yandex_business_id' => $parsed['business_id'],
                ],
                [
                    'yandex_url' => $url,
                    'title' => $parsed['title'],
                    'address' => $parsed['address'],
                    'average_rating' => $parsed['average_rating'],
                    'rating_count' => $parsed['rating_count'],
                    'review_count' => $parsed['review_count'],
                    'parsed_review_count' => $parsed['parsed_review_count'],
                    'sync_status' => 'ready',
                    'sync_error' => null,
                    'last_synced_at' => now(),
                    'meta' => $parsed['meta'],
                ],
            );

            $this->replaceReviews($organization, $parsed['reviews']);

            return $organization->refresh();
        });

        $this->logger->log('OrganizationSyncService@sync.done', [
            'organization_id' => $organization->id,
            'business_id' => $organization->yandex_business_id,
            'parsed_review_count' => $organization->parsed_review_count,
        ]);

        return $organization;
    }

    /**
     * @param array<int, array<string, mixed>> $reviews
     */
    private function replaceReviews(Organization $organization, array $reviews): void
    {
        $now = Carbon::now();
        $rows = [];
        $reviewIds = [];

        foreach ($reviews as $review) {
            $reviewIds[] = $review['yandex_review_id'];
            $rows[] = [
                'organization_id' => $organization->id,
                'yandex_review_id' => $review['yandex_review_id'],
                'author_name' => $review['author_name'],
                'author_public_id' => $review['author_public_id'],
                'reviewed_at' => $review['reviewed_at'],
                'text' => $review['text'],
                'rating' => $review['rating'],
                'raw' => json_encode($review['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            OrganizationReview::query()->upsert(
                $rows,
                ['organization_id', 'yandex_review_id'],
                ['author_name', 'author_public_id', 'reviewed_at', 'text', 'rating', 'raw', 'updated_at'],
            );
        }

        OrganizationReview::query()
            ->where('organization_id', $organization->id)
            ->when($reviewIds !== [], fn ($query) => $query->whereNotIn('yandex_review_id', $reviewIds))
            ->delete();
    }
}
