<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamenConfig;

class ExamensController extends Controller
{
    public function index()
    {
        $examens = ExamenConfig::select(
            'code',
            'nom_examen',
            'valeur_usuelle1',
            'valeur_usuelle2',
            'limite1',
            'limite2'
        )->orderBy('code')->get();

        return response()->json($examens);
    }
}
