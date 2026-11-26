@extends('layouts.kunjungan')

@section('title', 'Konfirmasi Checkout')

@section('content')

@php
    // Waktu mulai: saat disetujui (fallback ke check-in / submit untuk data lama)
    $startAt = $visit->approved_at ?? $visit->checked_in_at ?? $visit->submitted_at;
@endphp

<div class="fixed inset-0 -z-10 bg-cover bg-center"
     style="background-image: url('{{ asset('images/background_visitor.jpg') }}');">
</div>

<style>
    @keyframes pulse-dot {
        0%   { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.6); }
        70%  { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    .timer-box {
        min-width: 3.6rem;
        padding: 0.6rem 0.4rem 0.4rem;
        border-radius: 0.9rem;
        background: rgba(255, 255, 255, 0.55);
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 4px 12px rgba(60, 80, 30, 0.12);
    }
    .timer-num {
        display: block;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        color: #3b4a22;
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
    .timer-label {
        display: block;
        margin-top: 0.3rem;
        font-size: 0.6rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(59, 74, 34, 0.7);
    }
    .timer-sep {
        font-size: 1.6rem;
        font-weight: 700;
        color: rgba(59, 74, 34, 0.5);
        padding-bottom: 1rem;
    }
</style>

<div class="rounded-2xl shadow-lg p-6 border border-white/50 text-center"
     style="background-color: rgba(255,255,255,0.4); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);">

    <img src="{{ asset('images/logo_jafran.png') }}"
         alt="Logo Jafran"
         class="mx-auto mb-4 object-contain"
         style="width: 80px; height: auto;">

    <h1 class="text-xl font-bold mb-1 text-corn-dark">Konfirmasi Checkout</h1>

    <p class="text-corn-dark/80 text-sm mb-5">
        Anda akan mengakhiri kunjungan sebagai <strong>{{ $visit->visitor->name }}</strong>.
    </p>

    {{-- Timer durasi kunjungan --}}
    <div class="rounded-xl mb-4 px-4 py-4"
         style="background: rgba(255,255,255,0.35); border: 1px solid rgba(255,255,255,0.6);">

        <div style="display:flex; align-items:center; justify-content:center; gap:0.5rem; margin-bottom:0.9rem;">
            <span style="width:0.6rem; height:0.6rem; border-radius:9999px; background:#22c55e; animation:pulse-dot 1.8s infinite;"></span>
            <span style="font-size:0.7rem; letter-spacing:0.14em; text-transform:uppercase; font-weight:600; color:#3b4a22;">
                Sedang berkunjung
            </span>
        </div>

        <div id="duration-timer" style="display:flex; align-items:flex-end; justify-content:center; gap:0.4rem;">
            <div class="timer-box">
                <span id="t-hours" class="timer-num">00</span>
                <span class="timer-label">Jam</span>
            </div>
            <span class="timer-sep">:</span>
            <div class="timer-box">
                <span id="t-minutes" class="timer-num">00</span>
                <span class="timer-label">Menit</span>
            </div>
            <span class="timer-sep">:</span>
            <div class="timer-box">
                <span id="t-seconds" class="timer-num">00</span>
                <span class="timer-label">Detik</span>
            </div>
        </div>

        <p class="text-corn-dark/80 text-xs" style="margin-top:0.9rem;">
            Masuk pukul
            <strong>{{ $startAt?->format('H:i') ?? '-' }}</strong>
            &middot; {{ $startAt?->translatedFormat('d M Y') ?? '-' }}
        </p>
    </div>

    {{-- Detail kunjungan --}}
    <div class="rounded-xl mb-5 px-4 py-3 text-left text-sm text-corn-dark"
         style="background: rgba(255,255,255,0.35);">
        <p><span class="font-semibold">Keperluan:</span> {{ $visit->purpose ?? '-' }}</p>

        @if ($visit->department)
            <p><span class="font-semibold">Departemen:</span> {{ $visit->department->name }}</p>
        @endif

        @if ($visit->employee)
            <p><span class="font-semibold">Bertemu:</span> {{ $visit->employee->name }}</p>
        @endif
    </div>

    <form method="POST" action="{{ route('kunjungan.store-checkout') }}">
        @csrf
        <button type="submit"
                class="w-full py-3 rounded-lg text-white font-semibold text-sm"
                style="background-color: #4a5a2e;">
            Ya, Checkout Sekarang
        </button>
    </form>

</div>

@if ($startAt)
<script>
    (function () {
        const startMs = new Date("{{ $startAt->toIso8601String() }}").getTime();
        const serverNowMs = new Date("{{ now()->toIso8601String() }}").getTime();

        // Pakai jam server sebagai acuan, supaya akurat walau jam perangkat tamu salah
        const loadedAt = performance.now();

        const elHours = document.getElementById('t-hours');
        const elMinutes = document.getElementById('t-minutes');
        const elSeconds = document.getElementById('t-seconds');

        const pad = (n) => String(n).padStart(2, '0');

        function tick() {
            const nowMs = serverNowMs + (performance.now() - loadedAt);
            let diff = Math.max(0, Math.floor((nowMs - startMs) / 1000));

            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;

            elHours.textContent = pad(h);
            elMinutes.textContent = pad(m);
            elSeconds.textContent = pad(s);
        }

        tick();
        setInterval(tick, 1000);
    })();
</script>
@endif

@endsection