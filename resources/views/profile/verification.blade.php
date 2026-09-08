@extends('layouts.dashboard')
@section('title', 'Verifikasi Identitas')

@section('panel')
<div x-data="{ ubahRekening: false }" class="space-y-6">

    {{-- Page Header --}}
    <div class="border-b border-white/10 pb-5">
        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">Verifikasi Identitas &amp; Rekening</h1>
        <p class="mt-1 text-xs sm:text-sm font-medium leading-relaxed text-slate-400 max-w-2xl">
            Sebelum bisa menggalang dana, identitas Anda ditinjau oleh tim admin untuk memastikan legalitas, keamanan donatur, dan kunci rekening pencairan.
        </p>
    </div>

    {{-- Status Card for Already Verified Users --}}
    @if ($user->verification_status === 'verified')
        <div class="rounded-3xl border border-emerald-500/30 bg-emerald-500/10 p-6 shadow-xl backdrop-blur-md" x-show="! ubahRekening">
            <div class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-[#99ff04] text-black font-black text-lg shadow-md">
                    ✓
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-black text-white">Identitas Anda Sudah Terverifikasi</h2>
                    <p class="mt-1 text-xs text-slate-300">
                        Diverifikasi pada {{ $user->verified_at?->translatedFormat('d F Y, H:i') }}. Anda memiliki akses penuh untuk menggalang dana.
                    </p>

                    @if ($user->hasPayoutAccount())
                        <div class="verified-bank-box mt-4 rounded-2xl border border-white/10 bg-[#12101c]/80 p-4 text-xs">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Rekening Pencairan Terdaftar</span>
                            <div class="mt-1 font-mono font-bold text-[#99ff04] text-sm">{{ $user->maskedPayoutAccount() }}</div>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap gap-3">
                        @if ($user->isPengaju())
                            <a href="{{ route('pengaju.kampanye.create') }}" class="inline-flex items-center gap-2 rounded-full bg-[#99ff04] px-5 py-2 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20">
                                <span>Buat Kampanye Baru &rarr;</span>
                            </a>
                        @endif

                        @if ($user->hasPayoutAccount())
                            <button type="button" @click="ubahRekening = true" class="dt-btn-secondary text-xs">
                                Ubah Rekening Pencairan
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div @if ($user->verification_status === 'verified') x-show="ubahRekening" x-cloak class="space-y-6" @else class="space-y-6" @endif>
        @if ($user->verification_status === 'verified')
            <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 text-xs text-amber-200">
                <p class="font-extrabold text-amber-300 text-sm">Mengubah Rekening Akan Mengulang Proses Verifikasi</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 leading-relaxed text-slate-300">
                    <li>Status akun kembali <strong class="text-amber-300">Menunggu Peninjauan</strong>.</li>
                    <li>Admin akan mencocokkan nama pemilik rekening baru dengan dokumen KTP Anda yang tersimpan.</li>
                    <li>Perubahan memerlukan validasi Kode OTP Email demi keamanan.</li>
                </ul>
                <button type="button" @click="ubahRekening = false" class="dt-btn-secondary mt-4 text-xs">
                    Batal, Kembali
                </button>
            </div>
        @endif

        @if ($user->verification_status === 'pending')
            <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs font-bold text-amber-200">
                @if ($user->hasPendingPayoutAccount() && $user->hasPayoutAccount())
                    <p class="font-extrabold text-amber-300">Pengajuan Perubahan Rekening Sedang Ditinjau Admin</p>
                    <p class="mt-1 font-normal text-slate-300">
                        Anda mengajukan rekening baru: <strong class="font-mono text-amber-300">{{ $user->maskedPendingPayoutAccount() }}</strong>.
                        Jika ditolak, akun akan tetap menggunakan rekening terdaftar sebelumnya ({{ $user->maskedPayoutAccount() }}).
                    </p>
                @else
                    ⏳ Dokumen Anda sedang dalam antrean review tim admin. Anda tetap dapat memperbarui pengajuan di bawah jika terdapat kekeliruan.
                @endif
            </div>
        @endif

        @if ($user->verification_status === 'rejected' && $user->verification_note)
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs text-rose-200">
                <p class="font-extrabold text-rose-300">Pengajuan Sebelumnya Ditolak:</p>
                <p class="mt-1 leading-relaxed">{{ $user->verification_note }}</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr] lg:items-start">
            
            {{-- Form Verifikasi Identitas --}}
            <form method="POST" action="{{ route('verifikasi.identitas.store') }}" enctype="multipart/form-data"
                  class="dt-card p-6 space-y-5">
                @csrf

                <div>
                    <label for="organization" class="block text-xs font-bold text-slate-300 mb-1.5">
                        Lembaga / Organisasi <span class="font-normal text-slate-400">(Opsional)</span>
                    </label>
                    <input id="organization" name="organization" type="text" 
                           class="dt-input text-sm"
                           value="{{ old('organization', $user->organization) }}"
                           placeholder="Kosongkan jika atas nama pribadi">
                </div>

                @if (! empty($user->identity_document_path) && ! empty($user->identity_number_hash))
                    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs text-emerald-300">
                        <div class="flex items-center gap-2 font-black text-emerald-400">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span>Dokumen Identitas &amp; NIK Tersimpan Aman</span>
                        </div>
                        <p class="mt-1 text-slate-300 leading-relaxed">
                            NIK (akhiran ••••{{ $user->identity_number_last4 }}) dan foto KTP Anda telah tersimpan secara aman dari verifikasi sebelumnya. Anda tidak perlu mengunggah ulang KTP.
                        </p>
                    </div>
                @else
                    <div>
                        <label for="identity_number" class="block text-xs font-bold text-slate-300 mb-1.5">Nomor NIK (16 Digit)</label>
                        <input id="identity_number" name="identity_number" type="text" inputmode="numeric"
                               maxlength="16" required 
                               class="dt-input font-mono text-sm"
                               value="{{ old('identity_number') }}" placeholder="3374xxxxxxxxxxxx">
                        <p class="mt-1 text-[11px] text-slate-400">
                            Hanya 4 digit terakhir NIK yang disimpan di basis data demi privasi.
                        </p>
                    </div>

                    <div>
                        <label for="identity_document" class="block text-xs font-bold text-slate-300 mb-1.5">Foto / Scan Dokumen KTP</label>
                        <input id="identity_document" name="identity_document" type="file" required
                               accept=".jpg,.jpeg,.png,.pdf"
                               class="dt-input text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#99ff04] file:px-3 file:py-1 file:text-xs file:font-black file:text-black">
                        <p class="mt-1 text-[11px] text-slate-400">Format JPG, PNG, atau PDF (Maks. {{ round(config('donasi.max_upload_kb') / 1024, 1) }} MB).</p>
                    </div>
                @endif

                {{-- Rekening Pencairan --}}
                <div class="rounded-2xl border border-white/10 bg-[#231f36]/60 p-5 space-y-4">
                    <div>
                        <h3 class="text-sm font-black text-white">Rekening Tujuan Pencairan</h3>
                        <p class="mt-1 text-xs text-slate-400 leading-relaxed">
                            Dana kampanye yang disetujui hanya akan ditransfer ke rekening ini demi keamanan donatur.
                        </p>
                    </div>

                    <div>
                        <label for="bank_name" class="block text-xs font-bold text-slate-300 mb-1.5">Nama Bank</label>
                        <input id="bank_name" name="bank_name" type="text" required maxlength="60"
                               class="dt-input text-sm" 
                               value="{{ old('bank_name', $user->bank_name) }}"
                               placeholder="BCA / BRI / Mandiri / BSI / Bank Jago">
                    </div>

                    <div>
                        <label for="bank_account_number" class="block text-xs font-bold text-slate-300 mb-1.5">Nomor Rekening</label>
                        <input id="bank_account_number" name="bank_account_number" type="text"
                               inputmode="numeric" required maxlength="40"
                               class="dt-input font-mono text-sm"
                               value="{{ old('bank_account_number', $user->bank_account_number) }}"
                               placeholder="1234567890">
                    </div>

                    <div>
                        <label for="bank_account_holder" class="block text-xs font-bold text-slate-300 mb-1.5">Nama Pemilik Rekening</label>
                        <input id="bank_account_holder" name="bank_account_holder" type="text"
                               required maxlength="120" 
                               class="dt-input text-sm"
                               value="{{ old('bank_account_holder', $user->bank_account_holder) }}"
                               placeholder="Sesuai buku tabungan & KTP">
                        <p class="mt-1 text-[11px] text-slate-400">Harus sesuai dengan nama pada dokumen KTP yang diunggah.</p>
                    </div>
                </div>

                {{-- Verification Email OTP Security Step --}}
                @if ($user->hasPayoutAccount())
                    <div class="rounded-2xl border border-[#99ff04]/30 bg-[#99ff04]/10 p-5 space-y-4">
                        <x-otp-input purpose="bank_change" label="Kode Verifikasi Email (Wajib jika mengubah rekening)" />
                    </div>
                @endif

                <button type="submit" class="w-full rounded-full bg-[#99ff04] px-6 py-3 text-xs font-black text-black hover:bg-[#84e000] shadow-md shadow-[#99ff04]/20 transition-all">
                    Kirim Berkas Verifikasi &rarr;
                </button>
            </form>

            {{-- Privacy Info Aside --}}
            <aside class="dt-card p-6">
                <h2 class="text-sm font-black text-white">Standar Keamanan Privasi Dokumen</h2>
                <ul class="mt-4 space-y-3.5 text-xs text-slate-300">
                    <li class="flex gap-2.5">
                        <span class="text-[#99ff04] font-bold">✓</span>
                        <span class="leading-relaxed">Dokumen KTP disimpan di direktori privat terenkripsi di luar akses web publik.</span>
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-[#99ff04] font-bold">✓</span>
                        <span class="leading-relaxed">Hanya dibuka oleh tim verifikator resmi melalui middleware authorization.</span>
                    </li>
                    <li class="flex gap-2.5">
                        <span class="text-[#99ff04] font-bold">✓</span>
                        <span class="leading-relaxed">Nomor NIK lengkap tidak pernah disimpan permanen di database SQL.</span>
                    </li>
                </ul>
            </aside>

        </div>

    </div>

</div>
@endsection

