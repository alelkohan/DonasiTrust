{{-- Komponen Pasang Aplikasi PWA (Metode & Layout Identik Salsabila Admin/Pegawai) --}}
<style>
    #pwa-install-banner {
        position: fixed;
        bottom: 1.25rem;
        right: 1.25rem;
        left: 1.25rem;
        max-width: 410px;
        margin-left: auto;
        z-index: 99999;
        background-color: #1b182a;
        color: #f4f4f5;
        padding: 1rem 1.125rem;
        border-radius: 1rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.15);
        font-family: inherit;
        box-sizing: border-box;
        backdrop-filter: blur(16px);
    }

    html.theme-light #pwa-install-banner {
        background-color: #ffffff !important;
        color: #0f172a !important;
        border-color: #cbd5e1 !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15) !important;
    }

    @media (min-width: 640px) {
        #pwa-install-banner {
            left: auto;
        }
    }

    .pwa-banner-flex {
        display: flex;
        align-items: flex-start;
        gap: 0.875rem;
    }

    .pwa-icon-box {
        background-color: #231f36;
        border-radius: 0.75rem;
        padding: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    html.theme-light .pwa-icon-box {
        background-color: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
    }

    .pwa-content {
        flex: 1;
        min-width: 0;
    }

    .pwa-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .pwa-title {
        font-weight: 800;
        font-size: 0.875rem;
        color: #ffffff;
        margin: 0;
        letter-spacing: -0.01em;
    }

    html.theme-light .pwa-title {
        color: #0f172a !important;
    }

    .pwa-close-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 1.125rem;
        cursor: pointer;
        padding: 0 0.25rem;
        line-height: 1;
        transition: color 0.15s;
    }

    .pwa-close-btn:hover {
        color: #ffffff;
    }

    html.theme-light .pwa-close-btn:hover {
        color: #0f172a !important;
    }

    .pwa-desc {
        font-size: 0.78125rem;
        color: #cbd5e1;
        margin-top: 0.25rem;
        margin-bottom: 0.875rem;
        line-height: 1.45;
    }

    html.theme-light .pwa-desc {
        color: #475569 !important;
    }

    .pwa-btn-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pwa-btn-primary {
        background-color: #99ff04;
        color: #000000;
        font-weight: 900;
        font-size: 0.75rem;
        padding: 0.5rem 0.875rem;
        border-radius: 0.625rem;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.15s ease-in-out;
        box-shadow: 0 4px 10px rgba(153, 255, 4, 0.25);
    }

    .pwa-btn-primary:hover {
        background-color: #84e000;
        transform: scale(1.02);
    }

    .pwa-btn-secondary {
        background-color: transparent;
        color: #cbd5e1;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.625rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
        cursor: pointer;
        transition: background-color 0.15s, border-color 0.15s;
    }

    .pwa-btn-secondary:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.3);
        color: #ffffff;
    }

    html.theme-light .pwa-btn-secondary {
        color: #334155 !important;
        border-color: #cbd5e1 !important;
    }

    html.theme-light .pwa-btn-secondary:hover {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
    }

    /* Modal styling */
    .pwa-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(6px);
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .pwa-modal-card {
        background-color: #1b182a;
        color: #f4f4f5;
        border-radius: 1.25rem;
        max-width: 440px;
        width: 100%;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-sizing: border-box;
    }

    html.theme-light .pwa-modal-card {
        background-color: #ffffff !important;
        color: #0f172a !important;
        border-color: #e2e8f0 !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15) !important;
    }

    .pwa-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
    }

    html.theme-light .pwa-modal-header {
        border-bottom-color: #e2e8f0 !important;
    }

    .pwa-modal-guide-box {
        padding: 0.875rem;
        border-radius: 0.75rem;
        margin-bottom: 0.75rem;
        font-size: 0.78125rem;
        background-color: #231f36;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
    }

    html.theme-light .pwa-modal-guide-box {
        background-color: #f8fafc !important;
        border-color: #e2e8f0 !important;
        color: #334155 !important;
    }

    .pwa-hidden {
        display: none !important;
    }
</style>

