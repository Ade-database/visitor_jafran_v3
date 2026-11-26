@extends('layouts.kunjungan')

@section('title', 'Terima Kasih')

@section('content')

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

    @if (session('info'))

        <div class="mx-auto mb-4 flex items-center justify-center rounded-full"
             style="width:3.2rem; height:3.2rem; background:rgba(34,197,94,0.2);">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24"
                 stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>

        <p class="text-corn-dark font-medium mb-4">
            {{ session('info') }}
        </p>

        <a href="{{ route('kunjungan.phone') }}"
           class="inline-block px-5 py-2 rounded-lg text-white text-sm font-medium"
           style="background-color: #4a5a2e;">
            Kembali ke Halaman Awal
        </a>

        <p class="text-corn-dark/70 text-xs mt-3">
            Halaman ini akan kembali ke awal secara otomatis.
        </p>

        <script>
            setTimeout(function () {
                window.location.href = "{{ route('kunjungan.phone') }}";
            }, 10000);
        </script>

    @else

        <h1 id="status-title" class="text-xl font-bold mb-2 text-corn-dark">
            Menunggu Persetujuan
        </h1>

        <p id="status-description" class="text-corn-dark/80 text-sm mb-6">
            Mohon tunggu, data Anda sedang diproses oleh admin.
        </p>

        {{-- Timer durasi kunjungan (muncul setelah disetujui) --}}
        <div id="timer-card" class="hidden rounded-xl mb-5 px-4 py-4"
             style="background: rgba(255,255,255,0.35); border: 1px solid rgba(255,255,255,0.6);">

            <div style="display:flex; align-items:center; justify-content:center; gap:0.5rem; margin-bottom:0.9rem;">
                <span style="width:0.6rem; height:0.6rem; border-radius:9999px; background:#22c55e; animation:pulse-dot 1.8s infinite;"></span>
                <span style="font-size:0.7rem; letter-spacing:0.14em; text-transform:uppercase; font-weight:600; color:#3b4a22;">
                    Sedang berkunjung
                </span>
            </div>

            <div style="display:flex; align-items:flex-end; justify-content:center; gap:0.4rem;">
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

            <p id="timer-since" class="text-corn-dark/80 text-xs" style="margin-top:0.9rem;"></p>
        </div>

        <div id="status-badge"
             class="inline-block px-4 py-2 rounded-full bg-corn-gold/20 text-corn-dark font-medium mb-2">
            Status:
            <span id="status-text">Menunggu Persetujuan</span>
        </div>

        {{-- Tombol checkout (hanya saat disetujui) --}}
        <div id="checkout-section" class="hidden mt-4">
            <p class="text-corn-dark/80 text-xs mb-3">
                Sudah selesai berkunjung? Tekan tombol di bawah untuk checkout.
            </p>
            <button id="checkout-button"
                    type="button"
                    onclick="openCheckoutModal()"
                    class="block w-full px-5 py-3 rounded-lg text-white text-sm font-semibold shadow transition hover:opacity-90"
                    style="background-color: #b91c1c;">
                Checkout Sekarang
            </button>
        </div>

        {{-- Alasan penolakan (hanya saat ditolak) --}}
        <div id="rejection-box"
             class="hidden mt-4 text-left rounded-xl border border-red-200 bg-red-50/80 p-4">
            <p class="text-xs font-semibold text-red-700 mb-1">Alasan penolakan:</p>
            <p id="rejection-reason" class="text-sm text-red-800"></p>
        </div>

        {{-- Tombol kembali (hanya saat ditolak / selesai) --}}
        <div id="retry-button" class="hidden mt-5">
            <a href="{{ route('kunjungan.phone') }}"
               class="inline-block px-5 py-2 rounded-lg text-white text-sm font-medium"
               style="background-color: #4a5a2e;">
                Kembali ke Halaman Awal
            </a>
        </div>

    @endif

</div>

@if (! session('info'))

