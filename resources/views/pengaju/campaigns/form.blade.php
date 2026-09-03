@extends('layouts.dashboard')
@section('title', $campaign->exists ? 'Ubah kampanye' : 'Kampanye baru')

@php
    $menu = [
        ['label' => 'Ringkasan', 'url' => route('pengaju.dashboard')],
        ['label' => 'Kampanye saya', 'url' => route('pengaju.kampanye.index'), 'active' => true],
        ['label' => 'Profil saya', 'url' => route('profil.edit')],
    ];

    // Nilai awal untuk Alpine: dari old() bila validasi gagal, dari model bila mengubah.
    $itemsInitial = old('items', $campaign->exists
        ? $campaign->items->map(fn ($i) => [
            'name' => $i->name, 'quantity' => $i->quantity,
            'unit' => $i->unit, 'unit_price' => $i->unit_price,
        ])->values()->all()
        : [['name' => '', 'quantity' => 1, 'unit' => 'unit', 'unit_price' => 0]]);

    $milestonesInitial = old('milestones', $campaign->exists
        ? $campaign->milestones->map(fn ($m) => [
            'title' => $m->title, 'description' => $m->description, 'amount' => $m->amount,
        ])->values()->all()
        : [['title' => 'Tahap 1', 'description' => '', 'amount' => 0]]);
@endphp

