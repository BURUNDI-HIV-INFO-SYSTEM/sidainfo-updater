<?php

namespace App\Exports;

use App\Models\ExamenConfig;
use App\Models\TarifCentral;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class TarifsTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    private int $annee;
    private Collection $existingTarifs;

    public function __construct(int $annee)
    {
        $this->annee = $annee;
        $this->existingTarifs = TarifCentral::where('annee', $annee)
            ->pluck('prix', 'code_examen');
    }

    public function collection(): Collection
    {
        return ExamenConfig::orderBy('code')->get()->map(function ($examen) {
            $prix = $this->existingTarifs->get($examen->code);
            return [
                'code_examen' => $examen->code,
                'nom_examen'  => $examen->nom_examen,
                'prix_bif'    => $prix !== null && $prix > 0 ? number_format((float) $prix, 2, '.', '') : '',
                'annee'       => $this->annee,
            ];
        });
    }

    public function headings(): array
    {
        return ['code_examen', 'nom_examen', 'prix_bif', 'annee'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Bold header row
            1 => ['font' => ['bold' => true]],
            // Lock code and name columns visually (light grey fill)
            'A' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F1F5F9']]],
            'B' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F1F5F9']]],
            'D' => ['fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F1F5F9']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 34,
            'C' => 14,
            'D' => 10,
        ];
    }

    public function title(): string
    {
        return "Tarifs {$this->annee}";
    }
}
