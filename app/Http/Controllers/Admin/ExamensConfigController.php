<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExamensConfigExport;
use App\Http\Controllers\Controller;
use App\Imports\ExamensConfigImport;
use App\Models\ExamenConfig;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExamensConfigController extends Controller
{
    public function index()
    {
        $examens = ExamenConfig::orderBy('code')->get();

        return view('examens_config.index', compact('examens'));
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'examens'                   => 'required|array',
            'examens.*.nom_examen'      => 'required|string|max:100',
            'examens.*.valeur_usuelle1' => 'nullable|numeric',
            'examens.*.valeur_usuelle2' => 'nullable|numeric',
            'examens.*.limite1'         => 'nullable|numeric',
            'examens.*.limite2'         => 'nullable|numeric',
        ]);

        foreach ($request->input('examens') as $code => $data) {
            ExamenConfig::where('code', $code)->update([
                'nom_examen'      => $data['nom_examen'],
                'valeur_usuelle1' => $data['valeur_usuelle1'] !== '' ? $data['valeur_usuelle1'] : null,
                'valeur_usuelle2' => $data['valeur_usuelle2'] !== '' ? $data['valeur_usuelle2'] : null,
                'limite1'         => $data['limite1'] !== '' ? $data['limite1'] : null,
                'limite2'         => $data['limite2'] !== '' ? $data['limite2'] : null,
            ]);
        }

        return redirect()->route('examens-config.index')
            ->with('success', 'Configuration des examens mise à jour.');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ExamensConfigExport(), 'examens-config.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        $import = new ExamensConfigImport();
        Excel::import($import, $request->file('excel_file'));

        $msg = "{$import->imported} examen(s) mis à jour.";
        if ($import->skipped > 0) {
            $msg .= " {$import->skipped} ligne(s) ignorée(s) (codes inconnus : "
                . implode(', ', $import->skippedCodes) . ").";
        }

        return redirect()->route('examens-config.index')->with('success', $msg);
    }
}
