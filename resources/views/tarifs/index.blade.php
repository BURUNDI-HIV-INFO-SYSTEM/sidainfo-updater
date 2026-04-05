@extends('layouts.app')

@section('title', 'Tarifs des examens – SIDAInfo')
@section('heading', 'Tarifs des examens biologiques')

@section('header-actions')
    <div class="flex items-center gap-2">
        <span class="text-sm text-slate-500">Endpoint public :</span>
        <code class="text-xs bg-slate-100 border border-slate-200 px-2 py-1 rounded font-mono text-slate-700">
            GET /api/tarifs?annee={{ $annee }}
        </code>
    </div>
@endsection

@section('content')

{{-- Year selector --}}
<div class="flex items-center gap-4 mb-6">
    <label class="text-sm font-medium text-slate-700">Année tarifaire :</label>
    @php $years = range(date('Y') + 1, 2020); @endphp
    <select onchange="window.location='{{ route('tarifs.index') }}?annee='+this.value"
            class="text-sm border border-slate-300 rounded-lg px-3 py-2 bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
        @foreach($years as $y)
            <option value="{{ $y }}" {{ $y == $annee ? 'selected' : '' }}>{{ $y }}</option>
        @endforeach
    </select>
</div>

{{-- Import panel --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
    <div class="flex items-start gap-4">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8"/>
        </svg>
        <div class="flex-1">
            <p class="text-sm font-medium text-blue-900 mb-1">Importer depuis Excel</p>
            <p class="text-xs text-blue-700 mb-3">
                Téléchargez le modèle pré-rempli, saisissez les prix dans la colonne <strong>prix_bif</strong>, puis importez le fichier.
                Les colonnes <em>code_examen</em>, <em>nom_examen</em> et <em>annee</em> sont en lecture seule — ne les modifiez pas.
            </p>
            <div class="flex items-center gap-4 flex-wrap">
                {{-- Download template --}}
                <a href="{{ route('tarifs.template', ['annee' => $annee]) }}"
                   class="inline-flex items-center gap-2 bg-white border border-blue-300 hover:border-blue-400 text-blue-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Télécharger le modèle ({{ $annee }})
                </a>

                {{-- Upload form --}}
                <form method="POST" action="{{ route('tarifs.import') }}" enctype="multipart/form-data"
                      class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="annee" value="{{ $annee }}">
                    <label class="inline-flex items-center gap-2 bg-white border border-slate-300 hover:border-slate-400
                                  text-slate-700 text-sm px-4 py-2 rounded-lg cursor-pointer transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span id="file-label">Choisir un fichier .xlsx</span>
                        <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" class="hidden"
                               onchange="document.getElementById('file-label').textContent = this.files[0]?.name ?? 'Choisir un fichier .xlsx';
                                         document.getElementById('import-btn').disabled = false;">
                    </label>
                    <button id="import-btn" type="submit" disabled
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
<form method="POST" action="{{ route('tarifs.upsert') }}">
    @csrf
    <input type="hidden" name="annee" value="{{ $annee }}">

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Saisie manuelle</span>
            <span class="text-xs text-slate-400">Seuls les prix &gt; 0 sont exposés par l'API.</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-6 py-3 font-semibold text-slate-600 w-40">Code</th>
                    <th class="px-6 py-3 font-semibold text-slate-600">Examen</th>
                    <th class="px-6 py-3 font-semibold text-slate-600 w-48">Prix (BIF)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($examens as $examen)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $examen->code }}</td>
                    <td class="px-6 py-3 text-slate-700">{{ $examen->nom_examen }}</td>
                    <td class="px-6 py-3">
                        <input type="number"
                               name="prix[{{ $examen->code }}]"
                               value="{{ isset($tarifs[$examen->code]) ? number_format((float)$tarifs[$examen->code], 2, '.', '') : '' }}"
                               min="0"
                               step="0.01"
                               placeholder="0.00"
                               class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
            Enregistrer les tarifs {{ $annee }}
        </button>
    </div>
</form>

@endsection
