<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

class AppLogger
{
    /**
     * @param array<string, mixed> $detailedData
     */
    public function log(string $methodName, array $detailedData = []): void
    {
        Log::debug("log ( { {$methodName} }, detailed_data )", [
            'method-name' => $methodName,
            'detailed_data' => $detailedData,
        ]);
    }
}
