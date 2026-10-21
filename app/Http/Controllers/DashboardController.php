<?php

namespace App\Http\Controllers;

use App\Dashboard\DashboardService;
use App\Dashboard\SectionState;
use App\Http\Resources\Dashboard\DashboardResource;
use App\Http\Resources\Dashboard\SectionResource;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function index(): DashboardResource
    {
        return new DashboardResource($this->dashboard->all());
    }

    public function show(string $section): JsonResponse
    {
        $result = $this->dashboard->one($section);

        abort_if($result === null, 404);

        return (new SectionResource($result))
            ->response()
            ->setStatusCode($result->state === SectionState::Error ? 500 : 200);
    }
}