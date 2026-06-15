<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\ReviewResource;
use App\Models\Organization;
use App\Services\Logging\AppLogger;
use App\Services\Organizations\OrganizationSyncService;
use App\Services\YandexMaps\YandexMapsParserException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationSyncService $syncService,
        private readonly AppLogger $logger,
    ) {
    }

    public function show(Request $request): JsonResponse|OrganizationResource
    {
        $organization = $this->currentOrganization($request);

        $this->logger->log('OrganizationController@show', [
            'user_id' => $request->user()?->id,
            'organization_id' => $organization?->id,
        ]);

        if (! $organization) {
            return response()->json(['data' => null]);
        }

        return OrganizationResource::make($organization);
    }

    public function store(StoreOrganizationRequest $request): OrganizationResource|JsonResponse
    {
        $this->logger->log('OrganizationController@store.start', [
            'user_id' => $request->user()?->id,
            'url' => $request->validated('url'),
        ]);

        try {
            $organization = $this->syncService->sync($request->user(), $request->validated('url'));
        } catch (YandexMapsParserException $exception) {
            $this->logger->log('OrganizationController@store.parser_error', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return OrganizationResource::make($organization);
    }

    public function reviews(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $organization = $this->currentOrganization($request);

        $this->logger->log('OrganizationController@reviews.start', [
            'user_id' => $request->user()?->id,
            'organization_id' => $organization?->id,
            'page' => $request->integer('page', 1),
        ]);

        if (! $organization) {
            return response()->json([
                'message' => 'Организация еще не подключена.',
            ], 404);
        }

        $paginator = $organization->reviews()
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->paginate(50);

        return ReviewResource::collection($paginator);
    }

    private function currentOrganization(Request $request): ?Organization
    {
        return $request->user()
            ?->organizations()
            ->latest('updated_at')
            ->first();
    }
}