<div id="pwa-install-banner" class="no-print pwa-hidden">
    <div class="pwa-banner-flex">
        <div class="pwa-icon-box">
            <img src="{{ asset('images/logo-no-bg.png') }}" alt="DonasiTrust Logo" style="width: 1.5rem; height: 1.5rem; object-fit: contain;">
        </div>
        <div class="pwa-content">
            <div class="pwa-header">
                <h4 class="pwa-title">Install Aplikasi Donasi<span style="color: #99ff04;">Trust</span></h4>
                <button type="button" onclick="dismissPwaBanner()" class="pwa-close-btn" aria-label="Tutup Banner">✕</button>
            </div>
            <p class="pwa-desc">
                Pasang aplikasi ke HP / Laptop Anda agar dapat diakses cepat &amp; hemat kuota.
            </p>
            <div class="pwa-btn-group">
                <button type="button" id="pwa-install-btn" onclick="triggerPwaInstall()" class="pwa-btn-primary">
                    <svg style="width: 0.9rem; height: 0.9rem; stroke: #000000;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span id="pwa-install-btn-text">Install Langsung</span>
                </button>
                <button type="button" onclick="showPwaGuide()" class="pwa-btn-secondary">
                    Panduan Manual
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Panduan Manual -->
<div id="pwa-guide-modal" class="pwa-modal-overlay pwa-hidden">
    <div class="pwa-modal-card">
        <div class="pwa-modal-header">
            <h3 style="font-weight: 800; font-size: 0.9375rem; margin: 0;">
                Panduan Install Layar Utama
            </h3>
            <button type="button" onclick="closePwaGuide()" style="background: none; border: none; font-size: 1.125rem; cursor: pointer; color: #94a3b8;">✕</button>
        </div>

        <div style="font-size: 0.78125rem; line-height: 1.5;">
            <div class="pwa-modal-guide-box">
                <strong style="display: block; font-weight: 800; color: #99ff04; margin-bottom: 0.375rem;">Android / Desktop (Chrome, Edge, Opera):</strong>
                <ol style="margin: 0; padding-left: 1.125rem;">
                    <li>Buka menu browser (titik tiga ⋮ di sudut kanan atas).</li>
                    <li>Pilih menu <strong>"Install DonasiTrust"</strong> atau <strong>"Tambahkan ke layar Utama"</strong>.</li>
                    <li>Konfirmasi tombol Install pada layar perangkat Anda.</li>
                </ol>
            </div>

            <div class="pwa-modal-guide-box">
                <strong style="display: block; font-weight: 800; color: #38bdf8; margin-bottom: 0.375rem;">iPhone / iPad (Safari):</strong>
                <ol style="margin: 0; padding-left: 1.125rem;">
                    <li>Ketuk tombol Bagikan (Share / 📤) di bagian bawah Safari.</li>
                    <li>Geser ke bawah dan pilih <strong>"Tambah ke Layar Utama"</strong> (Add to Home Screen).</li>
                    <li>Ketuk <strong>"Tambah"</strong> di kanan atas.</li>
                </ol>
            </div>
        </div>

        <div style="margin-top: 1rem; text-align: right;">
            <button type="button" onclick="closePwaGuide()" class="pwa-btn-primary" style="padding: 0.45rem 1.25rem;">
                Mengerti
            </button>
        </div>
    </div>
</div>

<script>
    let localDeferredPrompt = null;

    function getActivePrompt() {
        return window.deferredPwaPrompt || localDeferredPrompt;
    }

    // Register Service Worker & tangkap event instalasi browser
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.deferredPwaPrompt = e;
        localDeferredPrompt = e;
        updatePwaUI();
        checkAndShowPwaBanner();
    });

    window.addEventListener('pwa-prompt-ready', () => {
        updatePwaUI();
        checkAndShowPwaBanner();
    });

    window.addEventListener('appinstalled', () => {
        dismissPwaBanner();
        window.deferredPwaPrompt = null;
        localDeferredPrompt = null;
    });

    function updatePwaUI() {
        const promptEvent = getActivePrompt();
        const btnText = document.getElementById('pwa-install-btn-text');
        if (btnText) {
            if (promptEvent) {
                btnText.textContent = 'Install Langsung';
            } else {
                btnText.textContent = 'Install Aplikasi';
            }
        }
    }

    function checkAndShowPwaBanner() {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const isDismissed = sessionStorage.getItem('dt_pwa_banner_dismissed') === 'true';

        if (!isStandalone && !isDismissed) {
            const banner = document.getElementById('pwa-install-banner');
            if (banner) banner.classList.remove('pwa-hidden');
        }
    }

    function triggerPwaInstall() {
        const promptEvent = getActivePrompt();
        if (promptEvent) {
            promptEvent.prompt();
            promptEvent.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    dismissPwaBanner();
                }
                window.deferredPwaPrompt = null;
                localDeferredPrompt = null;
            });
        } else {
            showPwaGuide();
        }
    }

    function dismissPwaBanner() {
        sessionStorage.setItem('dt_pwa_banner_dismissed', 'true');
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.classList.add('pwa-hidden');
    }

    function showPwaGuide() {
        document.getElementById('pwa-guide-modal')?.classList.remove('pwa-hidden');
    }

    function closePwaGuide() {
        document.getElementById('pwa-guide-modal')?.classList.add('pwa-hidden');
    }

    document.addEventListener('DOMContentLoaded', () => {
        updatePwaUI();
        setTimeout(checkAndShowPwaBanner, 1000);
    });
</script>
