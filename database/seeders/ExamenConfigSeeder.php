<?php

namespace Database\Seeders;

use App\Models\ExamenConfig;
use Illuminate\Database\Seeder;

class ExamenConfigSeeder extends Seeder
{
    public function run(): void
    {
        $examens = [
            // NFS / Hématologie
            ['code' => 'nfs_lymphocyte',   'nom_examen' => 'CD4 / Lymphocytes'],
            ['code' => 'nfs_hemoglobine',  'nom_examen' => 'Hémoglobine'],
            ['code' => 'nfs_globule_blanc','nom_examen' => 'Globules blancs'],
            ['code' => 'nfs_plaquette',    'nom_examen' => 'Plaquettes'],
            ['code' => 'neutrophyl',       'nom_examen' => 'Neutrophiles'],
            ['code' => 'vs',               'nom_examen' => 'Vitesse de sédimentation'],

            // Biochimie
            ['code' => 'uree',             'nom_examen' => 'Urée'],
            ['code' => 'creatinine',       'nom_examen' => 'Créatinine'],
            ['code' => 'glycemie',         'nom_examen' => 'Glycémie'],
            ['code' => 'acide_urique',     'nom_examen' => 'Acide urique'],
            ['code' => 'calcemie',         'nom_examen' => 'Calcémie'],
            ['code' => 'bil_total',        'nom_examen' => 'Bilirubine totale'],
            ['code' => 'bil_conjugue',     'nom_examen' => 'Bilirubine conjuguée'],
            ['code' => 'crp',              'nom_examen' => 'CRP'],
            ['code' => 'albumine',         'nom_examen' => 'Albumine'],

            // Enzymes hépatiques
            ['code' => 'ast',              'nom_examen' => 'ASAT'],
            ['code' => 'alt',              'nom_examen' => 'ALAT'],
            ['code' => 'pal',              'nom_examen' => 'PAL'],
            ['code' => 'ldh',              'nom_examen' => 'LDH'],
            ['code' => 'cpk',              'nom_examen' => 'CPK'],
            ['code' => 'gamma_gt',         'nom_examen' => 'Gamma GT'],
            ['code' => 'amylasemie',       'nom_examen' => 'Amylasémie'],
            ['code' => 'amylasurie',       'nom_examen' => 'Amylasurie'],

            // Lipides / Coagulation
            ['code' => 'hdl_cholesterol',  'nom_examen' => 'HDL-cholestérol'],
            ['code' => 'total_cholesterol','nom_examen' => 'Cholestérol total'],
            ['code' => 'cholesterol_ldl',  'nom_examen' => 'LDL-cholestérol'],
            ['code' => 'tryglyceride',     'nom_examen' => 'Triglycérides'],
            ['code' => 'tp',               'nom_examen' => 'Taux de prothrombine'],
            ['code' => 'tck',              'nom_examen' => 'TCK'],

            // Sérologie / Microbiologie
            ['code' => 'charge_virale',    'nom_examen' => 'Charge virale'],
            ['code' => 'pcr',              'nom_examen' => 'PCR'],
            ['code' => 'aslo',             'nom_examen' => 'ASLO'],
            ['code' => 'widal',            'nom_examen' => 'Widal'],
            ['code' => 'aghbs',            'nom_examen' => 'AgHBs'],
            ['code' => 'achvc',            'nom_examen' => 'AcHVC'],
            ['code' => 'vdlr',             'nom_examen' => 'VDRL'],
            ['code' => 'tpha',             'nom_examen' => 'TPHA'],
            ['code' => 'toxo',             'nom_examen' => 'Toxoplasmose'],
            ['code' => 'arthritest',       'nom_examen' => 'Arthritest'],
            ['code' => 'malaria',          'nom_examen' => 'Paludisme / Malaria'],
            ['code' => 'ecbu',             'nom_examen' => 'ECBU'],
            ['code' => 'groupe_sanguin',   'nom_examen' => 'Groupe sanguin'],
        ];

        foreach ($examens as $examen) {
            ExamenConfig::updateOrCreate(
                ['code' => $examen['code']],
                ['nom_examen' => $examen['nom_examen']]
            );
        }
    }
}
