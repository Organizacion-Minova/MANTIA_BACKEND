<?php

namespace App\Http\Resources\Dashboard;

use App\Dashboard\SectionResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
//seccion unica dash
class SectionResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var SectionResult $result */
        $result = $this->resource;

        return [
            'data' => $result->data,
            'meta' => ['status' => $result->state->value],
        ];
    }
}