<?php

namespace App\Imports;

use App\Models\ExamenConfig;
use App\Models\TarifCentral;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;

class TarifsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $skippedCodes = [];

    private int $annee;
    private Collection $validCodes;

    public function __construct(int $annee)
    {
        $this->annee = $annee;
        $this->validCodes = ExamenConfig::pluck('code');
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = trim((string) ($row['code_examen'] ?? ''));
            $prix = $row['prix_bif'] ?? $row['prix'] ?? null;

            if ($code === '' || $prix === null || $prix === '') {
                continue;
            }

            if (! $this->validCodes->contains($code)) {
                $this->skipped++;
                $this->skippedCodes[] = $code;
                continue;
            }

            TarifCentral::updateOrCreate(
                ['code_examen' => $code, 'annee' => $this->annee],
                ['prix' => (float) $prix, 'devise' => 'BIF']
            );

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [];
    }
}
