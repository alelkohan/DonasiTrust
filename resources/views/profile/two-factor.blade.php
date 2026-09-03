@extends('layouts.dashboard')
@section('title', 'Keamanan akun')

@section('panel')
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Keamanan akun</h1>
        <p class="mt-1 text-sm text-ink-600">
            Verifikasi dua langkah untuk aksi yang memindahkan uang.
        </p>
    </header>

    {{-- Kode pemulihan hanya lewat sekali di sini. Tidak ada halaman lain yang
         bisa menampilkannya ulang: yang tersimpan di basis data pun terenkripsi
         dan sengaja tidak diberi tampilan. --}}
    @if (session('recovery_codes'))
        <section class="dt-card mb-6 border-amber-300 bg-amber-50/60 p-5 sm:p-6">
            <h2 class="text-lg font-bold text-amber-900">Simpan kode pemulihan ini sekarang</h2>
            <p class="mt-1 text-sm leading-relaxed text-amber-900/85">
                Ini satu-satunya kali kode ditampilkan. Kalau ponsel Anda hilang, kode inilah yang
                membuat dana kampanye tidak ikut terkunci selamanya. Simpan di tempat terpisah dari
                ponsel — cetak, atau taruh di pengelola kata sandi.
            </p>

            <ul class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach (session('recovery_codes') as $code)
                    <li class="rounded-lg border border-amber-300 bg-white px-3 py-2 text-center font-mono text-sm font-semibold tracking-wider text-ink-900">
                        {{ $code }}
                    </li>
                @endforeach
            </ul>

            <p class="mt-3 text-xs text-amber-900/75">
                Setiap kode hanya berlaku satu kali pakai.
            </p>
        </section>
    @endif

    @if ($user->hasTwoFactorEnabled())
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <section class="dt-card p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-ink-900">Verifikasi dua langkah</h2>
                        <p class="mt-1 text-sm text-ink-600">
                            Aktif sejak {{ $user->totp_confirmed_at->translatedFormat('d F Y, H:i') }}
                        </p>
                    </div>
                    <x-badge tone="success">Aktif</x-badge>
                </div>

                <div class="mt-5 rounded-xl border border-ink-200 bg-ink-50 p-4 text-sm leading-relaxed text-ink-700">
                    <p class="font-semibold text-ink-900">Yang sekarang butuh kode Anda:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @if ($user->isAdmin())
                            <li>
                                Menandai dana pencairan sudah ditransfer
                                <span class="text-ink-500">— satu kode berlaku
                                {{ \App\Services\TotpGuard::SUDO_WINDOW_MINUTES }} menit, jadi antrean
                                pencairan tidak perlu diketik satu per satu</span>
                            </li>
                        @else
                            <li>
                                Mengganti rekening tujuan pencairan
                                <span class="text-ink-500">— pengajuan pencairan biasa tidak diminta kode,
                                karena rekening tujuannya memang sudah terkunci ke profil Anda</span>
                            </li>
                        @endif
                        <li>Mematikan verifikasi dua langkah ini</li>
                    </ul>

                    @unless ($user->isAdmin())
                        <p class="mt-3 border-t border-ink-200 pt-3 text-xs text-ink-600">
                            Halaman kampanye Anda kini menampilkan lencana
                            <strong>&ldquo;Rekening pencairan dikunci verifikasi dua langkah&rdquo;</strong>
                            yang bisa dilihat calon donatur.
                        </p>
                    @endunless
                </div>

                <div class="mt-5 flex items-center justify-between gap-4 border-t border-ink-100 pt-5 text-sm">
                    <div>
                        <p class="font-semibold text-ink-900">Kode pemulihan tersisa</p>
                        <p class="text-ink-600">{{ $user->unusedRecoveryCodeCount() }} dari 8</p>
                    </div>

                    <form method="POST" action="{{ route('keamanan.pemulihan') }}"
                          x-data="{ buka: false }" class="text-right">
                        @csrf
                        <button type="button" @click="buka = ! buka" x-show="!buka" class="dt-btn-secondary">
                            Buat ulang
                        </button>
                        <div x-show="buka" x-cloak class="flex items-center gap-2">
                            <input name="totp_code" inputmode="numeric" autocomplete="one-time-code"
                                   maxlength="9" required placeholder="000000"
                                   class="dt-input w-28 text-center font-mono tracking-widest">
                            <button type="submit" class="dt-btn-secondary">Ganti</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="dt-card p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink-900">Matikan verifikasi dua langkah</h2>
                <p class="mt-1 text-sm leading-relaxed text-ink-600">
                    Selama dimatikan, Anda tidak bisa mengajukan maupun melepas pencairan dana.
                    Butuh kata sandi <em>dan</em> kode yang masih berlaku — supaya sesi yang dibajak
                    tidak bisa mematikannya lebih dulu lalu mencairkan dana.
                </p>

                <form method="POST" action="{{ route('keamanan.matikan') }}" class="mt-5 space-y-4">
                    @csrf @method('DELETE')

                    <div>
                        <label for="current_password" class="dt-label">Kata sandi</label>
                        <input id="current_password" name="current_password" type="password" required
                               autocomplete="current-password" class="dt-input">
                    </div>

                    <div>
                        <label for="matikan_code" class="dt-label">Kode dari aplikasi authenticator</label>
                        <input id="matikan_code" name="totp_code" inputmode="numeric" required
                               autocomplete="one-time-code" maxlength="9" placeholder="000000"
                               class="dt-input text-center font-mono text-lg tracking-[0.4em]">
                        <p class="dt-hint">Bisa juga memakai satu kode pemulihan.</p>
                    </div>

                    <button type="submit" class="dt-btn-danger w-full">Matikan verifikasi dua langkah</button>
                </form>
            </section>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
            <section class="dt-card p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-lg font-bold text-ink-900">Aktifkan verifikasi dua langkah</h2>
                    <x-badge tone="warning">Belum aktif</x-badge>
                </div>

                <p class="mt-3 text-sm leading-relaxed text-ink-600">
                    Kata sandi bisa bocor lewat phishing atau dipakai ulang dari situs lain. Kode enam
                    digit yang berganti tiap 30 detik ini hanya ada di ponsel Anda, sehingga sesi yang
                    dibajak tetap tidak bisa menggerakkan dana.
                </p>

                <ol class="mt-5 space-y-4 text-sm text-ink-700">
                    <li class="flex gap-3">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">1</span>
                        <span>
                            Pasang aplikasi authenticator di ponsel — Google Authenticator, Aegis,
                            atau fitur bawaan pengelola kata sandi Anda.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">2</span>
                        <span>Pindai kode QR di samping, atau masukkan kuncinya secara manual.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-800">3</span>
                        <span>Ketik kode yang muncul di aplikasi untuk membuktikan kuncinya terbaca.</span>
                    </li>
                </ol>
            </section>

            <section class="dt-card p-5 sm:p-6">
                <div class="flex flex-col items-center">
                    <div class="rounded-xl border border-ink-200 bg-white p-4">
                        <canvas data-qr="{{ $provisioningUri }}" class="block h-44 w-44"
                                aria-label="Kode QR pendaftaran verifikasi dua langkah"></canvas>
                    </div>

                    <div class="mt-4 w-full">
                        <p class="text-center text-xs font-semibold tracking-wide text-ink-500 uppercase">
                            Atau masukkan kunci ini manual
                        </p>
                        <p class="mt-1.5 rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 text-center font-mono text-sm font-semibold tracking-wider break-all text-ink-900">
                            {{ app(\App\Services\Totp::class)->formatForManualEntry($pendingSecret) }}
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('keamanan.aktifkan') }}" class="mt-5 space-y-3 border-t border-ink-100 pt-5">
                    @csrf

                    <div>
                        <label for="aktifkan_code" class="dt-label">Kode enam digit dari aplikasi</label>
                        <input id="aktifkan_code" name="totp_code" inputmode="numeric" required autofocus
                               autocomplete="one-time-code" maxlength="6" placeholder="000000"
                               class="dt-input text-center font-mono text-lg tracking-[0.4em]">
                        <p class="dt-hint">
                            Kode berganti tiap 30 detik. Kalau selalu ditolak, periksa jam ponsel Anda.
                        </p>
                    </div>

                    <button type="submit" class="dt-btn-primary w-full py-3">Aktifkan sekarang</button>
                </form>
            </section>
        </div>
    @endif
@endsection
