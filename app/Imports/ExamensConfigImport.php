<?php

namespace App\Imports;

use App\Models\ExamenConfig;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class ExamensConfigImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $skippedCodes = [];

    private Collection $validCodes;

    public function __construct()
    {
        $this->validCodes = ExamenConfig::pluck('code');
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code      = trim((string) ($row['code'] ?? ''));
            $nomExamen = trim((string) ($row['nom_examen'] ?? ''));

            if ($code === '' || $nomExamen === '') {
                continue;
            }

            if (! $this->validCodes->contains($code)) {
                $this->skipped++;
                $this->skippedCodes[] = $code;
                continue;
            }

            $nullify = fn ($v) => ($v === null || trim((string) $v) === '') ? null : (float) $v;

            ExamenConfig::where('code', $code)->update([
                'nom_examen'      => $nomExamen,
                'valeur_usuelle1' => $nullify($row['valeur_usuelle1'] ?? null),
                'valeur_usuelle2' => $nullify($row['valeur_usuelle2'] ?? null),
                'limite1'         => $nullify($row['limite1'] ?? null),
                'limite2'         => $nullify($row['limite2'] ?? null),
            ]);

            $this->imported++;
        }
    }
}
