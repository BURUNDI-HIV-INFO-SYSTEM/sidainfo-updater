@extends('layouts.app')

@section('title', 'Config. examens – SIDAInfo')
@section('heading', 'Configuration des examens biologiques')

@section('header-actions')
    <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Endpoint public :</span>
        <code class="text-xs bg-slate-100 border border-slate-200 px-2 py-1 rounded font-mono text-slate-700">
            GET /api/examens
        </code>
    </div>
@endsection

@section('content')

<p class="text-sm text-slate-500 mb-6">
    Les noms et valeurs de référence ci-dessous sont synchronisés par les sites via <code class="font-mono">/api/examens</code>.
    Les codes sont fixes — ils correspondent aux colonnes de la table <code class="font-mono">bilans</code> dans les instances locales.
</p>

{{-- Import panel --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
    <div class="flex items-start gap-4">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8"/>
        </svg>
        <div class="flex-1">
            <p class="text-sm font-medium text-blue-900 mb-1">Importer / Exporter via Excel</p>
            <p class="text-xs text-blue-700 mb-3">
                Téléchargez le fichier actuel, modifiez les colonnes <strong>nom_examen</strong>,
                <strong>valeur_usuelle1</strong>, <strong>valeur_usuelle2</strong>, <strong>limite1</strong> et
                <strong>limite2</strong>, puis importez le fichier.
                La colonne <em>code</em> est en lecture seule — ne la modifiez pas.
            </p>
            <div class="flex items-center gap-4 flex-wrap">
                {{-- Download template --}}
                <a href="{{ route('examens-config.template') }}"
                   class="inline-flex items-center gap-2 bg-white border border-blue-300 hover:border-blue-400 text-blue-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Télécharger la configuration actuelle
                </a>

                {{-- Upload form --}}
                <form method="POST" action="{{ route('examens-config.import') }}" enctype="multipart/form-data"
                      class="flex items-center gap-2">
                    @csrf
                    <label class="inline-flex items-center gap-2 bg-white border border-slate-300 hover:border-slate-400
                                  text-slate-700 text-sm px-4 py-2 rounded-lg cursor-pointer transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span id="ec-file-label">Choisir un fichier .xlsx</span>
                        <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" class="hidden"
                               onchange="document.getElementById('ec-file-label').textContent = this.files[0]?.name ?? 'Choisir un fichier .xlsx';
                                         document.getElementById('ec-import-btn').disabled = false;">
                    </label>
                    <button id="ec-import-btn" type="submit" disabled
                            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed
                                   text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Importer
                    </button>
                </form>
            </div>
            @error('excel_file')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

{{-- Manual edit form --}}
<form method="POST" action="{{ route('examens-config.update') }}">
    @csrf

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-3 border-b border-slate-100 bg-slate-50">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Saisie manuelle</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-4 py-3 font-semibold text-slate-600 w-36">Code</th>
                    <th class="px-4 py-3 font-semibold text-slate-600 w-52">Nom affiché</th>
                    <th class="px-4 py-3 font-semibold text-slate-600 text-center" colspan="2">Valeurs usuelles</th>
                    <th class="px-4 py-3 font-semibold text-slate-600 text-center" colspan="2">Limites critiques</th>
                </tr>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-4 py-2"></th>
                    <th class="px-4 py-2"></th>
                    <th class="px-4 py-2 text-xs font-normal text-slate-400 text-center">Min</th>
                    <th class="px-4 py-2 text-xs font-normal text-slate-400 text-center">Max</th>
                    <th class="px-4 py-2 text-xs font-normal text-slate-400 text-center">Basse</th>
                    <th class="px-4 py-2 text-xs font-normal text-slate-400 text-center">Haute</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($examens as $examen)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $examen->code }}</td>
                    <td class="px-4 py-2.5">
                        <input type="text"
                               name="examens[{{ $examen->code }}][nom_examen]"
                               value="{{ $examen->nom_examen }}"
                               required
                               class="w-full border border-slate-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </td>
                    @foreach(['valeur_usuelle1','valeur_usuelle2','limite1','limite2'] as $field)
                    <td class="px-4 py-2.5">
                        <input type="number"
                               name="examens[{{ $examen->code }}][{{ $field }}]"
                               value="{{ $examen->$field !== null ? rtrim(rtrim($examen->$field, '0'), '.') : '' }}"
                               step="any"
                               placeholder="—"
                               class="w-full border border-slate-200 rounded px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
            Enregistrer la configuration
        </button>
    </div>
</form>

@endsection