{{-- Pop up checkout + ulasan (satu langkah) --}}
<div id="checkout-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.45); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);"
     onclick="if (event.target === this) closeCheckoutModal()">

    <form method="POST"
          action="{{ route('kunjungan.store-checkout') }}"
          onsubmit="this.querySelector('button[type=submit]').disabled = true;"
          class="w-full text-center rounded-2xl shadow-2xl p-6"
          style="max-width: 24rem; background: rgba(255,255,255,0.94); border: 1px solid rgba(255,255,255,0.8);">
        @csrf

        <div class="mx-auto mb-3 flex items-center justify-center rounded-full"
             style="width:3.2rem; height:3.2rem; background:#fee2e2;">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24"
                 stroke="#b91c1c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </div>

        <h2 class="text-lg font-bold text-corn-dark mb-1">Konfirmasi Checkout</h2>
        <p class="text-sm text-corn-dark/80 mb-4">
            Apakah Anda yakin ingin mengakhiri kunjungan sekarang?
        </p>

        {{-- Bintang --}}
        <p class="text-xs font-semibold text-corn-dark mb-1">Bagaimana pengalaman Anda? (opsional)</p>
        <input type="hidden" name="rating" id="rating-input" value="">
        <div id="star-row" style="display:flex; justify-content:center; gap:0.2rem; margin-bottom:0.75rem;">
            @for ($i = 1; $i <= 5; $i++)
                <button type="button"
                        class="star-btn"
                        onclick="setRating({{ $i }})"
                        aria-label="{{ $i }} bintang"
                        style="font-size:2rem; line-height:1; color:#d1d5db; background:none; border:none; cursor:pointer; padding:0 0.1rem;">
                    ★
                </button>
            @endfor
        </div>

        {{-- Ulasan --}}
        <textarea name="comment"
                  rows="3"
                  maxlength="1000"
                  placeholder="Tulis ulasan Anda (opsional)"
                  class="w-full rounded-lg text-sm text-left p-3 mb-4"
                  style="border:1px solid #d1d5db; background:rgba(255,255,255,0.9); resize:none;"></textarea>

        <div style="display:flex; gap:0.6rem;">
            <button type="button"
                    onclick="closeCheckoutModal()"
                    class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition hover:opacity-90"
                    style="background:#e5e7eb; color:#374151;">
                Batal
            </button>
            <button type="submit"
                    class="flex-1 px-4 py-2 rounded-lg text-white text-sm font-semibold transition hover:opacity-90 disabled:opacity-60"
                    style="background-color:#b91c1c;">
                Checkout
            </button>
        </div>
    </form>
</div>

