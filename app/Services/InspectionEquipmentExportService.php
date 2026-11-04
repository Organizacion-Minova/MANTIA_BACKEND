<?php

namespace App\Services;

use App\Models\InspectionEquipment;
use Illuminate\Support\Facades\Storage;

class InspectionEquipmentExportService
{
    public function getExportData(int $inspectionId): array
    {
        $inspection = InspectionEquipment::with([
            'format.equipment.ubicacion',
            'format.sections.questions',
            'answers',
            'signatures',
        ])->findOrFail($inspectionId);

        $answersMap = [];
        foreach ($inspection->answers as $answer) {
            $answersMap["{$answer->question_id}-{$answer->day}"] = [
                'value' => $answer->value,
                'observation' => $answer->observation,
            ];
        }

        $signaturesMap = [];
        foreach ($inspection->signatures as $sig) {
            $signaturesMap[$sig->role] = [
                'name' => $sig->name,
                'id_number' => $sig->id_number,
                'signature_base64' => $this->resolveSignatureBase64($sig->signature),
            ];
        }

        return [
            'inspection' => $inspection,
            'format' => $inspection->format,
            'equipment' => $inspection->format?->equipment,
            'answersMap' => $answersMap,
            'signaturesMap' => $signaturesMap,
        ];
    }

    private function resolveSignatureBase64(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        if (!file_exists($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: 'image/png';
        $content = file_get_contents($fullPath);

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
