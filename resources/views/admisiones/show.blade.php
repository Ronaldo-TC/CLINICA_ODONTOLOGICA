@extends('layouts.app')

@php
    $pac = $registro->paciente ?? null;
    $edad = $pac->edad ?? null;
@endphp

@section('title', 'Ficha de admisión')
@section('page-title', 'Ficha de admisión')
@section('page-subtitle', 'Expediente clínico')


@section('content')
<div class="ficha-compact">

    {{-- ============ TOOLBAR ============ --}}
    <div class="ficha-toolbar" role="toolbar" aria-label="Acciones de la ficha">
        <a class="btn btn-secondary" href="{{ route('admisiones.index') }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/>
            </svg>
            Volver
        </a>
        <div class="spacer"></div>
        <a class="btn btn-secondary" href="{{ route('admisiones.ficha-pdf', ['admision' => $registro->id, 'formato' => 'pdf']) }}">PDF de la ficha</a>
        <a class="btn btn-secondary" href="{{ route('admisiones.reporte-paciente', ['admision' => $registro->id, 'formato' => 'pdf']) }}">PDF paciente</a>
        <a class="btn btn-secondary" href="{{ route('admisiones.reporte-paciente', $registro->id) }}" target="_blank" rel="noopener">Vista reporte</a>
        @can('admisiones.editar')
            <a class="btn btn-primary" href="{{ route('admisiones.edit', $registro->id) }}">Editar</a>
        @endcan
    </div>
    <div class="ficha-toolbar-separator" role="presentation"></div>
    <div class="ficha-toolbar-separator" role="presentation"></div>
    <div class="ficha-toolbar-separator" role="presentation"></div>




    {{-- ============ HEADER PACIENTE ============ --}}
    <div class="ficha-header" style="display: flex; padding-top: 2rem;">

        <div class="ficha-header-main">
            <div class="ficha-avatar" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($pac->nombre_completo ?? 'P', 0, 1)) }}
            </div>
            <div style="min-width:0;">
                <div class="ficha-name">{{ $pac->nombre_completo ?? 'Paciente sin nombre' }}</div>
                <div class="ficha-sub">
                    Ficha #{{ $registro->id }}
                    @if($registro->created_at) · {{ $registro->created_at->format('d/m/Y H:i') }} @endif
                    · {{ $registro->periodo->nombre ?? 'Sin periodo' }}
                </div>
            </div>
        </div>
        <div class="ficha-meta">
            <div class="ficha-meta-item">
                <div class="ficha-meta-label">CI / Documento</div>
                <div class="ficha-meta-value">{{ $pac->ci ?? '—' }}</div>
            </div>
            <div class="ficha-meta-item">
                <div class="ficha-meta-label">Nacimiento / Edad</div>
                <div class="ficha-meta-value">
                    {{ $pac->fecha_nacimiento ? $pac->fecha_nacimiento->format('d/m/Y') : '—' }}
                    {{ $edad !== null ? '('.$edad.'a)' : '' }}
                </div>
            </div>
            <div class="ficha-meta-item">
                <div class="ficha-meta-label">Sexo</div>
                <div class="ficha-meta-value">{{ $pac->genero ?? '—' }}</div>
            </div>
            <div class="ficha-meta-item">
                <div class="ficha-meta-label">Estado</div>
                <div class="ficha-meta-value">
                    @if($registro->autorizado ?? false)
                        <span class="f-badge f-badge-ok">Autorizada</span>
                    @else
                        <span class="f-badge f-badge-warn">Pendiente</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============ EXPEDIENTE ============ --}}
    <div class="ficha-card" id="ficha-detalle">
        <div class="ficha-card-header">
            <div class="icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                    <path d="M9 12h6M9 16h6"/>
                </svg>
            </div>
            Expediente completo
        </div>
        <div class="ficha-card-body">
            @include('reportes.partials.ficha-admision', ['ficha' => $registro, 'soloFicha' => true, 'mostrar3d' => true])

            @canany(['admisiones.autorizar','admisiones.gestionar'])
                @if(auth()->user()->hasAnyRole(['admin', 'subadmin']) || $registro->responsable_id === auth()->id())
                    <div class="ficha-actions">
                        <button class="btn btn-primary" id="autorizar-ficha" type="button">
                            <span class="btn-spinner" aria-hidden="true"></span>
                            <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <span class="btn-label">Autorizar ficha</span>
                        </button>
                        <span id="ficha-resultado" class="ficha-actions-status" role="status" aria-live="polite"></span>
                    </div>
                @endif
            @endcanany
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    #ficha-detalle .ficha-odontograma-selector {
        display: inline-flex;
        gap: 3px;
        margin: 0 0 10px;
        padding: 3px;
        border: 1px solid var(--line, #dce6e2);
        border-radius: 10px;
        background: var(--surface, #fff);
    }
    #ficha-detalle .ficha-odontograma-selector button {
        min-width: 92px;
        padding: 7px 13px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: var(--muted, #60736f);
        font: inherit;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    #ficha-detalle .ficha-odontograma-selector button[aria-selected="true"] {
        background: var(--teal, #087f79);
        color: #fff;
        box-shadow: 0 2px 8px rgba(8, 127, 121, .2);
    }
    #ficha-detalle .ficha-odontograma-3d[hidden] { display: none; }
    #ficha-detalle .ficha-odontograma-3d {
        overflow: hidden;
        border: 1px solid var(--line, #dce6e2);
        border-radius: 12px;
        background: var(--surface, #fff);
    }
    #ficha-detalle .ficha-odontograma-3d-toolbar {
        display: flex;
        align-items: center;
        gap: 9px;
        flex-wrap: wrap;
        padding: 10px;
        border-bottom: 1px solid var(--line, #dce6e2);
        background: var(--surface-soft, #edf3f1);
    }
    #ficha-detalle .ficha-odontograma-3d-toolbar label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--muted, #60736f);
        font-size: 11px;
        font-weight: 700;
    }
    #ficha-detalle .ficha-odontograma-3d-toolbar select {
        min-height: 32px;
        padding: 5px 26px 5px 8px;
        border: 1px solid var(--line, #dce6e2);
        border-radius: 7px;
        background: var(--surface, #fff);
        color: var(--ink, #1b2e2b);
    }
    #ficha-detalle .ficha-odontograma-3d-toolbar .btn { min-height: 32px; padding: 5px 10px; font-size: 11px; }
    #ficha-detalle .ficha-odontograma-check { white-space: nowrap; }
    #ficha-detalle .ficha-odontograma-3d-stage {
        position: relative;
        height: clamp(360px, 52vw, 560px);
        overflow: hidden;
        background: radial-gradient(circle at 50% 38%, #31465a 0, #192633 46%, #101820 100%);
    }
    #ficha-detalle .ficha-odontograma-3d-stage canvas { display: block; width: 100%; height: 100%; touch-action: none; cursor: grab; }
    #ficha-detalle .ficha-odontograma-3d-stage canvas:active { cursor: grabbing; }
    #ficha-detalle .ficha-odontograma-3d-status {
        position: absolute;
        left: 12px;
        bottom: 12px;
        max-width: calc(100% - 24px);
        padding: 7px 10px;
        border-radius: 8px;
        background: rgba(15, 23, 42, .78);
        color: #e2e8f0;
        font-size: 11px;
        backdrop-filter: blur(6px);
        pointer-events: none;
    }
    #ficha-detalle .ficha-odontograma-3d-detalle { min-height: 38px; padding: 10px 12px; color: var(--ink, #1b2e2b); }
    #ficha-detalle .ficha-odontograma-3d:fullscreen { display: flex; flex-direction: column; padding: 14px; background: var(--surface, #fff); }
    #ficha-detalle .ficha-odontograma-3d:fullscreen .ficha-odontograma-3d-stage { flex: 1; height: auto; min-height: 260px; }
    @media (max-width: 720px) {
        #ficha-detalle .ficha-odontograma-selector { display: flex; }
        #ficha-detalle .ficha-odontograma-selector button { flex: 1; }
        #ficha-detalle .ficha-odontograma-3d-toolbar { align-items: stretch; }
        #ficha-detalle .ficha-odontograma-3d-toolbar label:not(.ficha-odontograma-check) { flex: 1 1 180px; justify-content: space-between; }
        #ficha-detalle .ficha-odontograma-3d-stage { height: 390px; }
    }
</style>
@endpush

@push('scripts')
@vite('resources/js/odontograma-ficha-3d.js')
<script>
document.getElementById('autorizar-ficha')?.addEventListener('click', async function () {
    const btn = this;
    const resultado = document.getElementById('ficha-resultado');
    const originalLabel = btn.querySelector('.btn-label')?.textContent || 'Autorizar ficha';

    btn.disabled = true;
    btn.classList.add('loading');
    resultado.textContent = '';
    resultado.classList.remove('is-success', 'is-error');

    try {
        const response = await fetch("{{ route('admisiones.autorizar', $registro->id) }}", {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();

        if (data.errors) {
            resultado.textContent = Object.values(data.errors).flat().join(' ');
            resultado.classList.add('is-error');
        } else {
            resultado.textContent = data.message || 'Ficha autorizada correctamente.';
            resultado.classList.add('is-success');
            if (response.ok) {
                setTimeout(() => location.reload(), 800);
            }
        }
    } catch (e) {
        resultado.textContent = 'No se pudo autorizar. Intente nuevamente.';
        resultado.classList.add('is-error');
    } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
        const label = btn.querySelector('.btn-label');
        if (label) label.textContent = originalLabel;
    }
});
</script>
@endpush