@section('panel')
<form method="POST" enctype="multipart/form-data"
      action="{{ $campaign->exists ? route('pengaju.kampanye.update', $campaign) : route('pengaju.kampanye.store') }}"
      x-data="formKampanye({
          items: {{ Illuminate\Support\Js::from($itemsInitial) }},
          milestones: {{ Illuminate\Support\Js::from($milestonesInitial) }},
          target: {{ (int) old('target_amount', $campaign->target_amount ?? 0) }},
      })">
    @csrf
    @if ($campaign->exists) @method('PUT') @endif

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-900">
                {{ $campaign->exists ? 'Ubah kampanye' : 'Kampanye baru' }}
            </h1>
            <p class="mt-1 text-sm text-ink-600">
                Target dana, total RAB, dan total tahapan wajib sama persis. Sistem menolak jika tidak seimbang.
            </p>
        </div>
        <a href="{{ route('pengaju.kampanye.index') }}" class="dt-btn-secondary">Batal</a>
    </header>

    {{-- 1. Informasi dasar --}}
    <section class="dt-card mt-6 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-ink-900">1. Informasi dasar</h2>

        <div class="mt-5 space-y-4">
            <div>
                <label for="title" class="dt-label">Judul kampanye</label>
                <input id="title" name="title" type="text" required maxlength="150" class="dt-input"
                       value="{{ old('title', $campaign->title) }}"
                       placeholder="Contoh: Perbaikan Atap Madrasah Al-Hikmah, Kendal">
                @error('title') <p class="dt-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category" class="dt-label">Kategori</label>
                    <select id="category" name="category" required class="dt-input">
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $campaign->category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="dt-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="deadline" class="dt-label">Batas waktu <span class="font-normal text-ink-400">(opsional)</span></label>
                    <input id="deadline" name="deadline" type="date" class="dt-input"
                           value="{{ old('deadline', $campaign->deadline?->format('Y-m-d')) }}"
                           min="{{ now()->addDay()->format('Y-m-d') }}">
                    @error('deadline') <p class="dt-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="summary" class="dt-label">Ringkasan singkat</label>
                <textarea id="summary" name="summary" rows="2" required maxlength="300" class="dt-input"
                          placeholder="Satu-dua kalimat yang muncul di kartu kampanye.">{{ old('summary', $campaign->summary) }}</textarea>
                @error('summary') <p class="dt-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="dt-label">Cerita lengkap</label>
                <textarea id="description" name="description" rows="9" required class="dt-input"
                          placeholder="Jelaskan situasinya, siapa yang terdampak, dan kenapa dana ini dibutuhkan sekarang.">{{ old('description', $campaign->description) }}</textarea>
                <p class="dt-hint">Donatur membaca bagian ini sebelum memutuskan. Sebutkan angka dan fakta konkret.</p>
                @error('description') <p class="dt-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cover" class="dt-label">Foto sampul</label>
                <input id="cover" name="cover" type="file" accept="image/*"
                       class="dt-input file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700">
                <p class="dt-hint">
                    Rasio 16:9, maksimal {{ round(config('donasi.max_upload_kb') / 1024, 1) }} MB.
                    @if ($campaign->cover_path) Sudah ada sampul — unggah baru untuk mengganti. @endif
                </p>
                @error('cover') <p class="dt-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- 2. Target & RAB --}}
    <section class="dt-card mt-6 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-ink-900">2. Target dana &amp; rincian anggaran</h2>
        <p class="mt-1 text-sm text-ink-600">
            RAB inilah yang nanti dibandingkan dengan nota yang Anda unggah. Buat sedetail mungkin.
        </p>

        <div class="mt-5">
            <label for="target_amount" class="dt-label">Target dana <span class="font-normal text-ink-500">(dihitung otomatis dari Total RAB)</span></label>
            <div class="relative max-w-xs">
                <span class="absolute inset-y-0 left-0 grid w-11 place-items-center text-sm font-semibold text-ink-500" aria-hidden="true">Rp</span>
                <input id="target_amount" name="target_amount" type="number" readonly
                       class="dt-input pl-11 font-bold tabular-nums bg-ink-50 cursor-not-allowed text-ink-600" 
                       :value="totalItems">
            </div>
            <p class="dt-hint" x-show="target > 0" x-cloak>
                Terbaca: <span class="font-semibold text-ink-700" x-text="format(target)"></span>
            </p>
            @error('target_amount') <p class="dt-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6 -mx-1 overflow-x-auto">
            <table class="dt-table min-w-[640px]">
                <thead>
                    <tr>
                        <th scope="col" class="w-2/5">Nama item</th>
                        <th scope="col">Jumlah</th>
                        <th scope="col">Satuan</th>
                        <th scope="col">Harga satuan</th>
                        <th scope="col" class="text-right">Subtotal</th>
                        <th scope="col"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, i) in items" :key="i">
                        <tr>
                            <td>
                                <input type="text" required maxlength="150" class="dt-input"
                                       :name="`items[${i}][name]`" x-model="item.name" placeholder="Genteng beton">
                            </td>
                            <td>
                                <input type="number" required min="1" class="dt-input w-24 tabular-nums"
                                       :name="`items[${i}][quantity]`" x-model.number="item.quantity">
                            </td>
                            <td>
                                <input type="text" required maxlength="30" class="dt-input w-24"
                                       :name="`items[${i}][unit]`" x-model="item.unit" placeholder="unit">
                            </td>
                            <td>
                                <input type="number" required min="0" step="500" class="dt-input w-36 tabular-nums"
                                       :name="`items[${i}][unit_price]`" x-model.number="item.unit_price">
                            </td>
                            <td class="text-right font-semibold whitespace-nowrap tabular-nums"
                                x-text="format(item.quantity * item.unit_price)"></td>
                            <td class="text-right">
                                <button type="button" x-show="items.length > 1" @click="items.splice(i, 1)"
                                        class="rounded-lg p-1.5 text-ink-400 hover:bg-rose-50 hover:text-rose-600">
                                    <span class="sr-only">Hapus baris</span>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <button type="button" @click="items.push({ name: '', quantity: 1, unit: 'unit', unit_price: 0 })"
                class="dt-btn-secondary mt-3">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Tambah item
        </button>

        <div class="mt-5 rounded-xl border p-4 transition-colors"
             :class="totalItems === target && target > 0 ? 'border-brand-200 bg-brand-50' : 'border-amber-200 bg-amber-50'">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="text-sm font-semibold" :class="totalItems === target && target > 0 ? 'text-brand-900' : 'text-amber-900'">
                    Total RAB
                </span>
                <span class="text-lg font-extrabold tabular-nums"
                      :class="totalItems === target && target > 0 ? 'text-brand-800' : 'text-amber-800'"
                      x-text="format(totalItems)"></span>
            </div>
            <p class="mt-1 text-xs" :class="totalItems === target && target > 0 ? 'text-brand-900/75' : 'text-amber-900/80'"
               x-text="totalItems === target && target > 0
                   ? 'Seimbang dengan target dana.'
                   : `Selisih ${format(Math.abs(target - totalItems))} dari target dana.`"></p>
        </div>

        @error('items') <p class="dt-error">{{ $message }}</p> @enderror
    </section>

    {{-- 3. Tahapan pencairan --}}
    <section class="dt-card mt-6 p-5 sm:p-6">
        <h2 class="text-lg font-bold text-ink-900">3. Tahapan pencairan</h2>
        <p class="mt-1 text-sm text-ink-600">
            Anda tidak menerima dana sekaligus. Tahap 2 baru terbuka setelah nota tahap 1 diverifikasi admin.
        </p>

        @if ($campaign->exists && $campaign->milestones->where('status', '!=', 'locked')->isNotEmpty())
            <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                Sebagian tahap sudah berjalan, jadi susunan tahapan dikunci dan tidak bisa diubah lagi.
            </div>
        @endif

        <div class="mt-5 space-y-3">
            <template x-for="(m, i) in milestones" :key="i">
                <div class="rounded-xl border border-ink-200 p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-ink-100 text-xs font-bold text-ink-600"
                              x-text="i + 1"></span>
                        <input type="text" required maxlength="150" class="dt-input flex-1"
                               :name="`milestones[${i}][title]`" x-model="m.title" placeholder="Pembelian material">
                        <button type="button" x-show="milestones.length > 1" @click="milestones.splice(i, 1)"
                                class="rounded-lg p-1.5 text-ink-400 hover:bg-rose-50 hover:text-rose-600">
                            <span class="sr-only">Hapus tahap</span>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                        </button>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_200px]">
                        <textarea rows="2" maxlength="1000" class="dt-input"
                                  :name="`milestones[${i}][description]`" x-model="m.description"
                                  placeholder="Apa yang dikerjakan di tahap ini?"></textarea>
                        <div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 grid w-11 place-items-center text-sm font-semibold text-ink-500" aria-hidden="true">Rp</span>
                                <input type="number" required min="1" class="dt-input pl-11 font-semibold tabular-nums"
                                       :name="`milestones[${i}][amount]`" x-model.number="m.amount">
                            </div>
                            <p class="dt-hint" x-text="m.amount > 0 ? format(m.amount) : 'Nominal tahap ini'"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" @click="milestones.push({ title: `Tahap ${milestones.length + 1}`, description: '', amount: 0 })"
                class="dt-btn-secondary mt-3">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Tambah tahap
        </button>

        <div class="mt-5 rounded-xl border p-4 transition-colors"
             :class="totalMilestones === target && target > 0 ? 'border-brand-200 bg-brand-50' : 'border-amber-200 bg-amber-50'">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="text-sm font-semibold" :class="totalMilestones === target && target > 0 ? 'text-brand-900' : 'text-amber-900'">
                    Total tahapan
                </span>
                <span class="text-lg font-extrabold tabular-nums"
                      :class="totalMilestones === target && target > 0 ? 'text-brand-800' : 'text-amber-800'"
                      x-text="format(totalMilestones)"></span>
            </div>
            <p class="mt-1 text-xs" :class="totalMilestones === target && target > 0 ? 'text-brand-900/75' : 'text-amber-900/80'"
               x-text="totalMilestones === target && target > 0
                   ? 'Seimbang dengan target dana.'
                   : `Selisih ${format(Math.abs(target - totalMilestones))} dari target dana.`"></p>
        </div>

        @error('milestones') <p class="dt-error">{{ $message }}</p> @enderror
    </section>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <button type="submit" class="dt-btn-primary px-6 py-3 text-base">
            {{ $campaign->exists ? 'Simpan perubahan' : 'Simpan sebagai draf' }}
        </button>
        <p class="text-sm text-ink-500">
            Draf belum tayang. Ajukan untuk review dari halaman &ldquo;Kampanye saya&rdquo;.
        </p>
    </div>
</form>

@push('head')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('formKampanye', (awal) => ({
            items: awal.items,
            milestones: awal.milestones,

            get target() {
                return this.totalItems;
            },

            get totalItems() {
                return this.items.reduce((n, i) => n + (Number(i.quantity) || 0) * (Number(i.unit_price) || 0), 0);
            },

            get totalMilestones() {
                return this.milestones.reduce((n, m) => n + (Number(m.amount) || 0), 0);
            },

            format(value) {
                return 'Rp' + new Intl.NumberFormat('id-ID').format(Math.round(value || 0));
            },
        }));
    });
</script>
@endpush
@endsection
