<?php

namespace App\Services;

use App\Models\MonitorCheck;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class UptimeCheckerService
{
    // Service class implementation
    private const TIMEOUT_SECONDS = 10;

    /**
     * Perform an HTTP GET against $url and return a result.
     */
    public function check(string $url): array
    {
        $start = microtime(true);

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withUserAgent('UptimeMonitor/1.0')
                ->get($url);

            $responseTimeMs = (int) round((microtime(true) - $start) * 1000);
            $statusCode = $response->status();
            $isUp = MonitorCheck::isStatusUp($statusCode);

            return [
                'status_code' => $statusCode,
                'response_time_ms' => $responseTimeMs,
                'is_up' => $isUp,
            ];
        } catch (ConnectionException) {
            // Timeout or DNS/network failure
            return [
                'status_code' => 0,
                'response_time_ms' => null,
                'is_up' => false,
            ];
        }
    }
}