<script>
    function openCheckoutModal() {
        document.getElementById('checkout-modal').classList.remove('hidden');
    }
    function closeCheckoutModal() {
        document.getElementById('checkout-modal').classList.add('hidden');
    }
    function setRating(n) {
        const input = document.getElementById('rating-input');
        // klik bintang yang sama sekali lagi = hapus penilaian
        const value = (String(input.value) === String(n)) ? '' : n;
        input.value = value;

        document.querySelectorAll('#star-row .star-btn').forEach((btn, i) => {
            btn.style.color = (value !== '' && i < value) ? '#f59e0b' : '#d1d5db';
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeCheckoutModal();
    });
</script>

<script>
    let pollTimer = null;
    let timerInterval = null;

    const pad = (n) => String(n).padStart(2, '0');

    function startTimer(approvedAtIso, serverNowIso) {
        if (timerInterval) return; // sudah berjalan

        const startMs = new Date(approvedAtIso).getTime();
        const serverNowMs = new Date(serverNowIso).getTime();
        const loadedAt = performance.now();

        const elHours = document.getElementById('t-hours');
        const elMinutes = document.getElementById('t-minutes');
        const elSeconds = document.getElementById('t-seconds');
        const since = document.getElementById('timer-since');

        const startDate = new Date(approvedAtIso);
        since.innerHTML = 'Masuk pukul <strong>'
            + pad(startDate.getHours()) + ':' + pad(startDate.getMinutes())
            + '</strong>';

        function tick() {
            const nowMs = serverNowMs + (performance.now() - loadedAt);
            const diff = Math.max(0, Math.floor((nowMs - startMs) / 1000));

            elHours.textContent = pad(Math.floor(diff / 3600));
            elMinutes.textContent = pad(Math.floor((diff % 3600) / 60));
            elSeconds.textContent = pad(diff % 60);
        }

        tick();
        timerInterval = setInterval(tick, 1000);
    }

    function pollStatus() {

        fetch("{{ route('kunjungan.status') }}")
            .then(response => response.json())
            .then(data => {

                if (!data.status) return;

                const status = data.status;

                const title = document.getElementById('status-title');
                const description = document.getElementById('status-description');
                const statusText = document.getElementById('status-text');
                const badge = document.getElementById('status-badge');
                const timerCard = document.getElementById('timer-card');
                const checkoutSection = document.getElementById('checkout-section');
                const rejectionBox = document.getElementById('rejection-box');
                const rejectionReason = document.getElementById('rejection-reason');
                const retryButton = document.getElementById('retry-button');

                if (status === 'pending') {

                    title.textContent = 'Menunggu Persetujuan';
                    description.textContent = 'Mohon tunggu, data Anda sedang diproses oleh admin.';
                    statusText.textContent = 'Menunggu Persetujuan';
                    badge.className = 'inline-block px-4 py-2 rounded-full bg-corn-gold/20 text-corn-dark font-medium mb-2';

                    timerCard.classList.add('hidden');
                    checkoutSection.classList.add('hidden');
                    rejectionBox.classList.add('hidden');
                    retryButton.classList.add('hidden');

                } else if (status === 'approved') {

                    title.textContent = 'Kunjungan Disetujui';
                    description.textContent = 'Kunjungan Anda telah disetujui. Silakan melanjutkan sesuai keperluan Anda.';
                    statusText.textContent = 'Disetujui';
                    badge.className = 'inline-block px-4 py-2 rounded-full bg-corn-green/20 text-corn-dark font-medium mb-2';

                    rejectionBox.classList.add('hidden');
                    retryButton.classList.add('hidden');
                    checkoutSection.classList.remove('hidden');

                    if (data.approved_at) {
                        timerCard.classList.remove('hidden');
                        startTimer(data.approved_at, data.server_now);
                    }

                    // Polling tetap jalan supaya terdeteksi kalau admin checkout-kan tamu

                } else if (status === 'rejected') {

                    title.textContent = 'Kunjungan Ditolak';
                    description.textContent = 'Mohon maaf, permohonan kunjungan Anda belum dapat disetujui.';
                    statusText.textContent = 'Ditolak';
                    badge.className = 'inline-block px-4 py-2 rounded-full bg-red-100 text-red-700 font-medium mb-2';

                    timerCard.classList.add('hidden');
                    checkoutSection.classList.add('hidden');
                    rejectionReason.textContent = data.rejected_reason || '-';
                    rejectionBox.classList.remove('hidden');
                    retryButton.classList.remove('hidden');

                    clearInterval(pollTimer);

                } else if (status === 'checked_out') {

                    title.textContent = 'Kunjungan Selesai';
                    description.textContent = 'Terima kasih sudah berkunjung ke PT. Jafran Indonesia.';
                    statusText.textContent = 'Selesai';
                    badge.className = 'inline-block px-4 py-2 rounded-full bg-corn-green/20 text-corn-dark font-medium mb-2';

                    timerCard.classList.add('hidden');
                    checkoutSection.classList.add('hidden');
                    retryButton.classList.remove('hidden');

                    clearInterval(timerInterval);
                    clearInterval(pollTimer);
                }

            })
            .catch(error => {
                console.error('Gagal mengambil status:', error);
            });
    }

    // Cek status setiap 5 detik
    pollTimer = setInterval(pollStatus, 5000);

    // Cek status langsung saat halaman dibuka
    pollStatus();
</script>

@endif

@endsection