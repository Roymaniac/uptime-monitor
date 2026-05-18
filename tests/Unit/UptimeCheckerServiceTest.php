<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\UptimeCheckerService;
use Illuminate\Http\Client\ConnectionException;

class UptimeCheckerServiceTest extends TestCase
{
    private UptimeCheckerService $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new UptimeCheckerService();
    }

    public function test_returns_up_for_200_response(): void
    {
        Http::fake(['https://example.com' => Http::response('OK', 200)]);

        $result = $this->checker->check('https://example.com');

        $this->assertSame(200, $result['status_code']);
        $this->assertTrue($result['is_up']);
        $this->assertIsInt($result['response_time_ms']);
    }

    public function test_returns_up_for_301_redirect(): void
    {
        Http::fake(['https://example.com' => Http::response('Moved', 301)]);

        $result = $this->checker->check('https://example.com');

        $this->assertTrue($result['is_up']);
    }

    public function test_returns_down_for_500_response(): void
    {
        Http::fake(['https://example.com' => Http::response('Error', 500)]);

        $result = $this->checker->check('https://example.com');

        $this->assertSame(500, $result['status_code']);
        $this->assertFalse($result['is_up']);
    }

    public function test_returns_zero_status_and_null_time_on_connection_failure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $result = $this->checker->check('https://unreachable.example');

        $this->assertSame(0, $result['status_code']);
        $this->assertNull($result['response_time_ms']);
        $this->assertFalse($result['is_up']);
    }
}
