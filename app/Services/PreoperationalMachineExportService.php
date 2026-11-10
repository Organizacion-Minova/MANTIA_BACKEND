<?php

namespace App\Services;

use App\Models\PreoperationalMachine;
use Illuminate\Support\Facades\Storage;

class PreoperationalMachineExportService
{
    public function getExportData(int $inspectionId): array
    {
        $inspection = PreoperationalMachine::with([
            'machine.category_maquina',
            'machine.ubicacion',
            'format.sections.questions',
            'answers',
        ])->findOrFail($inspectionId);

        $answersMap = [];
        foreach ($inspection->answers as $answer) {
            $qId = $answer->question_id;
            $day = $answer->day ?? 0;
        
            if (!isset($answersMap[$qId][$day])) {
                $answersMap[$qId][$day] = [
                    'value' => null,
                ];
            }
            if (!is_null($answer->value)) {
                $answersMap[$qId][$day]['value'] = $answer->value;
            }
        }

        $signatureBase64 = $this->resolveSignatureBase64($inspection->signature);

        return [
            'inspection' => $inspection,
            'format' => $inspection->format,
            'machine' => $inspection->machine,
            'answersMap' => $answersMap,
            'signatureBase64' => $signatureBase64,
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
