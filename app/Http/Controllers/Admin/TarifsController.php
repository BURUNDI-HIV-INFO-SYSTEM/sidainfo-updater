<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TarifsTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\TarifsImport;
use App\Models\ExamenConfig;
use App\Models\TarifCentral;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TarifsController extends Controller
{
    public function index(Request $request)
    {
        $annee = (int) $request->input('annee', date('Y'));

        $examens = ExamenConfig::orderBy('code')->get();

        // Index existing tarifs for this year by code for fast lookup
        $tarifs = TarifCentral::where('annee', $annee)
            ->pluck('prix', 'code_examen');

        return view('tarifs.index', compact('examens', 'tarifs', 'annee'));
    }

    public function upsert(Request $request)
    {
        $annee = (int) $request->input('annee', date('Y'));

        $request->validate([
            'annee'  => 'required|integer|min:2000|max:2100',
            'prix'   => 'required|array',
            'prix.*' => 'nullable|numeric|min:0',
        ]);

        $prices = $request->input('prix', []);

        foreach ($prices as $code => $prix) {
            $prix = $prix === '' || $prix === null ? 0 : (float) $prix;

            TarifCentral::updateOrCreate(
                ['code_examen' => $code, 'annee' => $annee],
                ['prix' => $prix, 'devise' => 'BIF']
            );
        }

        return redirect()->route('tarifs.index', ['annee' => $annee])
            ->with('success', "Tarifs {$annee} enregistrés avec succès.");
    }

    public function downloadTemplate(Request $request)
    {
        $annee = (int) $request->input('annee', date('Y'));

        return Excel::download(
            new TarifsTemplateExport($annee),
            "tarifs-{$annee}.xlsx"
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'annee'      => 'required|integer|min:2000|max:2100',
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        $annee = (int) $request->input('annee');

        $import = new TarifsImport($annee);
        Excel::import($import, $request->file('excel_file'));

        $msg = "{$import->imported} tarif(s) importé(s) pour {$annee}.";
        if ($import->skipped > 0) {
            $msg .= " {$import->skipped} ligne(s) ignorée(s) (codes inconnus : "
                . implode(', ', $import->skippedCodes) . ").";
        }

        return redirect()->route('tarifs.index', ['annee' => $annee])
            ->with('success', $msg);
    }
}
