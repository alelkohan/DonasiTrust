@extends('layouts.dashboard')
@section('title', 'Verifikasi identitas')


@section('panel')
{{-- Pengaju yang SUDAH terverifikasi tetap perlu bisa mengganti rekening
     pencairan — pindah bank, rekening lama ditutup, salah ketik nomor. Sebelum
     ini form-nya tidak dirender sama sekali untuk mereka, sehingga satu-satunya
     kelompok yang punya rekening untuk diganti justru tidak punya caranya. --}}
<div x-data="{ ubahRekening: false }">
    <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">Verifikasi identitas</h1>
    <p class="mt-2 max-w-2xl text-ink-600">
        Sebelum bisa menggalang dana, identitas Anda diperiksa <strong>manusia</strong> — bukan sistem otomatis.
        Kami tidak punya akses ke basis data kependudukan, jadi kami tidak akan berpura-pura bisa
        memverifikasi NIK secara instan.
    </p>

    @if ($user->verification_status === 'verified')
        <div class="dt-card mt-6 border-brand-300 p-6" x-show="! ubahRekening">
            <div class="flex items-start gap-3.5">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-600 text-white" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                </span>
                <div>
                    <h2 class="font-bold text-ink-900">Identitas Anda sudah terverifikasi</h2>
                    <p class="mt-1 text-sm text-ink-600">
                        Diverifikasi pada {{ $user->verified_at?->translatedFormat('d F Y, H:i') }}.
                        Anda bisa mengajukan kampanye sekarang.
                    </p>
                    @if ($user->hasPayoutAccount())
                        <dl class="mt-4 rounded-xl border border-ink-200 bg-ink-50 p-4 text-sm">
                            <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Rekening pencairan terdaftar</dt>
                            <dd class="mt-1 font-semibold text-ink-900">{{ $user->maskedPayoutAccount() }}</dd>
                        </dl>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($user->isPengaju())
                            <a href="{{ route('pengaju.kampanye.create') }}" class="dt-btn-primary">Buat kampanye</a>
                        @endif

                        @if ($user->hasPayoutAccount())
                            <button type="button" @click="ubahRekening = true" class="dt-btn-secondary">
                                Ubah rekening pencairan
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div @if ($user->verification_status === 'verified') x-show="ubahRekening" x-cloak class="mt-6" @endif>
        @if ($user->verification_status === 'verified')
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-sm text-amber-900">
                <p class="font-semibold">Mengubah rekening akan mengulang verifikasi</p>
                <ul class="mt-1.5 list-disc space-y-1 pl-5 text-xs leading-relaxed text-amber-900/85">
                    <li>Status akun kembali <strong>menunggu peninjauan</strong>, dan selama itu tidak ada
                        pencairan yang bisa diajukan.</li>
                    <li>KTP perlu diunggah ulang — admin mencocokkan <strong>nama pemilik rekening baru</strong>
                        dengan nama di KTP. Itulah yang menahan dana dialihkan ke nama orang lain.</li>
                    <li>Pemberitahuan dikirim ke email Anda, supaya perubahan yang bukan Anda lakukan tetap ketahuan.</li>
                </ul>
                <button type="button" @click="ubahRekening = false" class="dt-btn-secondary mt-3 py-1 px-3 text-xs">
                    Batal, kembali
                </button>
            </div>
        @endif

        @if ($user->verification_status === 'pending')
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Dokumen Anda sedang ditinjau admin. Anda tetap bisa mengunggah ulang jika ada yang keliru.
            </div>
        @endif

        @if ($user->verification_status === 'rejected' && $user->verification_note)
            <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                <p class="font-semibold">Pengajuan sebelumnya ditolak</p>
                <p class="mt-1">{{ $user->verification_note }}</p>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr] lg:items-start">
            <form method="POST" action="{{ route('verifikasi.identitas.store') }}" enctype="multipart/form-data"
                  class="dt-card space-y-4 p-5 sm:p-6">
                @csrf

                <div>
                    <label for="organization" class="dt-label">Lembaga / organisasi <span class="font-normal text-ink-400">(opsional)</span></label>
                    <input id="organization" name="organization" type="text" class="dt-input"
                           value="{{ old('organization', $user->organization) }}"
                           placeholder="Kosongkan jika mengajukan atas nama pribadi">
                </div>

                <div>
                    <label for="identity_number" class="dt-label">Nomor NIK (16 digit)</label>
                    <input id="identity_number" name="identity_number" type="text" inputmode="numeric"
                           maxlength="16" required class="dt-input font-mono tracking-wider"
                           value="{{ old('identity_number') }}" placeholder="3374xxxxxxxxxxxx">
                    <p class="dt-hint">
                        Hanya 4 digit terakhir yang disimpan di basis data. Nomor lengkap dipakai sekali
                        untuk pencocokan dengan dokumen, lalu dibuang.
                    </p>
                </div>

                <div>
                    <label for="identity_document" class="dt-label">Foto/scan KTP</label>
                    <input id="identity_document" name="identity_document" type="file" required
                           accept=".jpg,.jpeg,.png,.pdf"
                           class="dt-input file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700">
                    <p class="dt-hint">JPG, PNG, atau PDF. Maksimal {{ round(config('donasi.max_upload_kb') / 1024, 1) }} MB.</p>
                </div>

                {{-- Rekening tujuan pencairan --}}
                <div class="rounded-xl border border-ink-200 bg-ink-50/60 p-4">
                    <h3 class="text-sm font-bold text-ink-900">Rekening tujuan pencairan</h3>
                    <p class="mt-1 text-xs leading-relaxed text-ink-600">
                        Dana kampanye hanya bisa dicairkan ke rekening ini. Nomornya diperiksa admin
                        bersamaan dengan KTP, dan tidak bisa diubah sendiri setelah terverifikasi —
                        itu yang membuat dana tidak bisa dialihkan ke rekening lain saat pencairan.
                    </p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="bank_name" class="dt-label">Nama bank</label>
                            <input id="bank_name" name="bank_name" type="text" required maxlength="60"
                                   class="dt-input" value="{{ old('bank_name', $user->bank_name) }}"
                                   placeholder="BCA / BRI / Mandiri / BSI">
                            @error('bank_name') <p class="dt-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_account_number" class="dt-label">Nomor rekening</label>
                            <input id="bank_account_number" name="bank_account_number" type="text"
                                   inputmode="numeric" required maxlength="40"
                                   class="dt-input font-mono tracking-wider"
                                   value="{{ old('bank_account_number', $user->bank_account_number) }}"
                                   placeholder="1234567890">
                            @error('bank_account_number') <p class="dt-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="bank_account_holder" class="dt-label">Nama pemilik rekening</label>
                            <input id="bank_account_holder" name="bank_account_holder" type="text"
                                   required maxlength="120" class="dt-input"
                                   value="{{ old('bank_account_holder', $user->bank_account_holder) }}"
                                   placeholder="Sesuai buku tabungan">
                            <p class="dt-hint">Harus cocok dengan nama di KTP yang Anda unggah.</p>
                            @error('bank_account_holder') <p class="dt-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Gerbang dua langkah di titik yang benar-benar berisiko.
                     Mencairkan dana ke rekening lain memang mustahil — tujuannya
                     terkunci ke profil ini. Maka jalur serangan yang tersisa adalah
                     MENGGANTI rekening di halaman ini, dan di sinilah kodenya diminta.
                     Hanya muncul kalau sudah punya rekening tersimpan: pendaftaran
                     pertama belum punya apa pun untuk dicuri. --}}
                @if ($user->hasTwoFactorEnabled() && $user->hasPayoutAccount())
                    <div class="rounded-xl border border-brand-200 bg-brand-50/60 p-4">
                        <label for="totp_code" class="dt-label">Kode verifikasi dua langkah</label>
                        <input id="totp_code" name="totp_code" inputmode="numeric" maxlength="9"
                               autocomplete="one-time-code" placeholder="000000"
                               class="dt-input max-w-44 bg-white text-center font-mono text-lg tracking-[0.3em]">
                        <p class="mt-2 text-xs leading-relaxed text-brand-900/80">
                            Wajib diisi <strong>bila Anda mengubah rekening tujuan</strong>. Mengganti rekening
                            adalah satu-satunya cara dana bisa diarahkan ke pihak lain, jadi langkah ini
                            dikunci lebih rapat daripada pengajuan pencairan biasa.
                        </p>
                        @error('totp_code') <p class="dt-error">{{ $message }}</p> @enderror
                    </div>
                @elseif ($user->hasPayoutAccount() && ! $user->hasTwoFactorEnabled())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-900">Lindungi rekening pencairan Anda</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-900/80">
                            Tanpa verifikasi dua langkah, siapa pun yang berhasil masuk ke akun Anda bisa
                            mengganti rekening tujuan dari halaman ini.
                            <a href="{{ route('keamanan.index') }}" class="font-semibold underline">Aktifkan verifikasi dua langkah</a>
                            — pengaju yang mengaktifkannya juga mendapat lencana di halaman kampanyenya.
                        </p>
                    </div>
                @endif

                <button type="submit" class="dt-btn-primary w-full py-3">Kirim untuk diverifikasi</button>
            </form>

            <aside class="dt-card p-5 sm:p-6">
                <h2 class="text-sm font-bold text-ink-900">Bagaimana dokumen Anda disimpan</h2>
                <ul class="mt-4 space-y-3.5 text-sm text-ink-600">
                    @foreach ([
                        'Berkas masuk ke penyimpanan privat, di luar folder publik web server. Tidak ada URL langsung yang bisa ditebak.',
                        'Hanya admin dan Anda sendiri yang bisa membukanya, lewat route yang mengecek izin di setiap permintaan.',
                        'NIK lengkap tidak pernah masuk basis data — hanya 4 digit terakhir.',
                        'Setiap kali admin memutuskan verifikasi, keputusannya tercatat di jejak audit.',
                    ] as $point)
                        <li class="flex gap-2.5">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7.5"/></svg>
                            <span class="leading-relaxed">{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </div>
</div>
@endsection
