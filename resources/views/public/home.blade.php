@extends('layouts.app')

@section('title', 'Donasi yang bisa Anda lacak sampai nota terakhir')

@section('content')

{{-- Hero --}}
<section class="relative overflow-hidden bg-white">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute -top-32 -right-24 h-96 w-96 rounded-full bg-brand-100/60 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-32 h-96 w-96 rounded-full bg-sky-100/50 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <span class="dt-badge bg-brand-100 text-brand-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                    Ledger publik &amp; jejak audit ber-rantai-hash
                </span>

                <h1 class="mt-5 text-4xl leading-[1.1] font-extrabold tracking-tight text-ink-900 sm:text-5xl lg:text-6xl">
                    Donasi Anda tidak berhenti di<br class="hidden sm:block">
                    <span class="text-brand-600">&ldquo;terima&nbsp;kasih&rdquo;.</span>
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-ink-600">
                    Di kebanyakan platform, jejak uang Anda hilang begitu pembayaran berhasil.
                    DonasiTrust mencairkan dana <strong class="font-semibold text-ink-800">bertahap</strong>,
                    menahan tahap berikutnya sampai nota tahap sebelumnya masuk, dan menerbitkan
                    setiap langkahnya ke halaman yang bisa dibuka siapa pun.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('kampanye.index') }}" class="dt-btn-primary px-6 py-3 text-base">
                        Lihat kampanye
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    <a href="{{ route('transparansi') }}" class="dt-btn-secondary px-6 py-3 text-base">
                        Periksa ledger publik
                    </a>
                </div>

                <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-ink-200 pt-6">
                    <div>
                        <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Terkumpul</dt>
                        <dd class="mt-1 text-xl font-extrabold text-ink-900 tabular-nums">{{ rupiah_ringkas($stats['terkumpul']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Transaksi</dt>
                        <dd class="mt-1 text-xl font-extrabold text-ink-900 tabular-nums">{{ number_format($stats['donatur'], 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Kampanye</dt>
                        <dd class="mt-1 text-xl font-extrabold text-ink-900 tabular-nums">{{ $stats['kampanye'] }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Ilustrasi alur: bukan dekorasi, ia menjelaskan mekanismenya --}}
            <div class="lg:pl-6">
                <div class="dt-card p-6 sm:p-7">
                    <p class="text-xs font-semibold tracking-wide text-ink-500 uppercase">Perjalanan satu donasi</p>

                    <ol class="mt-5 space-y-0">
                        @foreach ([
                            ['Donatur membayar', 'Kuitansi digital terbit dengan kode verifikasi HMAC.', 'brand'],
                            ['Dana masuk saldo kampanye', 'Belum bisa disentuh pengaju. Tercatat di ledger publik.', 'brand'],
                            ['Pengaju minta tahap 1', 'Wajib melampirkan rincian kebutuhan. Admin memutuskan.', 'amber'],
                            ['Nota tahap 1 diunggah', 'Baru setelah ini tahap 2 terbuka.', 'amber'],
                            ['Publik bisa memeriksa', 'Setiap perubahan terkunci dalam rantai hash.', 'sky'],
                        ] as $i => $step)
                            <li class="relative flex gap-4 pb-6 last:pb-0">
                                @unless ($loop->last)
                                    <span class="absolute top-8 left-[15px] h-full w-px bg-ink-200" aria-hidden="true"></span>
                                @endunless
                                <span class="relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-bold
                                    {{ $step[2] === 'brand' ? 'bg-brand-600 text-white' : ($step[2] === 'amber' ? 'bg-amber-500 text-white' : 'bg-sky-600 text-white') }}">
                                    {{ $i + 1 }}
                                </span>
                                <div class="pt-0.5">
                                    <p class="text-sm font-semibold text-ink-900">{{ $step[0] }}</p>
                                    <p class="mt-0.5 text-sm leading-relaxed text-ink-600">{{ $step[1] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Tiga pilar --}}
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <h2 class="text-3xl font-extrabold tracking-tight text-ink-900">Tiga hal yang membuat angkanya bisa dipercaya</h2>
        <p class="mt-3 text-ink-600">
            Bukan janji, tapi aturan yang dipaksakan oleh sistem — bahkan pengaju kampanye pun tidak bisa melewatinya.
        </p>
    </div>

    <div class="mt-10 grid gap-6 md:grid-cols-3">
        @foreach ([
            [
                'Pencairan bertahap',
                'Target dana wajib dipecah jadi tahapan yang jumlahnya persis sama dengan RAB. Tahap berikutnya terkunci sampai tahap sebelumnya dilaporkan.',
                // Anak tangga: tiga undakan naik — dana keluar setahap demi setahap.
                'M3 20h5v-5H3zM9.5 20h5V10h-5zM16 20h5V4h-5z',
            ],
            [
                'Kuitansi terverifikasi',
                'Tiap donasi menghasilkan kode HMAC-SHA256. Siapa pun bisa mencocokkan kuitansi di halaman verifikasi tanpa perlu punya akun.',
                // Perisai bercentang.
                'M12 3 4 6.2v5.1c0 4.6 3.2 8.4 8 9.7 4.8-1.3 8-5.1 8-9.7V6.2Zm-3 8.9 2.2 2.2 4.2-4.4',
            ],
            [
                'Jejak audit ber-rantai',
                'Setiap entri menyimpan hash entri sebelumnya. Mengubah satu catatan lama membuat seluruh rantai sesudahnya gagal diverifikasi.',
                // Dua mata rantai saling mengait.
                'M10.6 13.4a4 4 0 0 0 5.7 0l2.8-2.9a4 4 0 0 0-5.7-5.6l-1.6 1.6M13.4 10.6a4 4 0 0 0-5.7 0l-2.8 2.9a4 4 0 0 0 5.7 5.6l1.6-1.6',
            ],
        ] as $pilar)
            <div class="dt-card p-6">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-700" aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $pilar[2] }}"/>
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-bold text-ink-900">{{ $pilar[0] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-600">{{ $pilar[1] }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- Kampanye pilihan --}}
<section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-3xl font-extrabold tracking-tight text-ink-900">Kampanye berjalan</h2>
            <p class="mt-2 text-ink-600">Semua sudah lolos review manual admin dan punya RAB yang terbuka.</p>
        </div>
        <a href="{{ route('kampanye.index') }}" class="dt-link text-sm">Lihat semua &rarr;</a>
    </div>

    @if ($campaigns->isEmpty())
        <x-empty-state class="mt-8" title="Belum ada kampanye tayang"
                       description="Kampanye akan muncul di sini setelah disetujui admin." />
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($campaigns as $campaign)
                <x-campaign-card :campaign="$campaign" />
            @endforeach
        </div>
    @endif
</section>

{{-- CTA --}}
<section class="mx-auto max-w-7xl px-4 pb-4 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-3xl bg-ink-900 px-6 py-12 sm:px-12 sm:py-16">
        <div class="grid items-center gap-8 lg:grid-cols-[1.5fr_1fr]">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-white">Punya program yang butuh dana?</h2>
                <p class="mt-3 max-w-xl text-ink-300">
                    Daftar sebagai pengaju, verifikasi identitas Anda, lalu susun RAB dan tahapan pencairan.
                    Kami sengaja membuat prosesnya lebih ketat — itu yang membuat donatur mau menekan tombol donasi.
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                <a href="{{ route('register') }}" class="dt-btn bg-white px-6 py-3 text-base text-ink-900 hover:bg-ink-100">
                    Ajukan kampanye
                </a>
            </div>
        </div>
    </div>
</section>

@endsection
