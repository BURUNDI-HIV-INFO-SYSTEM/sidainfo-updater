<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TarifCentral;
use Illuminate\Http\Request;

class TarifsController extends Controller
{
    public function index(Request $request)
    {
        $annee = (int) $request->input('annee', date('Y'));

        $tarifs = TarifCentral::where('annee', $annee)
            ->where('prix', '>', 0)
            ->select('code_examen', 'prix', 'devise', 'annee')
            ->orderBy('code_examen')
            ->get();

        return response()->json($tarifs);
    }
}
