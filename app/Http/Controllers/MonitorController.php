<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use App\Jobs\CheckMonitorJob;
use App\Http\Resources\MonitorResource;
use App\Http\Requests\StoreMonitorRequest;
use App\Http\Resources\MonitorCheckResource;

class MonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $monitors = Monitor::latest()->get();
        return MonitorResource::collection($monitors)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitorRequest $request)
    {
        $monitor = Monitor::create([
            'url' => $request->validated('url'),
            'check_interval' => $request->validated('check_interval', 5),
            'threshold' => $request->validated('threshold', 3),
            'status' => 'pending',
        ]);

        // dispatch monitor job
        CheckMonitorJob::dispatch($monitor->id);

        return (new MonitorResource($monitor))
            ->response()
            ->setStatusCode(201);
    }

    public function history(Request $request, Monitor $monitor)
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $checks = $monitor->checks()->latest('checked_at')
            ->paginate($perPage);

        return MonitorCheckResource::collection($checks)
            ->response()
            ->setStatusCode(200);
    }
}
