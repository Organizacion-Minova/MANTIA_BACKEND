<?php
namespace App\Http\Resources\Dashboard;

use App\Dashboard\SectionResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    //(para GET /api/dashboard)
    // Armamos la envoltura completa nosotros: el "data" de una sección puede ser null.
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $sections = collect($this->resource); // array<string, SectionResult>

        return [
            'data' => $sections->map(fn (SectionResult $r) => $r->data)->all(),
            'meta' => [
                'generatedAt' => now()->toIso8601ZuluString(),
                'sections' => $sections->map(fn (SectionResult $r) => $r->state->value)->all(),
            ],
        ];
    }
}