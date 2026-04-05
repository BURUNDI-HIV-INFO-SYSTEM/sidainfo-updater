<?php

namespace App\Exports;

use App\Models\ExamenConfig;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class ExamensConfigExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function collection(): Collection
    {
        return ExamenConfig::orderBy('code')->get()->map(fn ($e) => [
            'code'            => $e->code,
            'nom_examen'      => $e->nom_examen,
            'valeur_usuelle1' => $e->valeur_usuelle1 !== null ? rtrim(rtrim($e->valeur_usuelle1, '0'), '.') : '',
            'valeur_usuelle2' => $e->valeur_usuelle2 !== null ? rtrim(rtrim($e->valeur_usuelle2, '0'), '.') : '',
            'limite1'         => $e->limite1 !== null ? rtrim(rtrim($e->limite1, '0'), '.') : '',
            'limite2'         => $e->limite2 !== null ? rtrim(rtrim($e->limite2, '0'), '.') : '',
        ]);
    }

    public function headings(): array
    {
        return ['code', 'nom_examen', 'valeur_usuelle1', 'valeur_usuelle2', 'limite1', 'limite2'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1    => ['font' => ['bold' => true]],
            // code column is read-only — grey it out
            'A'  => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F1F5F9']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 34,
            'C' => 16,
            'D' => 16,
            'E' => 16,
            'F' => 16,
        ];
    }

    public function title(): string
    {
        return 'Examens config';
    }
}
